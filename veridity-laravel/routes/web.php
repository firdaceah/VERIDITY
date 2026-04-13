<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForensicController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth'])->group(function () {
    // Route::get('/dashboard', [AuthController::class, 'userDashboard'])->name('user.dashboard');

    Route::get('/dashboard', function () {
        return view('user.dashboard');
    })->name('user.dashboard');

    Route::get('/history', [ForensicController::class, 'history'])->name('user.history');

    // Rute untuk proses tombol "Mulai Analisis" di Web Dashboard
    // Ini yang memanggil script Python kamu
    Route::post('/audit/analyze', [ForensicController::class, 'analyze'])->name('audit.analyze');

    // Rute untuk API Upload (jika kamu butuh akses upload lewat Postman/Mobile)
    Route::post('/audit/upload', [ForensicController::class, 'uploadImage'])->name('audit.upload');

    // Rute untuk melihat riwayat audit milik user sendiri
    // Route::get('/my-audits', [ForensicController::class, 'history'])->name('user.dashboard');

    Route::get('/audit/result/{id}', [ForensicController::class, 'showResult'])->name('user.result');

    // Rute untuk menghapus riwayat audit
    // Route::delete('/audit/{id}', [ForensicController::class, 'destroy'])->name('audit.destroy');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {

    Route::get('/dashboard', [AuthController::class, 'adminDashboard'])->name('admin.dashboard');

    Route::get('/audit-logs', [AuthController::class, 'auditLogs'])->name('admin.audit-logs');

    Route::get('/audit/{id}', [AuthController::class, 'showAudit'])->name('admin.audit.show');

    Route::get('/admin/blacklist', [AuthController::class, 'blacklist'])->name('admin.blacklist');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
