<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForensicAnalysis;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        if (!$request->expectsJson()) {
            return redirect()->route('login')->with('success', 'Akun berhasil dibuat! Silakan masuk dengan email Anda.');
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Registrasi berhasil!',
            'data' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!auth()->attempt($credentials)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Login Gagal'], 401);
            }
            return back()->withErrors(['email' => 'Email atau password salah!'])->withInput();
        }

        $user = auth()->user();

        if (!$request->expectsJson()) {
            $request->session()->regenerate();

            if ($user->role === 'admin') {
                return redirect()->intended('/admin/dashboard');
            }
            return redirect()->intended('/dashboard');
        }

        $token = $user->createToken('veridity_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        // 2. Logika untuk Web (Menghapus Session & Logout Guard)
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 3. Response Berdasarkan Jenis Request
        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil logout, token telah dihapus.'
            ]);
        }

        // Jika diakses dari Browser/Web Admin, arahkan ke login dengan pesan
        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ]);
    }

    public function adminDashboard()
    {
        $totalAudit = ForensicAnalysis::count();
        $totalUser = User::where('role', 'user')->count();

        $fraudCount = ForensicAnalysis::whereIn('final_result', ['Bahaya', 'Mencurigakan'])->count();

        $recentAudits = ForensicAnalysis::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('totalUser', 'totalAudit', 'fraudCount', 'recentAudits'));
    }

    public function auditLogs()
    {
        $logs = \App\Models\ForensicAnalysis::with('user')->latest()->paginate(10);
        return view('admin.audit-logs', compact('logs'));
    }

    public function blacklist()
    {
        // Mengambil data audit yang hasilnya 'Bahaya' 
        // Kita tampilkan detail fotonya, bukan menyalahkan user yang upload
        $fraudCases = \App\Models\ForensicAnalysis::with('user')
            ->where('final_result', 'Bahaya')
            ->latest()
            ->paginate(10);

        return view('admin.blacklist', compact('fraudCases'));
    }

    // public function userDashboard()
    // {
    //     // Arahkan admin ke dashboard admin, user biasa ke dashboard user
    //     if (auth()->user()->role === 'admin') {
    //         return redirect()->route('admin.dashboard');
    //     }
    //     return view('user.dashboard');
    // }

    public function myAudits()
    {
        $myAudits = \App\Models\ForensicAnalysis::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('user.dashboard', compact('myAudits'));
    }

    public function showAudit($id)
    {
        $audit = ForensicAnalysis::with('user')->findOrFail($id);
        return view('admin.audit-detail', compact('audit'));
    }

    public function showResult($id)
    {
        // Mengambil data audit spesifik berdasarkan ID
        $analysis = \App\Models\ForensicAnalysis::where('user_id', auth()->id())
            ->findOrFail($id);

        return view('user.result', compact('analysis'));
    }
}
