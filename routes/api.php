<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\NewPasswordController;
use App\Http\Controllers\Api\Auth\PasswordResetLinkController;
use App\Http\Controllers\Api\Auth\RegisteredUserController;
use App\Http\Controllers\Api\Auth\VerifyEmailController;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\BatasBarangController;
use App\Http\Controllers\Api\BatasPengajuanController;
use App\Http\Controllers\Api\DetailPengajuanController;

// Route u/ Auth
Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest', 'web')
    ->name('login');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth', 'throttle:6,1'])
    ->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Route yang Diproteksi Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Route Users
    Route::apiResource('users', UserController::class);

    // Route Barang
    Route::apiResource('barang', BarangController::class);

    // Route Pengajuan
    Route::apiResource('pengajuan', PengajuanController::class);

    // Route DetailPengajuan
    Route::get('detail-pengajuan', [DetailPengajuanController::class, 'index']);
    Route::post('detail-pengajuan', [DetailPengajuanController::class, 'store']);
    Route::get('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'show']);
    Route::put('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'update']);
    Route::delete('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'destroy']);


    // Route Gudang
    Route::get('gudang', [GudangController::class, 'index']);
    Route::post('gudang', [GudangController::class, 'store']);
    Route::get('gudang/{unique_id}/{id_barang}', [GudangController::class, 'show']);
    Route::put('gudang/{unique_id}/{id_barang}', [GudangController::class, 'update']);
    Route::delete('gudang/{unique_id}/{id_barang}', [GudangController::class, 'destroy']);

    // Route Batas Barang
    Route::apiResource('batas-barang', BatasBarangController::class);

    // Route Batas Pengajuan
    Route::apiResource('batas-pengajuan', BatasPengajuanController::class);
});