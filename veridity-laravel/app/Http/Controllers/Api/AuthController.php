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
        // 1. Hitung total seluruh audit gambar di database
        $totalAudit = \App\Models\ForensicAnalysis::count();

        // 2. Hitung total pengguna terdaftar (selain admin)
        $totalUser = \App\Models\User::where('role', '!=', 'admin')->count();

        // 3. Ambil data dengan Eager Loading 'user' dan urutkan dari yang terbaru (Limit 5 untuk Live Traffic)
        // Kita ambil data traffic-nya dulu dari DB dengan struktur query builder yang benar
        $recentAudits = \App\Models\ForensicAnalysis::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 4. AMANKAN FILTER FRAUD TERDETEKSI (SOLUSI ORACLE ORA-00932)
        // Untuk menghitung total fraud, kita ambil semua data forensik untuk difilter di level Collection
        $allAudits = \App\Models\ForensicAnalysis::all();

        $fraudCount = $allAudits->filter(function ($audit) {
            // Membaca array/JSON final_result dengan aman
            $color = $audit->final_result['summary_color'] ?? '';
            return $color === 'warning' || $color === 'danger';
        })->count();

        // 5. Lempar semua variabel ke view Blade Admin
        return view('admin.dashboard', compact('totalAudit', 'totalUser', 'fraudCount', 'recentAudits'));
    }

    public function auditLogs()
    {
        $logs = \App\Models\ForensicAnalysis::with('user')->latest()->paginate(10);
        return view('admin.audit-logs', compact('logs'));
    }

    public function blacklist()
    {
        // FIX ORACLE CLOB BLACKLIST: Menggunakan Collection Filter untuk memisahkan data Bahaya/Fraud
        // 1. Ambil semua data analisis lengkap dengan relasi user, diurutkan dari yang terbaru
        $allAnalyses = \App\Models\ForensicAnalysis::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Filter data di level memory PHP (Mencari data yang summary_color-nya bernilai 'danger')
        $filteredFraud = $allAnalyses->filter(function ($analysis) {
            $color = $analysis->final_result['summary_color'] ?? '';
            return $color === 'danger';
        });

        // 3. Buat Manual Pagination agar fungsi ->links() dan ->hasPages() di view blacklist.blade.php tidak error
        $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $currentItems = $filteredFraud->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $fraudCases = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $filteredFraud->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath()]
        );

        // 4. Kirim data fraudCases yang sudah aman dari ORA-00932 ke view blacklist
        return view('admin.blacklist', compact('fraudCases'));
    }

    public function userDashboard()
    {
        // Arahkan admin ke dashboard admin, user biasa ke dashboard user
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
        return view('user.dashboard');
    }

    public function myAudits()
    {
        $myAudits = \App\Models\ForensicAnalysis::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('user.my-audits', compact('myAudits'));
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
