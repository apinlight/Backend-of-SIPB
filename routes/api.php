<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\BatasBarangController;
use App\Http\Controllers\Api\BatasPengajuanController;
use App\Http\Controllers\Api\DetailPengajuanController;
use App\Http\Controllers\Api\LaporanController;

// Health check
Route::get('v1/online', fn() => response()->json(['message' => 'API is online']));

// API Version 1: all protected routes
Route::prefix('v1')
    ->middleware('auth:sanctum')
    ->group(function () {
        
        // Auth routes - accessible by all authenticated users
        Route::post('logout', function (Request $request) {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'status' => true,
                'message' => 'Logged out successfully'
            ]);
        });
        
        Route::get('profile', function () {
            $user = Auth::user()->load('roles');
            return response()->json([
                'status' => true,
                'user' => new \App\Http\Resources\UserResource($user),
                'session_id' => session()->getId(),
                'auth_check' => Auth::check()
            ]);
        });

        // ✅ BARANG - All can view, only admin can modify
        Route::get('barang', [BarangController::class, 'index']); // All users
        Route::get('barang/{id}', [BarangController::class, 'show']); // All users
        Route::middleware('role:admin')->group(function () {
            Route::post('barang', [BarangController::class, 'store']);
            Route::put('barang/{id}', [BarangController::class, 'update']);
            Route::delete('barang/{id}', [BarangController::class, 'destroy']);
        });

        // ✅ JENIS BARANG - Admin only
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('jenis-barang', \App\Http\Controllers\Api\JenisBarangController::class);
        });

        // ✅ PENGAJUAN - Role-based access
        Route::get('pengajuan', [PengajuanController::class, 'index']); // All (with scope filtering)
        Route::get('pengajuan/{id}', [PengajuanController::class, 'show']); // All (with scope filtering)
        
        Route::middleware('role:user')->group(function () {
            Route::post('pengajuan', [PengajuanController::class, 'store']); // User only
            Route::delete('pengajuan/{id}', [PengajuanController::class, 'destroy']); // User only
        });
        
        Route::middleware('role:admin')->group(function () {
            Route::put('pengajuan/{id}', [PengajuanController::class, 'update']); // Admin only (approval)
        });

        // ✅ DETAIL PENGAJUAN - User can create, all can view (with scope)
        Route::get('detail-pengajuan', [DetailPengajuanController::class, 'index']);
        Route::get('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'show']);
        
        Route::middleware('role:user')->group(function () {
            Route::post('detail-pengajuan', [DetailPengajuanController::class, 'store']);
            Route::put('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'update']);
            Route::delete('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'destroy']);
        });

        // ✅ GUDANG - Scoped access
        Route::get('gudang', [GudangController::class, 'index']); // All (with scope filtering)
        Route::get('gudang/{unique_id}/{id_barang}', [GudangController::class, 'show']); // All (with scope filtering)
        
        Route::middleware('role:admin')->group(function () {
            Route::post('gudang', [GudangController::class, 'store']);
            Route::put('gudang/{unique_id}/{id_barang}', [GudangController::class, 'update']);
            Route::delete('gudang/{unique_id}/{id_barang}', [GudangController::class, 'destroy']);
        });

        // ✅ LAPORAN - Scoped access
        Route::prefix('laporan')->group(function () {
            Route::get('summary', [LaporanController::class, 'summary']); // All (with scope filtering)
            Route::get('barang', [LaporanController::class, 'barang']); // All (with scope filtering)
            Route::get('pengajuan', [LaporanController::class, 'pengajuan']); // All (with scope filtering)
            Route::get('cabang', [LaporanController::class, 'cabang']); // Admin + Manager only
            Route::get('export', [LaporanController::class, 'export']); // All (with scope filtering)
        });

        // ✅ ADMIN ONLY ROUTES
        Route::middleware('role:admin')->group(function () {
            Route::apiResource('users', UserController::class);
            Route::apiResource('batas-barang', BatasBarangController::class);
            Route::apiResource('batas-pengajuan', BatasPengajuanController::class);
        });
    });
