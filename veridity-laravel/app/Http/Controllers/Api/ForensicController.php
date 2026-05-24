<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ForensicAnalysis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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
        set_time_limit(300); // Memberikan waktu ekstra untuk pemrosesan citra di tingkat Python
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10000',
        ]);

        try {
            // 1. Simpan File Foto Orisinal ke Storage Local
            $imageFile = $request->file('image');
            $filename = time() . '_' . $imageFile->getClientOriginalName();
            $path = $imageFile->storeAs('uploads', $filename, 'public');

            $fullPathFoto = storage_path('app/public/' . $path);
            $outputFolder = storage_path('app/public/results/' . Auth::id());

            if (!file_exists($outputFolder)) {
                mkdir($outputFolder, 0777, true);
            }

            // 2. Eksekusi Engine Toolkit Forensik Python via CLI Shell
            $pythonPath = env('PYTHON_PATH');
            $scriptPath = env('PYTHON_TOOLKIT_SCRIPT');

            $command = "$pythonPath $scriptPath " . escapeshellarg($fullPathFoto) . " " . escapeshellarg($outputFolder);
            $output = shell_exec($command);
            $result = json_decode($output, true);

            // Proteksi fail-safe jika script Python terputus atau gagal dumping JSON
            if (!$result || $result['status'] === 'error') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Analisis gagal: ' . ($result['message'] ?? 'Output Python kosong')
                ], 500);
            }

            // 3. Ekstraksi Metrik Multidimensi Hasil Analisis Python
            $elaScore = $result['results']['ela']['metrics']['anomaly_score'] ?? 0;
            $ganScore = $result['results']['ai_detection']['metrics']['gan_score'] ?? 0;
            $metaVerdict = $result['results']['metadata']['summary']['verdict'] ?? 'UNKNOWN';
            $noiseInterpretation = $result['results']['noise']['interpretation'] ?? '';
            $finalScore = $result['final_score'] ?? 0;

            // Flags indikasi anomali teks lokal dan metadata berkas
            $isNoiseInconsistent = str_contains($noiseInterpretation, 'tidak rata') ||
                str_contains($noiseInterpretation, 'keanehan');

            $isMetaManipulated = (
                $metaVerdict === 'REKAYASA DIGITAL / EDITING' ||
                $metaVerdict === 'REKAYASA DIGITAL / GENERATOR AI (SANGAT BERBAHAYA)' ||
                str_contains($metaVerdict, 'EDITING')
            );

            // 4. SISTEM INTERSEPTOR HIERARKI STATUS (ANTI-BENTROK METODE)
            $statusLabel = 'FOTO ASLI / JEPRETAN MURNI';
            $statusColor = 'success';

            // KONDISI UTAMA: Proteksi Overriding untuk Ancaman Rekayasa Generator AI / Deepfake
            if ($ganScore > 0.5 || ($result['verdict'] ?? '') === 'DEEPFAKE / AI GENERATED') {
                $statusLabel = 'SANGAT BERBAHAYA (DEEPFAKE AI)';
                $statusColor = 'danger';
            }
            // KONDISI KEDUA: Filter Pengunci untuk Gambar Bersih dengan Skor ELA Mendekati Nol
            elseif ($elaScore <= 5.0 && $ganScore <= 0.4) {
                $statusLabel = 'FOTO ASLI / JEPRETAN MURNI';
                $statusColor = 'success';
            }
            // KONDISI KETIGA: Deteksi Kerusakan Integritas Piksel / Splicing Ekstrem
            elseif ($finalScore < 45 || $elaScore > 45 || $ganScore > 0.85 || ($result['verdict'] ?? '') === 'MANIPULATED') {
                $statusLabel = 'SANGAT BERBAHAYA';
                $statusColor = 'danger';
            }
            // KONDISI KEEMPAT: Deteksi Ancaman Modifikasi Lokal / Editing Minor (Oranye)
            elseif (
                $elaScore > 15 ||
                $ganScore > 0.4 ||
                ($isNoiseInconsistent && $elaScore > 8.0) ||
                $isMetaManipulated
            ) {
                $statusLabel = 'MENCURIGAKAN (TERINDIKASI REKAYASA)';
                $statusColor = 'warning';
            }

            // 5. Konsolidasi Penyimpanan Data Audit Lengkap ke Oracle Database
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
                    'full_report'   => $result // Dumping seluruh nested JSON untuk detail panel peneliti
                ],
            ]);

            // 6. Router Response Output (Web Redirect vs API AJAX/Flutter)
            if (!$request->expectsJson()) {
                return redirect()->route('user.result', $analysis->id)->with('success', 'Analisis Selesai!');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Analisis selesai!',
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
        // 1. Ambil data analisis berdasarkan ID, proteksi fail-safe jika tidak ditemukan
        $analysis = ForensicAnalysis::findOrFail($id);

        // 2. AMANKAN LOGIKA HAK AKSES (Mencegah salah baca properti model)
        $currentUser = Auth::user();

        // Cek apakah user yang login adalah pemilik foto ATAU merupakan seorang Admin
        $isOwner = (int) $analysis->user_id === (int) $currentUser->id;
        $isAdmin = isset($currentUser->role) && strtolower($currentUser->role) === 'admin';

        if (!$isOwner && !$isAdmin) {
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kamu tidak punya akses untuk menghapus data ini!'
                ], 403);
            }
            return redirect()->back()->with('error', 'Kamu tidak punya akses untuk menghapus data ini!');
        }

        // 3. HAPUS FILE FISIK FOTO ORISINAL (UPLOADS)
        if ($analysis->s3_path && Storage::disk('public')->exists($analysis->s3_path)) {
            Storage::disk('public')->delete($analysis->s3_path);
        }

        // 4. HAPUS FILE HASIL TOOLKIT PYTHON (ELA & NOISE MAP)
        // Mengambil nama file unik ELA dari kolom JSON final_result di database
        $reportData = $analysis->final_result;
        if (isset($reportData['full_report']['results'])) {
            $elaFile = $reportData['full_report']['results']['ela']['image_url'] ?? null;
            $noiseFile = $reportData['full_report']['results']['noise']['image_url'] ?? null;

            // Hapus file ELA map dari folder results/user_id/
            if ($elaFile) {
                $elaPath = 'results/' . $analysis->user_id . '/' . $elaFile;
                if (Storage::disk('public')->exists($elaPath)) {
                    Storage::disk('public')->delete($elaPath);
                }
            }

            // Hapus file Noise map dari folder results/user_id/
            if ($noiseFile) {
                $noisePath = 'results/' . $analysis->user_id . '/' . $noiseFile;
                if (Storage::disk('public')->exists($noisePath)) {
                    Storage::disk('public')->delete($noisePath);
                }
            }
        }

        // 5. HAPUS DATA DARI DATABASE
        $analysis->delete();

        // 6. RESPONSE OUTPUT SINKRON
        if (request()->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat audit dan berkas citra berhasil dihapus bersih!'
            ]);
        }

        return redirect()->back()->with('success', 'Riwayat audit berhasil dihapus!');
    }
}
