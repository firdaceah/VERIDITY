<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForensicAnalysis;
use App\Models\User;
use App\Services\EvidenceStorage;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    private const MESSAGES = [
        'login_failed' => [
            'en' => 'Login failed. Check your email and password.',
            'id' => 'Login gagal. Periksa email dan password.',
        ],
        'login_success' => [
            'en' => 'Login successful!',
            'id' => 'Login berhasil!',
        ],
        'logout_success' => [
            'en' => 'Logged out successfully.',
            'id' => 'Berhasil logout, token telah dihapus.',
        ],
        'profile_updated' => [
            'en' => 'Profile updated successfully.',
            'id' => 'Profil berhasil diperbarui.',
        ],
        'photo_updated' => [
            'en' => 'Profile photo updated successfully.',
            'id' => 'Foto profil berhasil diperbarui.',
        ],
        'photo_invalid' => [
            'en' => 'Profile photo must be a JPG, JPEG, or PNG file.',
            'id' => 'Foto profil harus berupa file JPG, JPEG, atau PNG.',
        ],
        'password_current_invalid' => [
            'en' => 'Current password is incorrect.',
            'id' => 'Password lama tidak sesuai.',
        ],
        'password_updated' => [
            'en' => 'Password updated successfully.',
            'id' => 'Password berhasil diperbarui.',
        ],
        'reset_created' => [
            'en' => 'Reset token created.',
            'id' => 'Token reset password berhasil dibuat.',
        ],
        'reset_invalid' => [
            'en' => 'The reset token is invalid.',
            'id' => 'Token reset password tidak valid.',
        ],
        'reset_expired' => [
            'en' => 'The reset token has expired.',
            'id' => 'Token reset password sudah kedaluwarsa.',
        ],
        'reset_success' => [
            'en' => 'Password reset successfully. Please log in again.',
            'id' => 'Password berhasil direset. Silakan login kembali.',
        ],
    ];

    private function normalizeLanguage(?string $language): string
    {
        return strtolower((string) $language) === 'id' ? 'id' : 'en';
    }

    private function message(string $key, ?string $language): string
    {
        $locale = $this->normalizeLanguage($language);

        return self::MESSAGES[$key][$locale] ?? self::MESSAGES[$key]['en'] ?? $key;
    }

    private function evidenceStorage(): EvidenceStorage
    {
        return app(EvidenceStorage::class);
    }

    private function tokenResponse(User $user, string $tokenName, string $message, int $statusCode = 200)
    {
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $user,
            'user' => $user,
            'access_token' => $token,
            'token' => $token,
            'token_type' => 'Bearer',
        ], $statusCode);
    }

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

        if (! $request->expectsJson()) {
            return redirect()->route('login')->with('success', 'Akun berhasil dibuat! Silakan masuk dengan email Anda.');
        }

        return $this->tokenResponse($user, 'auth_token', 'Registrasi berhasil!', 201);
    }

    public function login(Request $request)
    {
        $language = $this->normalizeLanguage($request->input('language'));
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'language' => 'nullable|string|in:en,id',
        ]);
        unset($credentials['language']);

        if (! auth()->attempt($credentials)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $this->message('login_failed', $language),
                ], 401);
            }

            return back()->withErrors(['email' => 'Email atau password salah!'])->withInput();
        }

        $user = auth()->user();

        if (! $request->expectsJson()) {
            $request->session()->regenerate();

            if ($user->role === 'admin') {
                return redirect()->intended('/admin/dashboard');
            }

            return redirect()->intended('/dashboard');
        }

        return $this->tokenResponse($user, 'veridity_token', $this->message('login_success', $language));
    }

    public function logout(Request $request)
    {
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => $this->message('logout_success', $request->input('language')),
            ]);
        }

        // 2. Logika untuk Web (Menghapus Session & Logout Guard)
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 3. Response Berdasarkan Jenis Request
        // Jika diakses dari Browser/Web Admin, arahkan ke login dengan pesan
        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }

    public function profile(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user(),
        ]);
    }

    public function webProfile(Request $request)
    {
        return view('user.profile', ['user' => $request->user()]);
    }

    public function webUpdateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'photo' => ['nullable', 'file', 'max:4096'],
        ]);

        unset($validated['photo']);
        $user->update($validated);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $extension = strtolower($file->getClientOriginalExtension());

            if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                return back()->withErrors(['photo' => 'Foto profil harus berupa JPG, JPEG, atau PNG.']);
            }

            $this->evidenceStorage()->delete($user->profile_photo_path);

            $path = $this->evidenceStorage()->makePath('profile-photos', $user->id, $file->getClientOriginalName());
            $this->evidenceStorage()->putLocalFile($path, $file->getRealPath());
            $user->update(['profile_photo_path' => $path]);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function webForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function webSendResetToken(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $message = 'Token reset password berhasil dibuat.';

        return redirect()
            ->route('password.reset.form')
            ->with('success', $message)
            ->with('reset_email', $validated['email'])
            ->with('dev_reset_token', $token);
    }

    public function webResetPasswordForm()
    {
        return view('auth.reset-password');
    }

    public function webResetPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (! $record || ! Hash::check($validated['token'], $record->token)) {
            return back()->withErrors(['token' => 'Token reset password tidak valid.'])->withInput();
        }

        if ($record->created_at && now()->diffInMinutes($record->created_at) > 60) {
            return back()->withErrors(['token' => 'Token reset password sudah kedaluwarsa.'])->withInput();
        }

        User::where('email', $validated['email'])->update([
            'password' => Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return redirect()->route('login')->with('success', 'Password berhasil direset. Silakan login kembali.');
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => $this->message('profile_updated', $request->input('language')),
            'data' => $user->fresh(),
        ]);
    }

    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|file|max:4096',
        ]);

        $user = $request->user();
        $file = $request->file('photo');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->message('photo_invalid', $request->input('language')),
            ], 422);
        }

        $this->evidenceStorage()->delete($user->profile_photo_path);

        $path = $this->evidenceStorage()->makePath('profile-photos', $user->id, $file->getClientOriginalName());
        $this->evidenceStorage()->putLocalFile($path, $file->getRealPath());
        $user->update(['profile_photo_path' => $path]);

        return response()->json([
            'status' => 'success',
            'message' => $this->message('photo_updated', $request->input('language')),
            'data' => $user->fresh(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->message('password_current_invalid', $request->input('language')),
            ], 422);
        }

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json([
            'status' => 'success',
            'message' => $this->message('password_updated', $request->input('language')),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $language = $this->normalizeLanguage($request->input('language'));
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'language' => ['nullable', 'string', 'in:en,id'],
        ]);
        unset($validated['language']);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $validated['email']],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $response = [
            'status' => 'success',
            'message' => $this->message('reset_created', $language),
            'dev_reset_token' => $token,
        ];

        try {
            $resetUrl = url('/reset-password');
            $emailBody = $language === 'id'
                ? "Token reset password VERIDITY Anda:\n\n{$token}\n\nBuka {$resetUrl} untuk mengatur password baru."
                : "Your VERIDITY password reset token:\n\n{$token}\n\nOpen {$resetUrl} to set a new password.";

            Mail::raw($emailBody, function ($message) use ($validated, $language) {
                $message
                    ->to($validated['email'])
                    ->subject($language === 'id' ? 'Token Reset Password VERIDITY' : 'VERIDITY Password Reset Token');
            });
        } catch (\Throwable $e) {
            Log::warning('Failed to send password reset email', [
                'email' => $validated['email'],
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json($response);
    }

    public function resetPassword(Request $request)
    {
        $language = $this->normalizeLanguage($request->input('language'));
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'language' => ['nullable', 'string', 'in:en,id'],
        ]);
        unset($validated['language']);

        $record = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();

        if (! $record || ! Hash::check($validated['token'], $record->token)) {
            return response()->json([
                'status' => 'error',
                'message' => $this->message('reset_invalid', $language),
            ], 422);
        }

        if ($record->created_at && now()->diffInMinutes($record->created_at) > 60) {
            return response()->json([
                'status' => 'error',
                'message' => $this->message('reset_expired', $language),
            ], 422);
        }

        User::where('email', $validated['email'])->update([
            'password' => Hash::make($validated['password']),
        ]);

        DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

        return response()->json([
            'status' => 'success',
            'message' => $this->message('reset_success', $language),
        ]);
    }

    public function adminDashboard()
    {
        // 1. Hitung total seluruh audit gambar di database
        $totalAudit = ForensicAnalysis::count();

        // 2. Hitung total pengguna terdaftar (selain admin)
        $totalUser = User::where('role', '!=', 'admin')->count();

        // 3. Ambil data dengan Eager Loading 'user' dan urutkan dari yang terbaru (Limit 5 untuk Live Traffic)
        // Kita ambil data traffic-nya dulu dari DB dengan struktur query builder yang benar
        $recentAudits = ForensicAnalysis::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 4. AMANKAN FILTER FRAUD TERDETEKSI (SOLUSI ORACLE ORA-00932)
        // Untuk menghitung total fraud, kita ambil semua data forensik untuk difilter di level Collection
        $allAudits = ForensicAnalysis::all();

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
        $logs = ForensicAnalysis::with('user')->latest()->paginate(10);

        return view('admin.audit-logs', compact('logs'));
    }

    public function blacklist()
    {
        // FIX ORACLE CLOB BLACKLIST: Menggunakan Collection Filter untuk memisahkan data Bahaya/Fraud
        // 1. Ambil semua data analisis lengkap dengan relasi user, diurutkan dari yang terbaru
        $allAnalyses = ForensicAnalysis::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        // 2. Filter data di level memory PHP (Mencari data yang summary_color-nya bernilai 'danger')
        $filteredFraud = $allAnalyses->filter(function ($analysis) {
            $color = $analysis->final_result['summary_color'] ?? '';

            return $color === 'danger';
        });

        // 3. Buat Manual Pagination agar fungsi ->links() dan ->hasPages() di view blacklist.blade.php tidak error
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 10;
        $currentItems = $filteredFraud->slice(($currentPage - 1) * $perPage, $perPage)->all();

        $fraudCases = new LengthAwarePaginator(
            $currentItems,
            $filteredFraud->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
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
        $myAudits = ForensicAnalysis::where('user_id', auth()->id())
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
        $analysis = ForensicAnalysis::where('user_id', auth()->id())
            ->findOrFail($id);

        return view('user.result', compact('analysis'));
    }
}
