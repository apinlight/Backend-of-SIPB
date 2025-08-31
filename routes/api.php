<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
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

// ✅ PUBLIC ROUTES - No authentication required
Route::prefix('v1')
    ->middleware('api.public')
    ->group(function () {
        Route::get('online', fn() => response()->json(['message' => 'API is online']));
        Route::get('health', function () {
            return response()->json([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'version' => 'v1',
                'environment' => app()->environment(),
            ]);
        });
    });

// ✅ PROTECTED ROUTES - All authenticated users can access
Route::prefix('v1')
    ->middleware('api.protected')
    ->group(function () {
        // Auth routes
        Route::post('logout', function (Request $request) {
            $request->user()->currentAccessToken()->delete();
            return response()->json([
                'status' => true,
                'message' => 'Logged out successfully'
            ]);
        });
               
        Route::get('profile', function (Request $request) {
            $user = $request->user()->load('roles');
            return response()->json([
                'status' => true,
                'user' => new \App\Http\Resources\UserResource($user),
                'session_id' => session()->getId(),
                'auth_check' => Auth::check()
            ]);
        });

        // ✅ READ-ONLY routes for all authenticated users
        Route::get('barang', [BarangController::class, 'index']);
        Route::get('barang/{id_barang}', [BarangController::class, 'show']);
        Route::get('jenis-barang', [JenisBarangController::class, 'index']);
               
        // ✅ Pengajuan viewing (with scope filtering in controller)
        Route::get('pengajuan', [PengajuanController::class, 'index']);
        Route::get('pengajuan/{id_pengajuan}', [PengajuanController::class, 'show']);
        Route::get('detail-pengajuan', [DetailPengajuanController::class, 'index']);
        Route::get('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'show']);

        // ✅ Enhanced pengajuan barang info endpoint
        Route::get('pengajuan/barang-info', [PengajuanBarangInfoController::class, 'getBarangInfo']);
        Route::get('pengajuan/barang-history/{id_barang}', [PengajuanBarangInfoController::class, 'getBarangPengajuanHistory']);
               
        // ✅ Gudang viewing (with scope filtering)
        Route::get('gudang', [GudangController::class, 'index']);
        Route::get('gudang/{unique_id}/{id_barang}', [GudangController::class, 'show']);
        
        // ✅ FIXED: All users need access to these
        Route::get('penggunaan-barang', [PenggunaanBarangController::class, 'index']);
        Route::get('penggunaan-barang/{id}', [PenggunaanBarangController::class, 'show']);
        Route::get('stok-tersedia', [PenggunaanBarangController::class, 'getAvailableStock']);
        Route::get('stok-tersedia/{id_barang}', [PenggunaanBarangController::class, 'getStockForItem']);
               
        // ✅ Basic reports (with scope filtering)
        Route::get('laporan/summary', [LaporanController::class, 'summary']);
        Route::get('laporan/barang', [LaporanController::class, 'barang']);
        Route::get('laporan/pengajuan', [LaporanController::class, 'pengajuan']);
        
        // ✅ Excel Export Routes - Admin access
        Route::get('laporan/export', [LaporanController::class, 'export']); // Legacy with type parameter
    });

// ✅ USER ROUTES - Only users can create pengajuan
Route::prefix('v1')
    ->middleware('api.user')
    ->group(function () {
        Route::post('pengajuan', [PengajuanController::class, 'store']);
        Route::delete('pengajuan/{id_pengajuan}', [PengajuanController::class, 'destroy']);
        Route::post('detail-pengajuan', [DetailPengajuanController::class, 'store']);
        Route::put('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'update']);
        Route::delete('detail-pengajuan/{id_pengajuan}/{id_barang}', [DetailPengajuanController::class, 'destroy']);
        
        // ✅ FIXED: Users can manage their own penggunaan barang
        Route::post('penggunaan-barang', [PenggunaanBarangController::class, 'store']);
        Route::put('penggunaan-barang/{id}', [PenggunaanBarangController::class, 'update']); // Own records only
        Route::delete('penggunaan-barang/{id}', [PenggunaanBarangController::class, 'destroy']); // Own records only
        Route::get('penggunaan-barang/my-requests', [PenggunaanBarangController::class, 'myRequests']);
    });

// ✅ MANAGER ROUTES - Only managers can view branch reports
Route::prefix('v1')
    ->middleware('api.manager')
    ->group(function () {
        Route::get('laporan/cabang', [LaporanController::class, 'cabang']);
        Route::get('laporan/penggunaan', [LaporanController::class, 'penggunaan']);
        Route::get('laporan/stok', [LaporanController::class, 'stok']);
        
        // ✅ Manager Excel Export Routes - Manager+ access
        Route::get('laporan/export/summary', [LaporanController::class, 'exportSummary']);
        Route::get('laporan/export/barang', [LaporanController::class, 'exportBarang']);
        Route::get('laporan/export/pengajuan', [LaporanController::class, 'exportPengajuan']);
        Route::get('laporan/export/penggunaan', [LaporanController::class, 'exportPenggunaan']);
        Route::get('laporan/export/stok', [LaporanController::class, 'exportStok']);
        Route::get('laporan/export/all', [LaporanController::class, 'exportAll']);
        
        // ✅ Manager can approve penggunaan barang for their branch
        Route::post('penggunaan-barang/{id}/approve', [PenggunaanBarangController::class, 'approve']);
        Route::post('penggunaan-barang/{id}/reject', [PenggunaanBarangController::class, 'reject']);
        Route::get('pending-approvals', [PenggunaanBarangController::class, 'pendingApprovals']);
    });

// ✅ ADMIN ROUTES - Only admins can approve and manage
Route::prefix('v1')
    ->middleware('api.admin')
    ->group(function () {
        // ✅ Pengajuan approval
        Route::put('pengajuan/{id_pengajuan}', [PengajuanController::class, 'update']);
               
        // ✅ Barang management
        Route::post('barang', [BarangController::class, 'store']);
        Route::put('barang/{id_barang}', [BarangController::class, 'update']);
        Route::delete('barang/{id_barang}', [BarangController::class, 'destroy']);
               
        // ✅ Jenis barang management
        Route::post('jenis-barang', [JenisBarangController::class, 'store']);
        Route::put('jenis-barang/{id_jenis_barang}', [JenisBarangController::class, 'update']);
        Route::delete('jenis-barang/{id_jenis_barang}', [JenisBarangController::class, 'destroy']);
               
        // ✅ Gudang management
        Route::post('gudang', [GudangController::class, 'store']);
        Route::put('gudang/{unique_id}/{id_barang}', [GudangController::class, 'update']);
        Route::delete('gudang/{unique_id}/{id_barang}', [GudangController::class, 'destroy']);
               
        // ✅ User management
        Route::get('users', [UserController::class, 'index']);
        Route::get('users/{unique_id}', [UserController::class, 'show']);
        Route::post('users', [UserController::class, 'store']);
        Route::put('users/{unique_id}', [UserController::class, 'update']);
        Route::delete('users/{unique_id}', [UserController::class, 'destroy']);
               
        // ✅ Batas barang management
        Route::get('batas-barang', [BatasBarangController::class, 'index']);
        Route::get('batas-barang/{id_barang}', [BatasBarangController::class, 'show']);
        Route::post('batas-barang', [BatasBarangController::class, 'store']);
        Route::put('batas-barang/{id_barang}', [BatasBarangController::class, 'update']);
        Route::delete('batas-barang/{id_barang}', [BatasBarangController::class, 'destroy']);
               
        // ✅ Global settings management
        Route::get('global-settings', [GlobalSettingsController::class, 'index']);
        Route::get('global-settings/monthly-limit', [GlobalSettingsController::class, 'getMonthlyLimit']);
        Route::put('global-settings/monthly-limit', [GlobalSettingsController::class, 'setMonthlyLimit']);
               
        // ✅ Admin laporan - Full access
        Route::get('laporan/cabang', [LaporanController::class, 'cabang']);
        Route::get('laporan/penggunaan', [LaporanController::class, 'penggunaan']);
        Route::get('laporan/stok', [LaporanController::class, 'stok']);
        
        // ✅ FIXED: Admin penggunaan barang management (no conflicts)
        Route::post('penggunaan-barang/{id}/approve', [PenggunaanBarangController::class, 'approve']);
        Route::post('penggunaan-barang/{id}/reject', [PenggunaanBarangController::class, 'reject']);
        Route::get('pending-approvals', [PenggunaanBarangController::class, 'pendingApprovals']);
        
        // ✅ Admin can force update/delete any penggunaan barang
        Route::put('penggunaan-barang/{id}/force-update', [PenggunaanBarangController::class, 'forceUpdate']);
        Route::delete('penggunaan-barang/{id}/force-delete', [PenggunaanBarangController::class, 'forceDelete']);
    });

// ✅ DEVELOPMENT ROUTES - Only in debug mode
Route::prefix('v1')
    ->middleware('api.debug')
    ->group(function () {
        Route::get('debug/info', function (Request $request) {
            return response()->json([
                'app' => config('app.name'),
                'environment' => app()->environment(),
                'debug' => config('app.debug'),
                'user' => $request->user(),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
            ]);
        });
               
        Route::get('debug/routes', function () {
            return response()->json([
                'routes' => collect(Route::getRoutes())->map(function ($route) {
                    return [
                        'method' => $route->methods(),
                        'uri' => $route->uri(),
                        'name' => $route->getName(),
                        'middleware' => $route->middleware(),
                    ];
                })->toArray()
            ]);
        });
    });
