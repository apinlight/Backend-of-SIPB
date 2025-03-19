<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\BatasBarangController;
use App\Http\Controllers\Api\BatasPengajuanController;
use App\Http\Controllers\Api\DetailPengajuanController;

// Users Routes
Route::apiResource('users', UserController::class);

// Barang Routes
Route::apiResource('barang', BarangController::class);

// Pengajuan Routes
Route::apiResource('pengajuan', PengajuanController::class);

// DetailPengajuan routes
Route::get('detail-pengajuan', [DetailPengajuanController::class, 'index']);
Route::post('detail-pengajuan', [DetailPengajuanController::class, 'store']);
Route::get('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'show']);
Route::put('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'update']);
Route::delete('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'destroy']);


// Gudang Routes - composite key requires custom parameter definitions:
Route::get('gudang', [GudangController::class, 'index']);
Route::post('gudang', [GudangController::class, 'store']);
Route::get('gudang/{unique_id}/{id_barang}', [GudangController::class, 'show']);
Route::put('gudang/{unique_id}/{id_barang}', [GudangController::class, 'update']);
Route::delete('gudang/{unique_id}/{id_barang}', [GudangController::class, 'destroy']);

// Batas Barang Routes
Route::apiResource('batas-barang', BatasBarangController::class);

// Batas Pengajuan Routes
Route::apiResource('batas-pengajuan', BatasPengajuanController::class);
