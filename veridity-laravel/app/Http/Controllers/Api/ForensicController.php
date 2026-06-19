<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ForensicResource;
use App\Models\ForensicAnalysis;
use App\Models\User;
use App\Services\AuditReportService;
use App\Services\EvidenceStorage;
use App\Services\PaymentProofContentValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class ForensicController extends Controller
{
    private const ANALYSIS_CANCEL_TTL_SECONDS = 1800;

    private const MESSAGES = [
        'analysis_cancel_requested' => [
            'en' => 'Analysis cancellation requested.',
            'id' => 'Permintaan pembatalan analisis diterima.',
        ],
        'analysis_cancelled' => [
            'en' => 'Analysis was cancelled.',
            'id' => 'Analisis dibatalkan.',
        ],
        'analysis_token_required' => [
            'en' => 'Analysis token is required.',
            'id' => 'Token analisis wajib dikirim.',
        ],
        'document_busy' => [
            'en' => 'The forensic document analysis service is busy or waking up. Please wait 30-60 seconds, then try again.',
            'id' => 'Layanan analisis dokumen forensik sedang sibuk atau baru aktif. Silakan tunggu 30-60 detik lalu coba lagi.',
        ],
        'document_failed' => [
            'en' => 'Document analysis failed: ',
            'id' => 'Analisis dokumen gagal: ',
        ],
        'document_success' => [
            'en' => 'Document analysis completed successfully!',
            'id' => 'Analisis dokumen berhasil selesai!',
        ],
        'image_failed' => [
            'en' => 'Image analysis failed: ',
            'id' => 'Analisis gambar gagal: ',
        ],
        'image_success' => [
            'en' => 'Image analysis completed!',
            'id' => 'Analisis citra selesai!',
        ],
    ];

    private function pythonEngineUrl(): string
    {
        return rtrim((string) config('services.veridity.python_engine_url', 'http://127.0.0.1:8001'), '/');
    }

    private function normalizeLanguage(?string $language): string
    {
        return strtolower((string) $language) === 'id' ? 'id' : 'en';
    }

    private function message(string $key, ?string $language): string
    {
        $locale = $this->normalizeLanguage($language);

        return self::MESSAGES[$key][$locale] ?? self::MESSAGES[$key]['en'] ?? $key;
    }

    private function cancellationCacheKey(string $token): string
    {
        return 'veridity:analysis:cancelled:'.$token;
    }

    private function markAnalysisCancelled(string $token): void
    {
        Cache::put($this->cancellationCacheKey($token), true, self::ANALYSIS_CANCEL_TTL_SECONDS);
    }

    private function isAnalysisCancelled(?string $token): bool
    {
        return is_string($token)
            && $token !== ''
            && Cache::get($this->cancellationCacheKey($token)) === true;
    }

    private function cancelledResponse(?string $language)
    {
        return response()->json([
            'status' => 'cancelled',
            'message' => $this->message('analysis_cancelled', $language),
        ], 499);
    }

    private function shouldCallRemotePython(): bool
    {
        $engineUrl = $this->pythonEngineUrl();

        return $engineUrl !== '' && ! str_contains($engineUrl, '127.0.0.1') && ! str_contains($engineUrl, 'localhost');
    }

    private function userCanAccessAudit(ForensicAnalysis $analysis): bool
    {
        $currentUser = Auth::user();

        if (! $currentUser) {
            return false;
        }

        $isOwner = (int) $analysis->user_id === (int) $currentUser->id;
        $isAdmin = isset($currentUser->role) && strtolower($currentUser->role) === 'admin';

        return $isOwner || $isAdmin;
    }

    private function findAccessibleAudit(int|string $id): ForensicAnalysis
    {
        $analysis = ForensicAnalysis::findOrFail($id);

        if (! $this->userCanAccessAudit($analysis)) {
            abort(404);
        }

        return $analysis;
    }

    private function reportService(): AuditReportService
    {
        return app(AuditReportService::class);
    }

    private function evidenceStorage(): EvidenceStorage
    {
        return app(EvidenceStorage::class);
    }

    private function paymentProofValidator(): PaymentProofContentValidator
    {
        return app(PaymentProofContentValidator::class);
    }

    private function storeEvidenceFile(string $path, string $localPath): string
    {
        if (! $this->evidenceStorage()->putLocalFile($path, $localPath)) {
            throw new \RuntimeException('Gagal menyimpan file permanen ke Supabase Storage.');
        }

        return $path;
    }

    private function storeImageAnalysisAssets(array $result, int $userId, string $outputFolder): void
    {
        foreach (['ela', 'noise'] as $key) {
            $fileName = $result['results'][$key]['image_url'] ?? null;
            if (! $fileName) {
                continue;
            }

            $localPath = $outputFolder.DIRECTORY_SEPARATOR.basename($fileName);
            if (! file_exists($localPath)) {
                continue;
            }

            $this->storeEvidenceFile('results/'.$userId.'/'.basename($fileName), $localPath);
            @unlink($localPath);
        }
    }

    private function deleteAnalysisArtifacts(ForensicAnalysis $analysis): void
    {
        $this->evidenceStorage()->delete($analysis->s3_path);

        $reportData = is_array($analysis->final_result) ? $analysis->final_result : [];
        if (isset($reportData['full_report']['results']['ela'])) {
            $elaFile = $reportData['full_report']['results']['ela']['image_url'] ?? null;
            $noiseFile = $reportData['full_report']['results']['noise']['image_url'] ?? null;

            if ($elaFile) {
                $this->evidenceStorage()->delete('results/'.$analysis->user_id.'/'.basename($elaFile));
            }
            if ($noiseFile) {
                $this->evidenceStorage()->delete('results/'.$analysis->user_id.'/'.basename($noiseFile));
            }
        }

        $reportPaths = array_filter(array_merge(
            [$analysis->report_pdf_path],
            array_values($reportData['report_pdf_paths'] ?? [])
        ));

        foreach (array_unique($reportPaths) as $reportPath) {
            $this->evidenceStorage()->delete($reportPath);
        }

        $analysis->delete();
    }

    private function deleteCancelledAnalysisForToken(string $token, ?int $userId): bool
    {
        if ($token === '' || ! $userId) {
            return false;
        }

        $analysis = ForensicAnalysis::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(12)
            ->get()
            ->first(fn (ForensicAnalysis $item) => data_get($item->final_result, 'analysis_token') === $token);

        if (! $analysis) {
            return false;
        }

        $this->deleteAnalysisArtifacts($analysis);

        return true;
    }

    private function integrationUserId(): ?int
    {
        $configuredUserId = config('services.veridity.integration_user_id');

        if ($configuredUserId && User::whereKey($configuredUserId)->exists()) {
            return (int) $configuredUserId;
        }

        return User::orderBy('id')->value('id');
    }

    public function cancelAnalysis(Request $request)
    {
        $language = $this->normalizeLanguage($request->input('language'));

        $validated = $request->validate([
            'analysis_token' => 'required|string|max:120',
            'language' => 'nullable|string|in:en,id',
        ], [
            'analysis_token.required' => $this->message('analysis_token_required', $language),
        ]);

        $token = $validated['analysis_token'];
        $this->markAnalysisCancelled($token);
        $deletedLateResult = $this->deleteCancelledAnalysisForToken($token, Auth::id());

        if ($this->shouldCallRemotePython()) {
            try {
                Http::timeout(8)->post($this->pythonEngineUrl().'/cancel-analysis', [
                    'analysis_token' => $token,
                    'language' => $language,
                ]);
            } catch (\Throwable $e) {
                Log::warning('Failed to forward analysis cancellation to Python engine', [
                    'engine_url' => $this->pythonEngineUrl(),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => $this->message('analysis_cancel_requested', $language),
            'deleted_late_result' => $deletedLateResult,
        ]);
    }

    private function runImageAnalysisCommand(string $fullPathFile, string $outputFolder, string $language = 'en', ?string $analysisToken = null): array
    {
        if ($this->isAnalysisCancelled($analysisToken)) {
            return [
                'status' => 'cancelled',
                'message' => $this->message('analysis_cancelled', $language),
            ];
        }

        $serviceResult = $this->runImageAnalysisService($fullPathFile, $outputFolder, $language, $analysisToken);
        if ($serviceResult !== null) {
            return $serviceResult;
        }

        $pythonPath = config('services.veridity.python_path');
        $scriptPath = config('services.veridity.python_toolkit_script');

        if (! $pythonPath || ! file_exists($pythonPath)) {
            return [
                'status' => 'error',
                'message' => 'Konfigurasi PYTHON_PATH tidak valid. Periksa file .env Laravel.',
            ];
        }

        if (! $scriptPath || ! file_exists($scriptPath)) {
            return [
                'status' => 'error',
                'message' => 'Konfigurasi PYTHON_TOOLKIT_SCRIPT tidak valid. Periksa file .env Laravel.',
            ];
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $scriptDirectory = dirname($scriptPath);
        $nltkDataPaths = array_filter([
            $scriptDirectory.DIRECTORY_SEPARATOR.'nltk_data',
            getenv('APPDATA') ? getenv('APPDATA').DIRECTORY_SEPARATOR.'nltk_data' : null,
            getenv('USERPROFILE') ? getenv('USERPROFILE').DIRECTORY_SEPARATOR.'nltk_data' : null,
        ]);

        $environment = array_merge($_SERVER, $_ENV, [
            'PYTHONPATH' => $scriptDirectory,
            'PYTHONIOENCODING' => 'utf-8',
            'TF_CPP_MIN_LOG_LEVEL' => '3',
            'NLTK_DATA' => implode(PATH_SEPARATOR, $nltkDataPaths),
        ]);

        $process = proc_open(
            [
                $pythonPath,
                $scriptPath,
                $fullPathFile,
                $outputFolder,
            ],
            $descriptorSpec,
            $pipes,
            $scriptDirectory,
            $environment
        );

        if (! is_resource($process)) {
            return [
                'status' => 'error',
                'message' => 'Analisis gambar gagal karena proses Python tidak dapat dijalankan.',
            ];
        }

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errorOutput = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if (! is_string($output) || trim($output) === '') {
            Log::error('Image forensic command returned empty output', [
                'python_path' => $pythonPath,
                'script_path' => $scriptPath,
                'file' => $fullPathFile,
                'output_folder' => $outputFolder,
                'exit_code' => $exitCode,
                'stderr' => $errorOutput,
            ]);

            return [
                'status' => 'error',
                'message' => 'Analisis gambar gagal karena Python tidak mengembalikan output. '.str($errorOutput ?: 'Pastikan dependency Python sudah terpasang.')->limit(180),
            ];
        }

        $result = json_decode(trim($output), true);

        if (! is_array($result)) {
            Log::error('Image forensic command returned invalid JSON', [
                'python_path' => $pythonPath,
                'script_path' => $scriptPath,
                'output' => $output,
                'stderr' => $errorOutput,
            ]);

            return [
                'status' => 'error',
                'message' => 'Analisis gambar gagal karena output Python tidak valid: '.str($output)->limit(180),
            ];
        }

        return $result;
    }

    private function runImageAnalysisService(string $fullPathFile, string $outputFolder, string $language = 'en', ?string $analysisToken = null): ?array
    {
        if (! $this->shouldCallRemotePython()) {
            return null;
        }

        $engineUrl = $this->pythonEngineUrl();

        try {
            $fileStream = fopen($fullPathFile, 'r');

            if (! is_resource($fileStream)) {
                return [
                    'status' => 'error',
                    'message' => 'File gambar tidak dapat dibaca untuk dikirim ke layanan Python.',
                ];
            }

            $response = Http::timeout(600)
                ->attach('file', $fileStream, basename($fullPathFile))
                ->post($engineUrl.'/analyze-image', array_filter([
                    'language' => $language,
                    'analysis_token' => $analysisToken,
                ]));

            fclose($fileStream);

            if ($response->status() === 499) {
                $cancelledResult = $response->json();

                return is_array($cancelledResult)
                    ? $cancelledResult
                    : [
                        'status' => 'cancelled',
                        'message' => $this->message('analysis_cancelled', $language),
                    ];
            }

            if ($response->failed()) {
                $pythonMessage = $response->json('message')
                    ?? $response->json('error')
                    ?? $response->body();

                return [
                    'status' => 'error',
                    'message' => 'Layanan analisis gambar Python gagal: '.str((string) $pythonMessage)->limit(220),
                ];
            }

            $result = $response->json();

            if (! is_array($result)) {
                return [
                    'status' => 'error',
                    'message' => 'Analisis gambar gagal karena output layanan Python tidak valid.',
                ];
            }

            foreach (($result['visual_assets'] ?? []) as $asset) {
                $filename = $asset['filename'] ?? null;
                $content = $asset['content_base64'] ?? null;

                if (! $filename || ! $content) {
                    continue;
                }

                if (! file_exists($outputFolder)) {
                    mkdir($outputFolder, 0777, true);
                }

                file_put_contents($outputFolder.DIRECTORY_SEPARATOR.basename($filename), base64_decode($content));
            }

            unset($result['visual_assets']);

            return $result;
        } catch (\Throwable $e) {
            Log::error('Image analysis service call failed', [
                'engine_url' => $engineUrl,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Analisis gambar gagal karena layanan Python tidak dapat dihubungi.',
            ];
        }
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:15000',
        ]);

        $file = $request->file('image');
        $temporaryPath = $file->storeAs('tmp/uploads', time().'_'.$file->getClientOriginalName(), 'public');
        $fullTemporaryPath = storage_path('app/public/'.$temporaryPath);
        $path = $this->evidenceStorage()->makePath('forensics/original', auth()->id(), $file->getClientOriginalName());
        $this->storeEvidenceFile($path, $fullTemporaryPath);
        Storage::disk('public')->delete($temporaryPath);

        $analysis = ForensicAnalysis::create([
            'user_id' => auth()->id(),
            'image_name' => $file->getClientOriginalName(),
            's3_path' => $path,
            'final_result' => 'Mencurigakan',
        ]);

        return response()->json([
            'message' => 'Gambar berhasil diunggah dan sedang dianalisis',
            'data' => $analysis,
        ], 201);
    }

    public function analyzeDistriProof(Request $request)
    {
        set_time_limit(300);

        $expectedKey = (string) config('services.veridity.distri_integration_key');
        $providedKey = (string) $request->header('X-Veridity-Integration-Key');

        if ($expectedKey === '' || ! hash_equals($expectedKey, $providedKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Integration key tidak valid.',
            ], 401);
        }

        $request->validate([
            'proof' => 'required|image|mimes:jpeg,png,jpg|max:15000',
            'order_id' => 'required|string|max:80',
            'payment_method' => 'required|string|max:80',
            'payment_channel' => 'required|string|max:120',
            'expected_amount' => 'required|numeric|min:0',
            'recipient_name' => 'nullable|string|max:160',
            'recipient_account' => 'nullable|string|max:120',
            'payment_instruction' => 'nullable|string|max:1000',
            'source' => 'nullable|string|max:40',
        ]);

        $userId = $this->integrationUserId();
        if (! $userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'User integrasi VERIDITY belum tersedia.',
            ], 500);
        }

        try {
            $file = $request->file('proof');
            $filename = time().'_distri_'.$request->input('order_id').'_'.str_replace(' ', '_', $file->getClientOriginalName());
            $path = $file->storeAs('uploads/distri', $filename, 'public');
            $fullPathFile = storage_path('app/public/'.$path);

            $outputFolder = storage_path('app/public/results/distri');
            if (! file_exists($outputFolder)) {
                mkdir($outputFolder, 0777, true);
            }

            $result = $this->runImageAnalysisCommand($fullPathFile, $outputFolder);

            if (! $result || ($result['status'] ?? null) === 'error') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Analisis bukti pembayaran gagal: '.($result['message'] ?? 'Output Python tidak tersedia'),
                ], 500);
            }

            $elaScore = $result['results']['ela']['metrics']['anomaly_score'] ?? 0;
            $ganScore = $result['results']['ai_detection']['metrics']['gan_score'] ?? 0;
            $metaVerdict = $result['results']['metadata']['summary']['verdict'] ?? 'UNKNOWN';
            $noiseInterpretation = $result['results']['noise']['interpretation'] ?? '';
            $finalScore = $result['final_score'] ?? 0;

            $isNoiseInconsistent = str_contains($noiseInterpretation, 'tidak rata') || str_contains($noiseInterpretation, 'keanehan');
            $isMetaManipulated = $metaVerdict === 'REKAYASA DIGITAL / EDITING' || str_contains($metaVerdict, 'EDITING');

            $statusLabel = 'BUKTI PEMBAYARAN AMAN';
            $statusColor = 'success';

            if ($ganScore > 0.5 || ($result['verdict'] ?? '') === 'DEEPFAKE / AI GENERATED') {
                $statusLabel = 'BUKTI PEMBAYARAN BERBAHAYA';
                $statusColor = 'danger';
            } elseif ($finalScore < 45 || $elaScore > 45 || $ganScore > 0.85 || ($result['verdict'] ?? '') === 'MANIPULATED') {
                $statusLabel = 'BUKTI PEMBAYARAN BERBAHAYA';
                $statusColor = 'danger';
            } elseif ($elaScore > 15 || $ganScore > 0.4 || ($isNoiseInconsistent && $elaScore > 8.0) || $isMetaManipulated) {
                $statusLabel = 'BUKTI PEMBAYARAN MENCURIGAKAN';
                $statusColor = 'warning';
            }

            if ($statusColor === 'success' && isset($result['results']['noise'])) {
                $result['results']['noise']['warnings'] = [];
                $result['results']['noise']['interpretation'] = 'Noise bukti pembayaran masih berada dalam toleransi hasil akhir dan tidak cukup kuat untuk menyimpulkan manipulasi.';
            }

            $paymentValidation = $this->paymentProofValidator()->validate($fullPathFile, [
                'amount' => $request->input('expected_amount'),
                'recipient_name' => $request->input('recipient_name'),
                'recipient_account' => $request->input('recipient_account'),
                'payment_channel' => $request->input('payment_channel'),
                'payment_instruction' => $request->input('payment_instruction'),
            ]);

            if ($paymentValidation['status'] === 'failed') {
                $statusLabel = 'BUKTI PEMBAYARAN TIDAK SESUAI';
                $statusColor = 'danger';
            } elseif ($statusColor === 'success' && $paymentValidation['status'] === 'review_required') {
                $statusLabel = 'BUKTI PEMBAYARAN PERLU REVIEW MANUAL';
                $statusColor = 'warning';
            }

            $result['results']['payment_validation'] = $paymentValidation;

            $analysis = ForensicAnalysis::create([
                'user_id' => $userId,
                'image_name' => $filename,
                's3_path' => $path,
                'ela_score' => $elaScore,
                'is_deepfake' => ($ganScore > 0.5),
                'metadata_details' => array_merge($result['results']['metadata'] ?? [], [
                    'integration' => [
                        'source' => $request->input('source', 'distri'),
                        'order_id' => $request->input('order_id'),
                        'payment_method' => $request->input('payment_method'),
                        'payment_channel' => $request->input('payment_channel'),
                        'expected_amount' => $request->input('expected_amount'),
                        'recipient_name' => $request->input('recipient_name'),
                        'recipient_account' => $request->input('recipient_account'),
                        'payment_validation' => $paymentValidation,
                    ],
                ]),
                'noise_status' => $result['results']['noise']['warnings'][0] ?? ($isNoiseInconsistent ? 'Inconsistent Noise Detected' : 'Normal'),
                'final_result' => [
                    'summary_label' => $statusLabel,
                    'summary_color' => $statusColor,
                    'full_report' => $result,
                ],
            ]);

            $this->reportService()->ensureReport($analysis);

            return response()->json([
                'status' => 'success',
                'message' => 'Nota berhasil dianalisis',
                'data' => [
                    'audit_id' => $analysis->id,
                    'summary_label' => $statusLabel,
                    'summary_color' => $statusColor,
                    'final_score' => $finalScore,
                    'payment_validation' => $paymentValidation,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function analyze(Request $request)
    {
        // Memberikan napas waktu lebih panjang untuk komputasi teks intensif model AI Hugging Face via CPU.
        set_time_limit(600);
        $language = $this->normalizeLanguage($request->input('language'));
        $analysisToken = $request->input('analysis_token');

        // 1. Validasi Fleksibel: Menerima rumpun Gambar (Citra) ATAU Dokumen Teks
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,pdf|max:15000',
            'language' => 'nullable|string|in:en,id',
            'analysis_token' => 'nullable|string|max:120',
        ], [
            'image.required' => $language === 'id' ? 'File analisis wajib dipilih.' : 'Analysis file is required.',
            'image.file' => $language === 'id' ? 'File analisis tidak valid.' : 'Analysis file is invalid.',
            'image.uploaded' => $language === 'id'
                ? 'File gagal diunggah. Pastikan ukuran file maksimal 15MB dan formatnya JPG, JPEG, PNG, atau PDF dokumen teks.'
                : 'File upload failed. Make sure the file is under 15MB and uses JPG, JPEG, PNG, or text-based PDF format.',
            'image.mimes' => $language === 'id'
                ? 'Format file belum didukung. Gunakan JPG, JPEG, PNG, atau PDF dokumen teks.'
                : 'Unsupported file format. Use JPG, JPEG, PNG, or text-based PDF documents.',
            'image.max' => $language === 'id' ? 'Ukuran file maksimal 15MB.' : 'Maximum file size is 15MB.',
        ]);

        if ($this->isAnalysisCancelled($analysisToken)) {
            return $this->cancelledResponse($language);
        }

        try {
            $file = $request->file('image');
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = time().'_'.$file->getClientOriginalName();

            // Simpan sementara agar Python dapat membaca file melalui path lokal.
            $path = $file->storeAs('tmp/uploads', $filename, 'public');
            $fullPathFile = storage_path('app/public/'.$path);

            // =========================================================================
            // PERCABANGAN KONDISI CABANG A: JIKA BERKAS ADALAH DOKUMEN TEKS (PDF)
            // =========================================================================
            if ($extension === 'pdf') {
                // Ambil stream file agar multipart upload stabil untuk PDF besar.
                $fileStream = fopen($fullPathFile, 'r');

                try {
                    // Panggil REST API Backend Python dengan proteksi retry karena Render free dapat cold start.
                    $response = Http::connectTimeout(60)
                        ->timeout(600)
                        ->retry(2, 8000)
                        ->attach('file', $fileStream, $filename)
                        ->post($this->pythonEngineUrl().'/analyze-document', array_filter([
                            'extension' => $extension,
                            'language' => $language,
                            'analysis_token' => $analysisToken,
                        ]));
                } catch (\Throwable $pythonException) {
                    if (is_resource($fileStream)) {
                        fclose($fileStream);
                    }

                    Log::warning('Veridity document Python service unreachable', [
                        'message' => $pythonException->getMessage(),
                        'engine_url' => $this->pythonEngineUrl(),
                        'filename' => $filename,
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => $this->message('document_busy', $language),
                    ], 503);
                }

                if (is_resource($fileStream)) {
                    fclose($fileStream);
                }

                if ($response->status() === 499) {
                    return $this->cancelledResponse($language);
                }

                if ($response->failed()) {
                    Log::warning('Veridity document Python service failed', [
                        'status' => $response->status(),
                        'body' => mb_substr($response->body(), 0, 1000),
                        'engine_url' => $this->pythonEngineUrl(),
                        'filename' => $filename,
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => $this->message('document_busy', $language),
                    ], 500);
                }

                $result = $response->json();

                if ($this->isAnalysisCancelled($analysisToken) || ($result['status'] ?? null) === 'cancelled') {
                    return $this->cancelledResponse($language);
                }

                // Proteksi Fail-Safe untuk Output JSON Dokumen
                if (! isset($result['status']) || $result['status'] === 'error') {
                    return response()->json([
                        'status' => 'error',
                        'message' => $this->message('document_failed', $language).($result['message'] ?? ($language === 'id' ? 'Output data kosong' : 'No output data')),
                    ], 500);
                }

                // Ambil map klasifikasi teks secara aman dari output response engine Python
                $classificationMapData = $result['classification_map'] ?? $result['results'] ?? [];

                if ($this->isAnalysisCancelled($analysisToken)) {
                    Storage::disk('public')->delete($path);

                    return $this->cancelledResponse($language);
                }

                $evidenceOriginalPath = $this->evidenceStorage()->makePath('forensics/original', Auth::id(), $filename);
                $this->storeEvidenceFile($evidenceOriginalPath, $fullPathFile);
                Storage::disk('public')->delete($path);

                if ($this->isAnalysisCancelled($analysisToken)) {
                    $this->evidenceStorage()->delete($evidenceOriginalPath);

                    return $this->cancelledResponse($language);
                }

                // Simpan Data Hasil Olahan Dokumen ke Oracle Database dengan aman masuk ke final_result
                $analysis = ForensicAnalysis::create([
                    'user_id' => Auth::id(),
                    'image_name' => $filename, // Berfungsi ganda menyimpan nama berkas dokumen
                    's3_path' => $evidenceOriginalPath,
                    'ela_score' => 0, // Fallback nilai 0 karena dokumen tidak memiliki ELA
                    'is_deepfake' => (($result['summary_color'] ?? '') === 'danger'),
                    'metadata_details' => [
                        'summary' => [
                            'status' => $result['summary_label'] ?? ($language === 'id' ? 'TEKS CAMPURAN' : 'MIXED TEXT'),
                            'verdict' => $result['summary_label'] ?? ($language === 'id' ? 'TEKS CAMPURAN' : 'MIXED TEXT'),
                        ],
                    ],
                    'noise_status' => 'Not Applicable',
                    'final_result' => [
                        'analysis_token' => $analysisToken,
                        'language' => $language,
                        'summary_label' => $result['summary_label'] ?? ($language === 'id' ? 'TEKS CAMPURAN' : 'MIXED TEXT'),
                        'summary_color' => $result['summary_color'] ?? 'warning',
                        'full_report' => [
                            'language' => $language,
                            'final_score' => $result['final_score'] ?? 0,
                            'summary_label' => $result['summary_label'] ?? ($language === 'id' ? 'TEKS CAMPURAN' : 'MIXED TEXT'),
                            'summary_color' => $result['summary_color'] ?? 'warning',
                            'results' => $result['results'] ?? [],
                            'classification_map' => $classificationMapData,
                        ],
                    ],
                ]);

                if ($this->isAnalysisCancelled($analysisToken)) {
                    $this->deleteAnalysisArtifacts($analysis);

                    return $this->cancelledResponse($language);
                }

                $reportPath = $this->reportService()->ensureReport($analysis, $language);
                $analysis->refresh();

                if ($this->isAnalysisCancelled($analysisToken)) {
                    $this->deleteAnalysisArtifacts($analysis);

                    return $this->cancelledResponse($language);
                }

                if (! $request->expectsJson()) {
                    return redirect()->route('user.result', $analysis->id)->with('success', $this->message('document_success', $language));
                }

                return response()->json([
                    'status' => 'success',
                    'message' => $this->message('document_success', $language),
                    'data' => new ForensicResource($analysis),
                ]);
            }

            // =========================================================================
            // PERCABANGAN KONDISI CABANG B: JIKA BERKAS ADALAH CITRA FOTO (LOGIKA LAMA)
            // =========================================================================
            $outputFolder = storage_path('app/public/results/'.Auth::id());
            if (! file_exists($outputFolder)) {
                mkdir($outputFolder, 0777, true);
            }

            $result = $this->runImageAnalysisCommand($fullPathFile, $outputFolder, $language, $analysisToken);

            if (($result['status'] ?? null) === 'cancelled' || $this->isAnalysisCancelled($analysisToken)) {
                return $this->cancelledResponse($language);
            }

            if (! $result || $result['status'] === 'error') {
                return response()->json([
                    'status' => 'error',
                    'message' => $this->message('image_failed', $language).($result['message'] ?? ($language === 'id' ? 'Output Python tidak tersedia' : 'Python output is unavailable')),
                ], 500);
            }

            $elaScore = $result['results']['ela']['metrics']['anomaly_score'] ?? 0;
            $ganScore = $result['results']['ai_detection']['metrics']['gan_score'] ?? 0;
            $metaVerdict = $result['results']['metadata']['summary']['verdict'] ?? 'UNKNOWN';
            $noiseInterpretation = $result['results']['noise']['interpretation'] ?? '';
            $noiseWarningKeys = $result['results']['noise']['warning_keys'] ?? [];
            $noiseWarnings = $result['results']['noise']['warnings'] ?? [];
            $noiseAuthenticityScore = (float) ($result['results']['noise']['metrics']['noise_authenticity_score'] ?? 100);
            $metaAuthenticityScore = (float) ($result['results']['metadata']['summary']['authenticity_score'] ?? 100);
            $isMetadataMissing = (bool) ($result['results']['metadata']['summary']['metadata_missing'] ?? false);
            $finalScore = $result['final_score'] ?? 0;

            $hasStrongNoiseSignal = $noiseAuthenticityScore < 50
                || in_array('noise_very_low', $noiseWarningKeys, true)
                || ($noiseAuthenticityScore < 75 && $elaScore > 8);
            $isNoiseInconsistent = $hasStrongNoiseSignal
                || str_contains(strtolower($noiseInterpretation), 'tidak rata')
                || str_contains(strtolower($noiseInterpretation), 'keanehan')
                || (
                    str_contains(strtolower($noiseInterpretation), 'local variation')
                    && ($noiseAuthenticityScore < 75 || $elaScore > 8)
                );
            $isMetaManipulated = ($metaVerdict === 'REKAYASA DIGITAL / EDITING'
                || $metaVerdict === 'REKAYASA DIGITAL / GENERATOR AI (SANGAT BERBAHAYA)'
                || str_contains(strtoupper($metaVerdict), 'EDITING'));
            $isMetaSuspicious = ! $isMetadataMissing
                && ($isMetaManipulated
                    || $metaAuthenticityScore < 85
                    || str_contains(strtoupper($metaVerdict), 'MENCURIGAKAN')
                    || str_contains(strtoupper($metaVerdict), 'SUSPICIOUS'));
            $pythonVerdict = strtoupper((string) ($result['verdict'] ?? ''));

            $statusLabel = $language === 'id' ? 'FOTO ASLI / JEPRETAN MURNI' : 'AUTHENTIC PHOTO / ORIGINAL CAPTURE';
            $statusColor = 'success';

            if ($ganScore > 0.5 || $pythonVerdict === 'DEEPFAKE / AI GENERATED') {
                $statusLabel = $language === 'id' ? 'SANGAT BERBAHAYA (DEEPFAKE AI)' : 'HIGH RISK (AI DEEPFAKE)';
                $statusColor = 'danger';
            } elseif ($finalScore < 45 || $elaScore > 45 || $ganScore > 0.85 || $pythonVerdict === 'MANIPULATED') {
                $statusLabel = $language === 'id' ? 'SANGAT BERBAHAYA' : 'HIGH RISK';
                $statusColor = 'danger';
            } elseif ($pythonVerdict === 'SUSPICIOUS'
                || $finalScore < 80
                || $elaScore > 8
                || $ganScore > 0.25
                || $isNoiseInconsistent
                || $isMetaSuspicious) {
                $statusLabel = $language === 'id' ? 'MENCURIGAKAN (TERINDIKASI REKAYASA)' : 'SUSPICIOUS (MANIPULATION INDICATED)';
                $statusColor = 'warning';
            }

            if ($statusColor === 'success' && isset($result['results']['noise'])) {
                $result['results']['noise']['warnings'] = [];
                $result['results']['noise']['interpretation'] = $language === 'id'
                    ? 'Pola noise memiliki variasi lokal yang masih berada dalam toleransi hasil akhir. Tidak ditemukan korelasi kuat dengan ELA, metadata, atau deteksi AI untuk menyimpulkan manipulasi.'
                    : 'The noise pattern has local variation within the final tolerance range. No strong correlation with ELA, metadata, or AI detection was found to conclude manipulation.';
                $result['results']['noise']['researcher_note'] = $language === 'id'
                    ? 'Noise diperlakukan sebagai sinyal pendukung dan telah diselaraskan dengan keputusan akhir analisis citra.'
                    : 'Noise is treated as a supporting signal and aligned with the final image-analysis decision.';
            }

            if ($this->isAnalysisCancelled($analysisToken)) {
                Storage::disk('public')->delete($path);

                return $this->cancelledResponse($language);
            }

            $evidenceOriginalPath = $this->evidenceStorage()->makePath('forensics/original', Auth::id(), $filename);
            $this->storeEvidenceFile($evidenceOriginalPath, $fullPathFile);
            $this->storeImageAnalysisAssets($result, (int) Auth::id(), $outputFolder);
            Storage::disk('public')->delete($path);

            if ($this->isAnalysisCancelled($analysisToken)) {
                $this->evidenceStorage()->delete($evidenceOriginalPath);

                return $this->cancelledResponse($language);
            }

            $analysis = ForensicAnalysis::create([
                'user_id' => Auth::id(),
                'image_name' => $filename,
                's3_path' => $evidenceOriginalPath,
                'ela_score' => $elaScore,
                'is_deepfake' => ($ganScore > 0.5),
                'metadata_details' => $result['results']['metadata'],
                'noise_status' => $result['results']['noise']['warnings'][0] ?? ($isNoiseInconsistent ? 'Inconsistent Noise Detected' : 'Normal'),
                'final_result' => [
                    'analysis_token' => $analysisToken,
                    'language' => $language,
                    'summary_label' => $statusLabel,
                    'summary_color' => $statusColor,
                    'full_report' => array_merge($result, ['language' => $language]),
                ],
            ]);

            if ($this->isAnalysisCancelled($analysisToken)) {
                $this->deleteAnalysisArtifacts($analysis);

                return $this->cancelledResponse($language);
            }

            $this->reportService()->ensureReport($analysis, $language);
            $analysis->refresh();

            if ($this->isAnalysisCancelled($analysisToken)) {
                $this->deleteAnalysisArtifacts($analysis);

                return $this->cancelledResponse($language);
            }

            if (! $request->expectsJson()) {
                return redirect()->route('user.result', $analysis->id)->with('success', $this->message('image_success', $language));
            }

            return response()->json([
                'status' => 'success',
                'message' => $this->message('image_success', $language),
                'data' => new ForensicResource($analysis),
                'visual_results' => [
                    'ela' => route('files.public', ['path' => 'results/'.Auth::id().'/'.$result['results']['ela']['image_url']]),
                    'noise' => route('files.public', ['path' => 'results/'.Auth::id().'/'.$result['results']['noise']['image_url']]),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function showResult($id)
    {
        $analysis = ForensicAnalysis::where('user_id', Auth::id())->findOrFail($id);

        return view('user.result', compact('analysis'));
    }

    public function show($id)
    {
        return response()->json([
            'status' => 'success',
            'data' => new ForensicResource($this->findAccessibleAudit($id)),
        ]);
    }

    public function history()
    {
        $myAudits = ForensicAnalysis::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => ForensicResource::collection($myAudits),
            ], 200);
        }

        return view('user.my-audits', compact('myAudits'));
    }

    public function destroy($id)
    {
        $analysis = ForensicAnalysis::findOrFail($id);

        if (! $this->userCanAccessAudit($analysis)) {
            if (request()->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'Kamu tidak punya akses!'], 403);
            }

            return redirect()->back()->with('error', 'Kamu tidak punya akses!');
        }

        $this->deleteAnalysisArtifacts($analysis);

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat audit berhasil dihapus bersih!',
            ]);
        }

        $tab = request('redirect_tab') === 'documents' ? 'documents' : 'images';

        return redirect()->route('user.my-audits', ['tab' => $tab])->with('success', 'Riwayat audit berhasil dihapus!');
    }

    public function downloadPdf($id)
    {
        // Melonggarkan batas waktu tunggu pemrosesan penyusunan PDF halaman berganda
        set_time_limit(180);

        try {
            $audit = $this->findAccessibleAudit($id);
            $language = $this->normalizeLanguage(request('language'));
            $reportPath = $this->reportService()->ensureReport($audit, $language);

            if (! $reportPath) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Laporan PDF belum tersedia. Silakan coba unduh kembali beberapa saat lagi.',
                    ], 502);
                }

                return back()->with('error', 'Laporan PDF belum tersedia. Silakan coba unduh kembali beberapa saat lagi.');
            }

            return $this->evidenceStorage()->downloadResponse(
                $reportPath,
                $this->reportService()->reportFileName($audit, $language),
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Exception $e) {
            Log::error('Gagal mengunduh laporan PDF: '.$e->getMessage());

            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan internal sistem saat memproses layout laporan cetak.',
                ], 500);
            }

            return back()->with('error', 'Terjadi kesalahan internal sistem saat memproses layout laporan cetak.');
        }
    }

    public function downloadPdfForMobile(Request $request, $id)
    {
        $plainTextToken = (string) $request->query('token', '');
        $accessToken = PersonalAccessToken::findToken($plainTextToken);

        if (! $accessToken || ! $accessToken->tokenable) {
            abort(401);
        }

        Auth::setUser($accessToken->tokenable);

        return $this->downloadPdf($id);
    }

    public function publicStorageFile(string $path)
    {
        $path = ltrim($path, '/');

        abort_if(str_contains($path, '..'), 404);
        abort_unless($this->evidenceStorage()->exists($path), 404);

        return $this->evidenceStorage()->fileResponse($path);
    }
}
