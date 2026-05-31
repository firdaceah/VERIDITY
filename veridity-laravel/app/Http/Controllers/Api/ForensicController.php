<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ForensicAnalysis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class ForensicController extends Controller
{
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
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
            'data' => $analysis
        ], 201);
    }

    public function analyze(Request $request)
    {
        // Memberikan napas waktu 5 menit untuk komputasi teks intensif model AI Hugging Face via CPU
        set_time_limit(300); 

        // 1. Validasi Fleksibel: Menerima rumpun Gambar (Citra) ATAU Dokumen Teks
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,pdf,docx|max:15000',
        ]);

        try {
            $file = $request->file('image');
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = time() . '_' . $file->getClientOriginalName();

            // Simpan file asli ke dalam storage lokal public/uploads
            $path = $file->storeAs('uploads', $filename, 'public');
            $fullPathFile = storage_path('app/public/' . $path);

            // =========================================================================
            // PERCABANGAN KONDISI CABANG A: JIKA BERKAS ADALAH DOKUMEN TEKS (PDF / DOCX)
            // =========================================================================
            if (in_array($extension, ['pdf', 'docx'])) {

                // Ambil binary stream data file untuk ditembakkan langsung ke server FastAPI Python
                $fileBytes = file_get_contents($fullPathFile);

                // Panggil REST API Backend Python di Port 8001 dengan proteksi timeout 5 menit
                $response = Http::timeout(300)
                    ->attach('file', $fileBytes, $filename)
                    ->post('http://127.0.0.1:8001/analyze-document', [
                        'extension' => $extension
                    ]);

                if ($response->failed()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Layanan analisis dokumen forensik sedang tidak merespons. Silakan coba sesaat lagi.'
                    ], 500);
                }

                $result = $response->json();

                // Proteksi Fail-Safe untuk Output JSON Dokumen
                if (!isset($result['status']) || $result['status'] === 'error') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Analisis Dokumen Gagal: ' . ($result['message'] ?? 'Output data kosong')
                    ], 500);
                }

                // Ambil map klasifikasi teks secara aman dari output response engine Python
                $classificationMapData = $result['classification_map'] ?? $result['results'] ?? [];

                // Simpan Data Hasil Olahan Dokumen ke Oracle Database dengan aman masuk ke final_result
                $analysis = ForensicAnalysis::create([
                    'user_id'          => Auth::id(),
                    'image_name'       => $filename, // Berfungsi ganda menyimpan nama berkas dokumen
                    's3_path'          => $path,
                    'ela_score'        => 0, // Fallback nilai 0 karena dokumen tidak memiliki ELA
                    'is_deepfake'      => (($result['summary_color'] ?? '') === 'danger'),
                    'metadata_details' => [
                        'summary' => [
                            'status' => $result['summary_label'] ?? 'MIXED TEXT',
                            'verdict' => $result['summary_label'] ?? 'MIXED TEXT'
                        ]
                    ],
                    'noise_status'     => 'Not Applicable',
                    'final_result'     => [
                        'summary_label' => $result['summary_label'] ?? 'MIXED TEXT',
                        'summary_color' => $result['summary_color'] ?? 'warning',
                        'full_report'   => [
                            'final_score' => $result['final_score'] ?? 0,
                            'summary_label' => $result['summary_label'] ?? 'MIXED TEXT',
                            'summary_color' => $result['summary_color'] ?? 'warning',
                            'results' => $result['results'] ?? [],
                            'classification_map' => $classificationMapData
                        ]
                    ]
                ]);

                if (!$request->expectsJson()) {
                    return redirect()->route('user.result', $analysis->id)->with('success', 'Analisis Dokumen Selesai!');
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Analisis Dokumen Berhasil Selesai!',
                    'data' => $analysis
                ]);
            }

            // =========================================================================
            // PERCABANGAN KONDISI CABANG B: JIKA BERKAS ADALAH CITRA FOTO (LOGIKA LAMA)
            // =========================================================================
            $outputFolder = storage_path('app/public/results/' . Auth::id());
            if (!file_exists($outputFolder)) {
                mkdir($outputFolder, 0777, true);
            }

            $pythonPath = env('PYTHON_PATH');
            $scriptPath = env('PYTHON_TOOLKIT_SCRIPT');

            $command = "$pythonPath $scriptPath " . escapeshellarg($fullPathFile) . " " . escapeshellarg($outputFolder);
            $output = shell_exec($command);
            $result = json_decode($output, true);

            if (!$result || $result['status'] === 'error') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Analisis gambar gagal: ' . ($result['message'] ?? 'Output Python kosong')
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

            $analysis = ForensicAnalysis::create([
                'user_id'          => Auth::id(),
                'image_name'       => $filename,
                's3_path'          => $path,
                'ela_score'        => $elaScore,
                'is_deepfake'      => ($ganScore > 0.5),
                'metadata_details' => $result['results']['metadata'],
                'noise_status'     => $result['results']['noise']['warnings'][0] ?? ($isNoiseInconsistent ? 'Inconsistent Noise Detected' : 'Normal'),
                'final_result'     => [
                    'summary_label' => $statusLabel,
                    'summary_color' => $statusColor,
                    'full_report'   => $result
                ],
            ]);

            if (!$request->expectsJson()) {
                return redirect()->route('user.result', $analysis->id)->with('success', 'Analisis Citra Selesai!');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Analisis citra selesai!',
                'data' => $analysis,
                'visual_results' => [
                    'ela' => asset('storage/results/' . Auth::id() . '/' . $result['results']['ela']['image_url']),
                    'noise' => asset('storage/results/' . Auth::id() . '/' . $result['results']['noise']['image_url']),
                ]
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

    public function history()
    {
        $myAudits = ForensicAnalysis::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'data' => $myAudits
            ], 200);
        }

        return view('user.my-audits', compact('myAudits'));
    }

    public function destroy($id)
    {
        $analysis = ForensicAnalysis::findOrFail($id);
        $currentUser = Auth::user();

        $isOwner = (int) $analysis->user_id === (int) $currentUser->id;
        $isAdmin = isset($currentUser->role) && strtolower($currentUser->role) === 'admin';

        if (!$isOwner && !$isAdmin) {
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
                $elaPath = 'results/' . $analysis->user_id . '/' . $elaFile;
                if (Storage::disk('public')->exists($elaPath)) Storage::disk('public')->delete($elaPath);
            }
            if ($noiseFile) {
                $noisePath = 'results/' . $analysis->user_id . '/' . $noiseFile;
                if (Storage::disk('public')->exists($noisePath)) Storage::disk('public')->delete($noisePath);
            }
        }

        $analysis->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat audit berhasil dihapus bersih!'
            ]);
        }

        return redirect()->back()->with('success', 'Riwayat audit berhasil dihapus!');
    }

    public function downloadPdf($id)
    {
        // Melonggarkan batas waktu tunggu pemrosesan penyusunan PDF halaman berganda
        set_time_limit(180);

        try {
            // 1. Ambil data record audit murni dari database Oracle
            $audit = ForensicAnalysis::findOrFail($id);

            // 2. Ambil referensi nama file asli dari field database yang valid (image_name)
            $namaFile = $audit->image_name ?? $audit->s3_path ?? null;

            // Target folder utama tempat dokumen asli tersimpan
            $folderStorage = storage_path('app/public/uploads/');

            if (!file_exists($folderStorage)) {
                $folderStorage = storage_path('app/public/forensics/');
            }

            $pdfPath = $folderStorage . $namaFile;

            // Jalur Pengaman Alternatif Cepat
            if (!$namaFile || !file_exists($pdfPath)) {
                $semuaFile = glob($folderStorage . "*.pdf");

                if (!empty($semuaFile)) {
                    $pdfPath = $semuaFile[0];
                } else {
                    return back()->with('warning', 'Berkas fisik dokumen pemeriksaan belum tersedia di server local storage. Silakan lakukan pemeriksaan ulang.');
                }
            }

            // 3. SEKSION PEMBONGKARAN JSON BERTIINGKAT KHUSUS DARI KOLOM final_result
            $finalResultData = $audit->final_result;

            // Jika properti di model belum di-cast array murni (masih berbentuk string JSON), lakukan decode
            if (is_string($finalResultData)) {
                $finalResultData = json_decode($finalResultData, true);
            }

            // Selam ke dalam struktur array bertingkat: final_result -> full_report -> classification_map
            $classificationMap = $finalResultData['full_report']['classification_map'] ?? [];
            
            // Ambil juga summary_label secara presisi dari dalam JSON final_result
            $summaryLabel = $finalResultData['summary_label'] ?? 'MIXED TEXT';

            // 4. Lempar data parameter secara lengkap ke REST API Python Engine Port 8001
            $response = Http::timeout(120)->attach(
                'file',
                file_get_contents($pdfPath),
                'document.pdf'
            )->post('http://127.0.0.1:8001/generate-pdf-report', [
                'classification_map_str' => json_encode($classificationMap),
                'summary_label'          => $summaryLabel,
                'audit_id'               => $audit->id,
                'analyzed_at'            => $audit->created_at->setTimezone('Asia/Jakarta')->format('d M Y, H:i') . ' WIB'
            ]);

            if ($response->failed()) {
                return back()->with('error', 'Gagal terhubung dengan Layanan Analisis Forensik (Python Engine). Pastikan service gateway aktif.');
            }

            // 5. Sambas data stream binary PDF dari FastAPI dan luncurkan ke browser client
            $pdfContent = $response->body();
            $fileName = "REPORT_VERIDITY_#VRD-" . $audit->id . ".pdf";

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            \Log::error("Gagal mengunduh laporan PDF: " . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan internal sistem saat memproses layout laporan cetak.');
        }
    }
}