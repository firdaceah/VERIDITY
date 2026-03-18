<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ForensicAnalysis;

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
}
