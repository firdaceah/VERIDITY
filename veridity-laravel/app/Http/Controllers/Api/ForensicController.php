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

    public function getHistory()
    {
        $history = ForensicAnalysis::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:10000',
        ]);

        try {
            $imageFile = $request->file('image');
            $filename = time() . '_' . $imageFile->getClientOriginalName();
            $path = $imageFile->storeAs('uploads', $filename, 'public');

            $fullPathFoto = storage_path('app/public/' . $path);

            $outputFolder = storage_path('app/public/results/' . Auth::id());
            if (!file_exists($outputFolder)) {
                mkdir($outputFolder, 0777, true);
            }

            $pythonPath = env('PYTHON_PATH');
            $scriptPath = env('PYTHON_TOOLKIT_SCRIPT');

            $command = "$pythonPath $scriptPath " . escapeshellarg($fullPathFoto) . " " . escapeshellarg($outputFolder);
            $output = shell_exec($command);
            $result = json_decode($output, true);

            if (!$result || $result['status'] === 'error') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Analisis gagal: ' . ($result['message'] ?? 'Output Python kosong')
                ], 500);
            }

            $skor = $result['final_score'];
            $ganScore = $result['results']['ai_detection']['gan_score'] ?? 0;
            $hasMetadataWarnings = !empty($result['results']['metadata']['warnings']);

            $statusLabel = 'Aman'; 
            $statusColor = 'success'; 

            if ($skor < 40 || $ganScore > 0.8) {
                $statusLabel = 'Sangat Berbahaya';
                $statusColor = 'danger';
            } elseif ($skor < 70 || $ganScore > 0.5 || $hasMetadataWarnings) {
                $statusLabel = 'Mencurigakan / Warning';
                $statusColor = 'warning';
            }

            $analysis = ForensicAnalysis::create([
                'user_id'          => Auth::id(),
                'image_name'       => $filename,
                's3_path'          => $path,
                'ela_score'        => $result['results']['ela']['metrics']['anomaly_score'] ?? 0,
                'is_deepfake'      => ($ganScore > 0.7),
                'metadata_details' => $result['results']['metadata'],
                'noise_status'     => $result['results']['noise']['warnings'][0] ?? 'Normal',
                'final_result'     => [
                    'summary_label' => $statusLabel,
                    'summary_color' => $statusColor,
                    'full_report'   => $result
                ],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Analisis selesai!',
                'data' => $analysis,
                'visual_results' => [
                    'ela' => asset('storage/results/' . Auth::id() . '/ela_result.jpg'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function history()
    {
        $history = ForensicAnalysis::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $history
        ]);
    }
}
