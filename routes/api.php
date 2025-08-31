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

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Refactored for clarity by grouping routes by resource, not by role.
*/

Route::prefix('v1')->group(function () {

    // --- PUBLIC & HEALTH CHECK ROUTES ---
    Route::middleware('api.public')->group(function () {
        Route::get('online', fn() => response()->json(['message' => 'API is online']));
        Route::get('health', fn() => response()->json(['status' => 'healthy', 'timestamp' => now()->toISOString()]));
    });

    // --- AUTHENTICATED USER ROUTES ---
    // All routes in this group require a valid token.
    Route::middleware('api.protected')->group(function () {

        // Profile & Session
        Route::get('profile', fn(Request $request) => new UserResource($request->user()->load('roles')));
        Route::post('logout', function (Request $request) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Logged out successfully']);
        });

        // --- RESOURCE: Barang & Jenis Barang ---
        Route::apiResource('barang', BarangController::class);
        Route::apiResource('jenis-barang', JenisBarangController::class);

        // --- RESOURCE: Pengajuan (Item Requests) ---
        Route::apiResource('pengajuan', PengajuanController::class);
        Route::apiResource('detail-pengajuan', DetailPengajuanController::class)->except(['index', 'show']); // Assuming these are custom
        Route::get('detail-pengajuan', [DetailPengajuanController::class, 'index']);
        Route::get('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'show']);
        
        // --- RESOURCE: Gudang (Warehouse/Inventory) ---
        Route::apiResource('gudang', GudangController::class);

        // --- RESOURCE: Penggunaan Barang (Item Usage) ---
        Route::apiResource('penggunaan-barang', PenggunaanBarangController::class);
        Route::prefix('penggunaan-barang')->name('penggunaan-barang.')->group(function () {
            Route::get('my-requests', [PenggunaanBarangController::class, 'myRequests'])->name('my-requests');
            Route::post('{id}/approve', [PenggunaanBarangController::class, 'approve'])->name('approve')->middleware('role:admin|manager');
            Route::post('{id}/reject', [PenggunaanBarangController::class, 'reject'])->name('reject')->middleware('role:admin|manager');
            Route::get('pending-approvals', [PenggunaanBarangController::class, 'pendingApprovals'])->name('pending-approvals')->middleware('role:admin|manager');
            Route::put('{id}/force-update', [PenggunaanBarangController::class, 'forceUpdate'])->name('force-update')->middleware('role:admin');
            Route::delete('{id}/force-delete', [PenggunaanBarangController::class, 'forceDelete'])->name('force-delete')->middleware('role:admin');
        });
        
        // --- RESOURCE: Users ---
        Route::apiResource('users', UserController::class)->middleware('role:admin');

        // --- RESOURCE: Batas Barang (Item Limits) ---
        Route::apiResource('batas-barang', BatasBarangController::class)->middleware('role:admin');

        // --- RESOURCE: Global Settings ---
        Route::prefix('global-settings')->name('global-settings.')->middleware('role:admin')->group(function () {
            Route::get('/', [GlobalSettingsController::class, 'index'])->name('index');
            Route::get('monthly-limit', [GlobalSettingsController::class, 'getMonthlyLimit'])->name('getMonthlyLimit');
            Route::put('monthly-limit', [GlobalSettingsController::class, 'setMonthlyLimit'])->name('setMonthlyLimit');
        });

        // --- REPORTS ---
        Route::prefix('laporan')->name('laporan.')->group(function () {
            // General reports for all authenticated users
            Route::get('summary', [LaporanController::class, 'summary'])->name('summary');
            Route::get('barang', [LaporanController::class, 'barang'])->name('barang');
            Route::get('pengajuan', [LaporanController::class, 'pengajuan'])->name('pengajuan');
            
            // Manager & Admin reports
            Route::get('cabang', [LaporanController::class, 'cabang'])->name('cabang')->middleware('role:admin|manager');
            Route::get('penggunaan', [LaporanController::class, 'penggunaan'])->name('penggunaan')->middleware('role:admin|manager');
            Route::get('stok', [LaporanController::class, 'stok'])->name('stok')->middleware('role:admin|manager');
            
            // Exports
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
