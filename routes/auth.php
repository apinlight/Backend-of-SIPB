<?php
// routes/auth.php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\Auth\NewPasswordController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;
use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\LogoutController;

Route::middleware('web')->group(function () {
// 1) Bootstrap CSRF cookie for SPA
Route::get('/sanctum/csrf-cookie', [\Laravel\Sanctum\Http\Controllers\CsrfCookieController::class, 'show'])
     ->name('sanctum.csrf-cookie');

Route::post('api/logout', [LogoutController::class, 'destroy'])
     ->name('api.logout');

// Handle OPTIONS preflight request for logout specifically
Route::options('api/logout', function () {
    return response()->json([], 200);
});    

// 2) Guest-only routes
Route::prefix('api')->middleware(['web', 'guest'])->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register');
    Route::post('/login', [LoginController::class, 'store'])->name('login');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
         ->name('password.email');
    Route::post('/reset-password', [NewPasswordController::class,     'store'])
         ->name('password.update');

    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
         ->middleware(['signed', 'throttle:6,1'])
         ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
         ->middleware('throttle:6,1')
         ->name('verification.send');
});

// Options for CORS
Route::options('/api/login', fn() => response()->json([], 204));

});

// // 3) Authenticated route for logout
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');
// });

// Route::options('/logout', fn() => response()->json([], 204));