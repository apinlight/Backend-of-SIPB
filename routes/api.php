<?php

use App\Http\Controllers\Api\BarangController;
use App\Http\Controllers\Api\BatasBarangController;
use App\Http\Controllers\Api\DetailPengajuanController;
use App\Http\Controllers\Api\GlobalSettingsController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\JenisBarangController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\PengajuanBarangInfoController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\PenggunaanBarangController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware('api.public')->group(function () {
        Route::get('online', fn () => response()->json(['message' => 'API is online']));
        Route::get('health', fn () => response()->json(['status' => 'healthy', 'timestamp' => now()->toISOString()]));
    });

    Route::middleware('api.protected')->group(function () {
        Route::get('profile', [UserController::class, 'profile']);
        Route::put('profile', [UserController::class, 'updateProfile']);

        Route::apiResource('barang', BarangController::class);
        Route::apiResource('jenis-barang', JenisBarangController::class);
        Route::apiResource('pengajuan', PengajuanController::class);
        Route::apiResource('detail-pengajuan', DetailPengajuanController::class);
        Route::apiResource('gudang', GudangController::class);
        Route::apiResource('penggunaan-barang', PenggunaanBarangController::class);
        Route::apiResource('users', UserController::class);
        Route::apiResource('batas-barang', BatasBarangController::class);

        Route::prefix('jenis-barang')->group(function () {
            Route::get('list/active', [JenisBarangController::class, 'active']);
            Route::post('{jenis_barang}/toggle-status', [JenisBarangController::class, 'toggleStatus']);
        });

        Route::prefix('pengajuan')->group(function () {
            Route::get('info/barang', [PengajuanBarangInfoController::class, 'getBarangInfo']);
            Route::get('info/barang-history/{id_barang}', [PengajuanBarangInfoController::class, 'getBarangPengajuanHistory']);
        });

        Route::prefix('gudang')->group(function () {
            Route::post('{unique_id}/{id_barang}/adjust-stock', [GudangController::class, 'adjustStock'])->middleware('role:admin');
        });

        Route::prefix('penggunaan-barang')->group(function () {
            Route::post('{penggunaan_barang}/approve', [PenggunaanBarangController::class, 'approve'])->middleware('role:admin|manager');
            Route::post('{penggunaan_barang}/reject', [PenggunaanBarangController::class, 'reject'])->middleware('role:admin|manager');
            Route::get('pending/approvals', [PenggunaanBarangController::class, 'pendingApprovals'])->middleware('role:admin|manager');
        });

        Route::prefix('stok')->group(function () {
            Route::get('tersedia', [PenggunaanBarangController::class, 'getAvailableStock']);
            Route::get('tersedia/{id_barang}', [PenggunaanBarangController::class, 'getStockForItem']);
        });

        Route::prefix('users')->group(function () {
            Route::post('{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('role:admin');
            Route::post('{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('role:admin');
        });

        Route::post('batas-barang/check-allocation', [BatasBarangController::class, 'checkAllocation']);

        Route::prefix('global-settings')->middleware('role:admin')->group(function () {
            Route::get('/', [GlobalSettingsController::class, 'index']);
            Route::get('monthly-limit', [GlobalSettingsController::class, 'getMonthlyLimit']);
            Route::put('monthly-limit', [GlobalSettingsController::class, 'setMonthlyLimit']);
        });

        Route::prefix('laporan')->name('laporan.')->group(function () {
            Route::get('summary', [LaporanController::class, 'summary']);
            Route::get('barang', [LaporanController::class, 'barang']);
            Route::get('pengajuan', [LaporanController::class, 'pengajuan']);

            Route::middleware('role:admin|manager')->group(function () {
                Route::get('cabang', [LaporanController::class, 'cabang']);
                Route::get('penggunaan', [LaporanController::class, 'penggunaan']);
                Route::get('stok', [LaporanController::class, 'stok']);
                Route::get('stok-summary', [LaporanController::class, 'stockSummary']);
            });

            Route::prefix('export')->name('export.')->middleware('role:admin|manager')->group(function () {
                Route::get('summary', [LaporanController::class, 'exportSummary']);
                Route::get('barang', [LaporanController::class, 'exportBarang']);
                Route::get('pengajuan', [LaporanController::class, 'exportPengajuan']);
                Route::get('penggunaan', [LaporanController::class, 'exportPenggunaan']);
                Route::get('stok', [LaporanController::class, 'exportStok']);
                Route::get('all', [LaporanController::class, 'exportAll']);
            });

            // ✅ DOCX (Word) export endpoints
            Route::prefix('export-word')->name('export-word.')->middleware('role:admin|manager')->group(function () {
                Route::get('summary', [LaporanController::class, 'exportSummaryDocx']);
                Route::get('barang', [LaporanController::class, 'exportBarangDocx']);
                // Additional types can be added similarly: pengajuan, penggunaan, stok, all
            });
        });
    });

    // ✅ Catch-all OPTIONS route for CORS preflight - must be last and explicitly use CORS middleware
    Route::options('{any}', function () {
        return response('', 200);
    })->where('any', '.*')->middleware('cors.custom');
});
