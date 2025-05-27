<?php
// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\BatasBarangController;
use App\Http\Controllers\Api\BatasPengajuanController;
use App\Http\Controllers\Api\DetailPengajuanController;
use App\Http\Controllers\Api\Auth\LogoutController;

// Health check
Route::get('v1/online', fn() => response()->json(['message' => 'API is online']));

// API Version 1: only protected routes
Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function () {

        // Resources
        Route::apiResource('users', UserController::class);
        Route::apiResource('barang', BarangController::class);
        Route::apiResource('jenis-barang', \App\Http\Controllers\Api\JenisBarangController::class);
        Route::apiResource('pengajuan', PengajuanController::class);
        Route::apiResource('batas-barang', BatasBarangController::class);
        Route::apiResource('batas-pengajuan', BatasPengajuanController::class);

        // DetailPengajuan
        Route::get('detail-pengajuan', [DetailPengajuanController::class, 'index']);
        Route::post('detail-pengajuan', [DetailPengajuanController::class, 'store']);
        Route::get('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'show']);
        Route::put('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'update']);
        Route::delete('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'destroy']);

        // Gudang
        Route::get('gudang', [GudangController::class, 'index']);
        Route::post('gudang', [GudangController::class, 'store']);
        Route::get('gudang/{unique_id}/{id_barang}', [GudangController::class, 'show']);
        Route::put('gudang/{unique_id}/{id_barang}', [GudangController::class, 'update']);
        Route::delete('gudang/{unique_id}/{id_barang}', [GudangController::class, 'destroy']);
    });

// Handle preflight
Route::options('{any}', fn() => response()->json([], 204))
     ->where('any', '.*');
