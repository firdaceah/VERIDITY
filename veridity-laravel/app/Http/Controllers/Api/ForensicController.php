<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ForensicResource;
use App\Models\ForensicAnalysis;
use App\Services\AuditReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;

class ForensicController extends Controller
{
    private function pythonEngineUrl(): string
    {
        return rtrim((string) config('services.veridity.python_engine_url', 'http://127.0.0.1:8001'), '/');
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

    private function runImageAnalysisCommand(string $fullPathFile, string $outputFolder): array
    {
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

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:15000',
        ]);

        $path = $request->file('image')->store('forensics', 'public');

        $analysis = ForensicAnalysis::create([
            'user_id' => auth()->id(),
            'image_name' => $request->file('image')->getClientOriginalName(),
            's3_path' => $path,
            'final_result' => 'Mencurigakan',
        ]);

        return response()->json([
            'message' => 'Gambar berhasil diunggah dan sedang dianalisis',
            'data' => $analysis,
        ], 201);
    }

    public function analyze(Request $request)
    {
        // Memberikan napas waktu 5 menit untuk komputasi teks intensif model AI Hugging Face via CPU
        set_time_limit(300);

        // 1. Validasi Fleksibel: Menerima rumpun Gambar (Citra) ATAU Dokumen Teks
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,pdf|max:15000',
        ]);

        try {
            $file = $request->file('image');
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = time().'_'.$file->getClientOriginalName();

            // Simpan file asli ke dalam storage lokal public/uploads
            $path = $file->storeAs('uploads', $filename, 'public');
            $fullPathFile = storage_path('app/public/'.$path);

            // =========================================================================
            // PERCABANGAN KONDISI CABANG A: JIKA BERKAS ADALAH DOKUMEN TEKS (PDF)
            // =========================================================================
            if ($extension === 'pdf') {
                // Ambil stream file agar multipart upload stabil untuk PDF besar.
                $fileStream = fopen($fullPathFile, 'r');

                // Panggil REST API Backend Python di Port 8001 dengan proteksi timeout 5 menit
                $response = Http::timeout(300)
                    ->attach('file', $fileStream, $filename)
                    ->post($this->pythonEngineUrl().'/analyze-document', [
                        'extension' => $extension,
                    ]);

                if (is_resource($fileStream)) {
                    fclose($fileStream);
                }

                if ($response->failed()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Layanan analisis dokumen forensik sedang tidak merespons. Silakan coba sesaat lagi.',
                    ], 500);
                }

                $result = $response->json();

                // Proteksi Fail-Safe untuk Output JSON Dokumen
                if (! isset($result['status']) || $result['status'] === 'error') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Analisis Dokumen Gagal: '.($result['message'] ?? 'Output data kosong'),
                    ], 500);
                }

                // Ambil map klasifikasi teks secara aman dari output response engine Python
                $classificationMapData = $result['classification_map'] ?? $result['results'] ?? [];

                // Simpan Data Hasil Olahan Dokumen ke Oracle Database dengan aman masuk ke final_result
                $analysis = ForensicAnalysis::create([
                    'user_id' => Auth::id(),
                    'image_name' => $filename, // Berfungsi ganda menyimpan nama berkas dokumen
                    's3_path' => $path,
                    'ela_score' => 0, // Fallback nilai 0 karena dokumen tidak memiliki ELA
                    'is_deepfake' => (($result['summary_color'] ?? '') === 'danger'),
                    'metadata_details' => [
                        'summary' => [
                            'status' => $result['summary_label'] ?? 'MIXED TEXT',
                            'verdict' => $result['summary_label'] ?? 'MIXED TEXT',
                        ],
                    ],
                    'noise_status' => 'Not Applicable',
                    'final_result' => [
                        'summary_label' => $result['summary_label'] ?? 'MIXED TEXT',
                        'summary_color' => $result['summary_color'] ?? 'warning',
                        'full_report' => [
                            'final_score' => $result['final_score'] ?? 0,
                            'summary_label' => $result['summary_label'] ?? 'MIXED TEXT',
                            'summary_color' => $result['summary_color'] ?? 'warning',
                            'results' => $result['results'] ?? [],
                            'classification_map' => $classificationMapData,
                        ],
                    ],
                ]);

                $this->reportService()->ensureReport($analysis);
                $analysis->refresh();

                if (! $request->expectsJson()) {
                    return redirect()->route('user.result', $analysis->id)->with('success', 'Analisis Dokumen Selesai!');
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Analisis Dokumen Berhasil Selesai!',
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

            $result = $this->runImageAnalysisCommand($fullPathFile, $outputFolder);

            if (! $result || $result['status'] === 'error') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Analisis gambar gagal: '.($result['message'] ?? 'Output Python tidak tersedia'),
                ], 500);
            }

            $elaScore = $result['results']['ela']['metrics']['anomaly_score'] ?? 0;
            $ganScore = $result['results']['ai_detection']['metrics']['gan_score'] ?? 0;
            $metaVerdict = $result['results']['metadata']['summary']['verdict'] ?? 'UNKNOWN';
            $noiseInterpretation = $result['results']['noise']['interpretation'] ?? '';
            $finalScore = $result['final_score'] ?? 0;

            $isNoiseInconsistent = str_contains($noiseInterpretation, 'tidak rata') || str_contains($noiseInterpretation, 'keanehan');
            $isMetaManipulated = ($metaVerdict === 'REKAYASA DIGITAL / EDITING' || $metaVerdict === 'REKAYASA DIGITAL / GENERATOR AI (SANGAT BERBAHAYA)' || str_contains($metaVerdict, 'EDITING'));

            $statusLabel = 'FOTO ASLI / JEPRETAN MURNI';
            $statusColor = 'success';

            if ($ganScore > 0.5 || ($result['verdict'] ?? '') === 'DEEPFAKE / AI GENERATED') {
                $statusLabel = 'SANGAT BERBAHAYA (DEEPFAKE AI)';
                $statusColor = 'danger';
            } elseif ($elaScore <= 5.0 && $ganScore <= 0.4) {
                $statusLabel = 'FOTO ASLI / JEPRETAN MURNI';
                $statusColor = 'success';
            } elseif ($finalScore < 45 || $elaScore > 45 || $ganScore > 0.85 || ($result['verdict'] ?? '') === 'MANIPULATED') {
                $statusLabel = 'SANGAT BERBAHAYA';
                $statusColor = 'danger';
            } elseif ($elaScore > 15 || $ganScore > 0.4 || ($isNoiseInconsistent && $elaScore > 8.0) || $isMetaManipulated) {
                $statusLabel = 'MENCURIGAKAN (TERINDIKASI REKAYASA)';
                $statusColor = 'warning';
            }

            if ($statusColor === 'success' && isset($result['results']['noise'])) {
                $result['results']['noise']['warnings'] = [];
                $result['results']['noise']['interpretation'] = 'Pola noise memiliki variasi lokal yang masih berada dalam toleransi hasil akhir. Tidak ditemukan korelasi kuat dengan ELA, metadata, atau deteksi AI untuk menyimpulkan manipulasi.';
                $result['results']['noise']['researcher_note'] = 'Noise diperlakukan sebagai sinyal pendukung dan telah diselaraskan dengan keputusan akhir analisis citra.';
            }

            $analysis = ForensicAnalysis::create([
                'user_id' => Auth::id(),
                'image_name' => $filename,
                's3_path' => $path,
                'ela_score' => $elaScore,
                'is_deepfake' => ($ganScore > 0.5),
                'metadata_details' => $result['results']['metadata'],
                'noise_status' => $result['results']['noise']['warnings'][0] ?? ($isNoiseInconsistent ? 'Inconsistent Noise Detected' : 'Normal'),
                'final_result' => [
                    'summary_label' => $statusLabel,
                    'summary_color' => $statusColor,
                    'full_report' => $result,
                ],
            ]);

            $this->reportService()->ensureReport($analysis);
            $analysis->refresh();

            if (! $request->expectsJson()) {
                return redirect()->route('user.result', $analysis->id)->with('success', 'Analisis Citra Selesai!');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Analisis citra selesai!',
                'data' => new ForensicResource($analysis),
                'visual_results' => [
                    'ela' => asset('storage/results/'.Auth::id().'/'.$result['results']['ela']['image_url']),
                    'noise' => asset('storage/results/'.Auth::id().'/'.$result['results']['noise']['image_url']),
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

        if ($analysis->s3_path && Storage::disk('public')->exists($analysis->s3_path)) {
            Storage::disk('public')->delete($analysis->s3_path);
        }

        $reportData = $analysis->final_result;
        if (isset($reportData['full_report']['results']['ela'])) {
            $elaFile = $reportData['full_report']['results']['ela']['image_url'] ?? null;
            $noiseFile = $reportData['full_report']['results']['noise']['image_url'] ?? null;

            if ($elaFile) {
                $elaPath = 'results/'.$analysis->user_id.'/'.$elaFile;
                if (Storage::disk('public')->exists($elaPath)) {
                    Storage::disk('public')->delete($elaPath);
                }
            }
            if ($noiseFile) {
                $noisePath = 'results/'.$analysis->user_id.'/'.$noiseFile;
                if (Storage::disk('public')->exists($noisePath)) {
                    Storage::disk('public')->delete($noisePath);
                }
            }
        }

        if ($analysis->report_pdf_path && Storage::disk('public')->exists($analysis->report_pdf_path)) {
            Storage::disk('public')->delete($analysis->report_pdf_path);
        }

        $analysis->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat audit berhasil dihapus bersih!',
            ]);
        }

        return redirect()->back()->with('success', 'Riwayat audit berhasil dihapus!');
    }

    public function downloadPdf($id)
    {
        // Melonggarkan batas waktu tunggu pemrosesan penyusunan PDF halaman berganda
        set_time_limit(180);

        try {
            $audit = $this->findAccessibleAudit($id);
            $reportPath = $this->reportService()->ensureReport($audit);

            if (! $reportPath) {
                if (request()->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Laporan PDF belum tersedia. Silakan coba unduh kembali beberapa saat lagi.',
                    ], 502);
                }

                return back()->with('error', 'Laporan PDF belum tersedia. Silakan coba unduh kembali beberapa saat lagi.');
            }

            return Storage::disk('public')->download(
                $reportPath,
                $this->reportService()->reportFileName($audit),
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
}
