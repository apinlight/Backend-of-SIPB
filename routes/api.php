<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\PengajuanBarangInfoController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\BatasBarangController;
use App\Http\Controllers\Api\GlobalSettingsController;
use App\Http\Controllers\Api\DetailPengajuanController;
use App\Http\Controllers\Api\JenisBarangController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\PenggunaanBarangController;
use App\Http\Resources\UserResource;

Route::prefix('v1')->group(function () {
    // --- PUBLIC & HEALTH CHECK ROUTES ---
    Route::middleware('api.public')->group(function () {
        Route::get('online', fn() => response()->json(['message' => 'API is online']));
        Route::get('health', fn() => response()->json(['status' => 'healthy', 'timestamp' => now()->toISOString()]));
    });

    // --- AUTHENTICATED USER ROUTES ---
    Route::middleware('api.protected')->group(function () {
        
        // Profile & Session
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);

        // --- STANDARD RESOURCES ---
        Route::apiResource('barang', BarangController::class);
        Route::apiResource('jenis-barang', JenisBarangController::class);
        Route::apiResource('pengajuan', PengajuanController::class);
        Route::apiResource('detail-pengajuan', DetailPengajuanController::class);
        Route::apiResource('gudang', GudangController::class);
        Route::apiResource('penggunaan-barang', PenggunaanBarangController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('batas-barang', BatasBarangController::class);
        
        // --- CUSTOM RESOURCE ACTIONS ---

        Route::prefix('pengajuan')->group(function() {
            Route::get('info/barang', [PengajuanBarangInfoController::class, 'getBarangInfo']);
            Route::get('info/barang-history/{id_barang}', [PengajuanBarangInfoController::class, 'getBarangPengajuanHistory']);
        });

        Route::prefix('gudang')->group(function() {
            Route::post('{unique_id}/{id_barang}/adjust-stock', [GudangController::class, 'adjustStock'])->middleware('role:admin');
        });

        Route::prefix('penggunaan-barang')->group(function () {
            // Note: The route parameter should match the variable name in the controller method for route model binding
            Route::post('{penggunaan_barang}/approve', [PenggunaanBarangController::class, 'approve'])->middleware('role:admin|manager');
            Route::post('{penggunaan_barang}/reject', [PenggunaanBarangController::class, 'reject'])->middleware('role:admin|manager');
            Route::get('pending/approvals', [PenggunaanBarangController::class, 'pendingApprovals'])->middleware('role:admin|manager');
        });
        
        Route::prefix('stok')->group(function() {
            Route::get('tersedia', [PenggunaanBarangController::class, 'getAvailableStock']);
            Route::get('tersedia/{id_barang}', [PenggunaanBarangController::class, 'getStockForItem']);
        });

        Route::prefix('users')->group(function () {
            Route::post('{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('role:admin|manager');
            Route::post('{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('role:admin|manager');
        });

        Route::prefix('global-settings')->middleware('role:admin')->group(function () {
            Route::get('/', [GlobalSettingsController::class, 'index']);
            Route::get('monthly-limit', [GlobalSettingsController::class, 'getMonthlyLimit']);
            Route::put('monthly-limit', [GlobalSettingsController::class, 'setMonthlyLimit']);
        });

        // --- REPORTS ---
        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('summary', [LaporanController::class, 'summary'])->name('summary');
            Route::get('barang', [LaporanController::class, 'barang'])->name('barang');
            Route::get('pengajuan', [LaporanController::class, 'pengajuan'])->name('pengajuan');
            
            Route::middleware('role:admin|manager')->group(function () {
                Route::get('cabang', [LaporanController::class, 'cabang'])->name('cabang');
                Route::get('penggunaan', [LaporanController::class, 'penggunaan'])->name('penggunaan');
                Route::get('stok', [LaporanController::class, 'stok'])->name('stok');
                Route::get('stok-summary', [LaporanController::class, 'stockSummary'])->name('stok-summary');
            });
            
            Route::prefix('export')->name('export.')->middleware('role:admin|manager')->group(function () {
                Route::get('summary', [LaporanController::class, 'exportSummary'])->name('summary');
                Route::get('barang', [LaporanController::class, 'exportBarang'])->name('barang');
                Route::get('pengajuan', [LaporanController::class, 'exportPengajuan'])->name('pengajuan');
                Route::get('penggunaan', [LaporanController::class, 'exportPenggunaan'])->name('penggunaan');
                Route::get('stok', [LaporanController::class, 'exportStok'])->name('stok');
                Route::get('all', [LaporanController::class, 'exportAll'])->name('all');
            });
        });
    });

    // --- DEVELOPMENT ROUTES ---
    if (app()->environment('local')) {
        Route::middleware('api.debug')->prefix('debug')->name('debug.')->group(function () {
            Route::get('info', fn(Request $request) => response()->json([ /* debug info */ ]));
            Route::get('routes', fn() => response()->json(['routes' => collect(Route::getRoutes())->map(fn($route) => [ /* route info */ ])->toArray()]));
        });
    }
});