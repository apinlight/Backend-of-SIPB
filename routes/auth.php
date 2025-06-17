<?php
// routes/auth.php

use App\Http\Controllers\Api\Auth\TokenAuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\Auth\NewPasswordController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;

// // 1) Bootstrap CSRF cookie for SPA
// Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show'])
//      ->name('sanctum.csrf-cookie');

// 2) Guest-only routes
Route::prefix('api')->group(function () {
    Route::post('/register', [TokenAuthController::class, 'register'])->name('register');
    Route::post('/login', [TokenAuthController::class, 'login'])->name('login');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
         ->name('password.email');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
         ->name('password.update');

    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
         ->middleware(['signed', 'throttle:6,1'])
         ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
         ->middleware('throttle:6,1')
         ->name('verification.send');
});

// // Options for CORS
// Route::options('/api/login', fn() => response()->json([], 204));
// Route::options('/api/register', fn() => response()->json([], 204));
// Route::options('/api/forgot-password', fn() => response()->json([], 204));
// Route::options('/api/reset-password', fn() => response()->json([], 204));
