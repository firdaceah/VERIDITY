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
        set_time_limit(300); // Memberikan waktu lebih lama untuk proses Python
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10000',
        ]);

        try {
            // 1. Simpan Foto Asli
            $imageFile = $request->file('image');
            $filename = time() . '_' . $imageFile->getClientOriginalName();
            $path = $imageFile->storeAs('uploads', $filename, 'public');

            $fullPathFoto = storage_path('app/public/' . $path);
            $outputFolder = storage_path('app/public/results/' . Auth::id());

            if (!file_exists($outputFolder)) {
                mkdir($outputFolder, 0777, true);
            }

            // 2. Jalankan Skrip Python
            $pythonPath = env('PYTHON_PATH');
            $scriptPath = env('PYTHON_TOOLKIT_SCRIPT');

            $command = "$pythonPath $scriptPath " . escapeshellarg($fullPathFoto) . " " . escapeshellarg($outputFolder);
            $output = shell_exec($command);
            $result = json_decode($output, true);

            // Proteksi jika Python error
            if (!$result || $result['status'] === 'error') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Analisis gagal: ' . ($result['message'] ?? 'Output Python kosong')
                ], 500);
            }

            // 3. Ambil Data Hasil Analisis
            $elaScore = $result['results']['ela']['metrics']['anomaly_score'] ?? 0;
            $ganScore = $result['results']['ai_detection']['gan_score'] ?? 0;
            $metaVerdict = $result['results']['metadata']['summary']['verdict'] ?? 'UNKNOWN';
            $noiseInterpretation = strtolower($result['results']['noise']['interpretation'] ?? '');
            $hasMetadataWarnings = !empty($result['results']['metadata']['warnings']);
            $finalScore = $result['final_score'] ?? 0;

            // Cek anomali pada noise
            $isNoiseInconsistent = str_contains($noiseInterpretation, 'inconsistent') ||
                str_contains($noiseInterpretation, 'tampering');

            // 4. Logika Penentuan Status Terintegrasi (Tuning)
            $statusLabel = 'Aman';
            $statusColor = 'success';

            if ($finalScore < 45 || $elaScore > 45 || $ganScore > 0.85) {
                // KONDISI: SANGAT BERBAHAYA
                $statusLabel = 'Sangat Berbahaya';
                $statusColor = 'danger';
            } elseif (
                $elaScore > 20 || 
                $ganScore > 0.5 ||
                ($isNoiseInconsistent && $elaScore > 15) || 
                $metaVerdict == 'LIKELY MANIPULATED'
            ) {
                // KONDISI: MENCURIGAKAN (WARNING)
                $statusLabel = 'Mencurigakan / Warning';
                $statusColor = 'warning';
            }

            // 5. Simpan ke Database
            $analysis = ForensicAnalysis::create([
                'user_id'          => Auth::id(),
                'image_name'       => $filename,
                's3_path'          => $path,
                'ela_score'        => $elaScore,
                'is_deepfake'      => ($ganScore > 0.7),
                'metadata_details' => $result['results']['metadata'],
                'noise_status'     => $result['results']['noise']['warnings'][0] ?? ($isNoiseInconsistent ? 'Inconsistent Noise Detected' : 'Normal'),
                'final_result'     => [
                    'summary_label' => $statusLabel,
                    'summary_color' => $statusColor,
                    'full_report'   => $result
                ],
            ]);

            // 6. Response (Redirect untuk Web, JSON untuk API/AJAX)
            if (!$request->expectsJson()) {
                return redirect()->route('user.result', $analysis->id)->with('success', 'Analisis Selesai!');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Analisis selesai!',
                'data' => $analysis,
                'visual_results' => [
                    'ela' => asset('storage/results/' . Auth::id() . '/ela_result.jpg'),
                    'noise' => asset('storage/results/' . Auth::id() . '/temp_noise_map.png'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function showResult($id)
    {
        // Ambil data analisis berdasarkan ID, pastikan milik user yang login
        $analysis = ForensicAnalysis::where('user_id', Auth::id())->findOrFail($id);

        // Lempar ke view hasil yang sudah kita buat sebelumnya
        return view('user.result', compact('analysis'));
    }

    public function history()
    {
        $myAudits = ForensicAnalysis::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('user.history', compact('myAudits'));
    }

    public function destroy($id)
    {
        $analysis = ForensicAnalysis::findOrFail($id);

        // Proteksi: Jika bukan Admin DAN bukan pemilik data, maka dilarang hapus
        if (Auth::user()->role !== 'admin' && $analysis->user_id !== Auth::id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak punya akses untuk menghapus data ini!'
            ], 403);
        }

        // Hapus file fisik dari storage agar tidak memenuhi hosting/laptop
        if (Storage::disk('public')->exists($analysis->s3_path)) {
            Storage::disk('public')->delete($analysis->s3_path);
        }

        // Hapus data dari database
        $analysis->delete();

        if (request()->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Riwayat berhasil dihapus']);
        }

        return redirect()->back()->with('success', 'Riwayat berhasil dihapus');
    }
}
