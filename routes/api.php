<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
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

//API Versi 1
Route::prefix('v1')->group(function () {
    
    //Route u/ Cek Online
    Route::get('/online', function () {
        return response()->json(['message' => 'API is online']);
    });

    // Route u/ Auth
    Route::prefix('auth')->group(function () {
        Route::post('/register', [RegisteredUserController::class, 'store']);
        Route::post('/login', [LoginController::class, 'store']);
        Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
        Route::post('/reset-password', [NewPasswordController::class, 'store']);
        Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class);
        Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store']);
        //Route::post('/logout', [LogoutController::class, 'destroy']);
        Route::middleware('api')->post('/logout', [LogoutController::class, 'destroy']);
    });

    // Route yang Diproteksi Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        // Route Users
        Route::apiResource('users', UserController::class);

        // Route Barang
        Route::apiResource('barang', BarangController::class);

        // Route Jenis Barang
        Route::apiResource('jenis-barang', App\Http\Controllers\Api\JenisBarangController::class);


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
});

// Tangani semua OPTIONS request (preflight)
Route::options('{any}', function () {
    return response()->json([], 204);
})->where('any', '.*');
