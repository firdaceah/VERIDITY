<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForensicController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/audits', [ForensicController::class, 'analyze']);
    Route::get('/audits', [ForensicController::class, 'history']);
    Route::get('/audits/{id}', [ForensicController::class, 'show']);
    Route::delete('/audits/{id}', [ForensicController::class, 'destroy']);
    Route::get('/audits/{id}/report', [ForensicController::class, 'downloadPdf']);

    Route::post('/analyze', [ForensicController::class, 'analyze']);
    Route::get('/history', [ForensicController::class, 'history']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/photo', [AuthController::class, 'updateProfilePhoto']);
    Route::post('/profile/password', [AuthController::class, 'updatePassword']);
});
