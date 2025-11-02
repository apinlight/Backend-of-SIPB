# Frontend-Backend Alignment Analysis

**Date:** October 28, 2025  
**Focus:** Sidebar menu options vs Backend API routes

---

## Frontend Sidebar Menu Structure

### 1. **Dashboard** 🏠
- **Path:** `/dashboard`
- **Roles:** admin, manager, user
- **Backend:** ✅ No specific API endpoint (just a UI page)

---

### 2. **Pengadaan** 📦
**Roles:** admin, manager, user

#### 2.1 Daftar Barang
- **Path:** `/daftar-barang`
- **Roles:** admin, manager, user
- **Backend:** ✅ `Route::apiResource('barang', BarangController::class)`
  - GET /api/v1/barang (index)
  - GET /api/v1/barang/{id} (show)
  - POST /api/v1/barang (store) - admin only
  - PUT /api/v1/barang/{id} (update) - admin only
  - DELETE /api/v1/barang/{id} (destroy) - admin only

#### 2.2 Pengajuan Barang
- **Path:** `/user/pengajuan`
- **Roles:** user
- **Backend:** ✅ `Route::apiResource('pengajuan', PengajuanController::class)`
  - GET /api/v1/pengajuan (index)
  - POST /api/v1/pengajuan (store)
  - GET /api/v1/pengajuan/{id} (show)
  - PUT /api/v1/pengajuan/{id} (update)
  - DELETE /api/v1/pengajuan/{id} (destroy)
- **Additional:** ✅ `Route::get('info/barang', [PengajuanBarangInfoController::class, 'getBarangInfo'])`
- **Additional:** ✅ `Route::get('info/barang-history/{id_barang}', [PengajuanBarangInfoController::class, 'getBarangPengajuanHistory'])`

#### 2.3 Riwayat Pengajuan
- **Path:** `/user/riwayat`
- **Roles:** admin, manager, user
- **Backend:** ✅ Uses same `pengajuan` resource endpoint with filters

#### 2.4 Persetujuan Pengadaan
- **Path:** `/admin/persetujuan`
- **Roles:** admin, manager
- **Backend:** ✅ `Route::apiResource('pengajuan', PengajuanController::class)` + approval logic in controller
- **Status:** Likely needs specific approval/rejection endpoints (currently handled via update with status change)

#### 2.5 Pengadaan Disetujui
- **Path:** `/admin/pengadaan-disetujui`
- **Roles:** admin
- **Backend:** ✅ Uses `pengajuan` resource with `status=approved` filter

#### 2.6 Pengadaan Manual
- **Path:** `/admin/pengadaan-manual`
- **Roles:** admin
- **Backend:** ✅ `Route::apiResource('detail-pengajuan', DetailPengajuanController::class)`
  - For manually adding procurement details
- **Additional:** ⚠️ May need dedicated manual procurement endpoint if workflow differs

---

### 3. **Penggunaan Barang** 🔧
**Roles:** admin, manager, user

#### 3.1 Kelola Penggunaan
- **Path:** `/penggunaan-barang`
- **Roles:** admin, manager, user
- **Backend:** ✅ `Route::apiResource('penggunaan-barang', PenggunaanBarangController::class)`
  - GET /api/v1/penggunaan-barang (index)
  - POST /api/v1/penggunaan-barang (store)
  - GET /api/v1/penggunaan-barang/{id} (show)
  - PUT /api/v1/penggunaan-barang/{id} (update)
  - DELETE /api/v1/penggunaan-barang/{id} (destroy)
- **Additional:** ✅ `Route::post('{penggunaan_barang}/approve', [PenggunaanBarangController::class, 'approve'])->middleware('role:admin|manager')`
- **Additional:** ✅ `Route::post('{penggunaan_barang}/reject', [PenggunaanBarangController::class, 'reject'])->middleware('role:admin|manager')`
- **Additional:** ✅ `Route::get('pending/approvals', [PenggunaanBarangController::class, 'pendingApprovals'])->middleware('role:admin|manager')`

#### 3.2 Stok Tersedia
- **Path:** `/stok-tersedia`
- **Roles:** admin, manager, user
- **Backend:** ✅ `Route::get('stok/tersedia', [PenggunaanBarangController::class, 'getAvailableStock'])`
- **Backend:** ✅ `Route::get('stok/tersedia/{id_barang}', [PenggunaanBarangController::class, 'getStockForItem'])`
- **Alternative:** ✅ `Route::apiResource('gudang', GudangController::class)` - for stock management
- **Additional:** ✅ `Route::post('gudang/{unique_id}/{id_barang}/adjust-stock', [GudangController::class, 'adjustStock'])->middleware('role:admin')`

---

### 4. **Laporan** 📈
**Roles:** admin, manager

#### 4.1 Laporan Pengadaan
- **Path:** `/laporan`
- **Roles:** admin
- **Backend:** ✅ `Route::prefix('laporan')->group(function () { ... })`
  - GET /api/v1/laporan/summary
  - GET /api/v1/laporan/barang
  - GET /api/v1/laporan/pengajuan
  - GET /api/v1/laporan/cabang (admin|manager)
  - GET /api/v1/laporan/penggunaan (admin|manager)
  - GET /api/v1/laporan/stok (admin|manager)
  - GET /api/v1/laporan/stok-summary (admin|manager)
- **Export Excel:** ✅ 
  - GET /api/v1/laporan/export/summary (admin|manager)
  - GET /api/v1/laporan/export/barang (admin|manager)
  - GET /api/v1/laporan/export/pengajuan (admin|manager)
  - GET /api/v1/laporan/export/penggunaan (admin|manager)
  - GET /api/v1/laporan/export/stok (admin|manager)
  - GET /api/v1/laporan/export/all (admin|manager)
- **Export Word:** ✅ 
  - GET /api/v1/laporan/export-word/summary (admin|manager)
  - GET /api/v1/laporan/export-word/barang (admin|manager)

#### 4.2 Riwayat Cabang
- **Path:** `/manager/riwayat-cabang`
- **Roles:** manager
- **Backend:** ✅ `Route::get('laporan/cabang', [LaporanController::class, 'cabang'])->middleware('role:admin|manager')`

---

### 5. **Administrasi** ⚙️
**Roles:** admin

#### 5.1 Kelola Users
- **Path:** `/users`
- **Roles:** admin
- **Backend:** ✅ `Route::apiResource('users', UserController::class)`
  - GET /api/v1/users (index)
  - POST /api/v1/users (store)
  - GET /api/v1/users/{id} (show)
  - PUT /api/v1/users/{id} (update)
  - DELETE /api/v1/users/{id} (destroy)
- **Additional:** ✅ `Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('role:admin')`
- **Additional:** ✅ `Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->middleware('role:admin')`

#### 5.2 Jenis Barang
- **Path:** `/jenis-barang`
- **Roles:** admin
- **Backend:** ✅ `Route::apiResource('jenis-barang', JenisBarangController::class)`
  - GET /api/v1/jenis-barang (index)
  - POST /api/v1/jenis-barang (store)
  - GET /api/v1/jenis-barang/{id} (show)
  - PUT /api/v1/jenis-barang/{id} (update)
  - DELETE /api/v1/jenis-barang/{id} (destroy)
- **Additional:** ✅ `Route::get('jenis-barang/list/active', [JenisBarangController::class, 'active'])`
- **Additional:** ✅ `Route::post('jenis-barang/{jenis_barang}/toggle-status', [JenisBarangController::class, 'toggleStatus'])`

#### 5.3 Batas Barang
- **Path:** `/batas-barang`
- **Roles:** admin
- **Backend:** ✅ `Route::apiResource('batas-barang', BatasBarangController::class)`
  - GET /api/v1/batas-barang (index)
  - POST /api/v1/batas-barang (store)
  - GET /api/v1/batas-barang/{id} (show)
  - PUT /api/v1/batas-barang/{id} (update)
  - DELETE /api/v1/batas-barang/{id} (destroy)
- **Additional:** ✅ `Route::post('batas-barang/check-allocation', [BatasBarangController::class, 'checkAllocation'])`

#### 5.4 Batas Pengajuan
- **Path:** `/batas-pengajuan`
- **Roles:** admin
- **Backend:** ✅ `Route::prefix('global-settings')->middleware('role:admin')->group(function () { ... })`
  - GET /api/v1/global-settings/
  - GET /api/v1/global-settings/monthly-limit
  - PUT /api/v1/global-settings/monthly-limit

---

## Summary

### ✅ **Fully Aligned**
- Dashboard
- Daftar Barang
- Pengajuan Barang
- Riwayat Pengajuan
- Penggunaan Barang (Kelola Penggunaan)
- Stok Tersedia
- Laporan Pengadaan (with Excel and Word exports)
- Riwayat Cabang
- Kelola Users
- Jenis Barang
- Batas Barang
- Batas Pengajuan

### ⚠️ **Partial/Need Verification**
- **Persetujuan Pengadaan:** Uses general `pengajuan` endpoints; may benefit from dedicated approval endpoints
- **Pengadaan Disetujui:** Filter-based on `pengajuan` resource
- **Pengadaan Manual:** Uses `detail-pengajuan` resource; workflow may need dedicated endpoint if complex

### ❌ **Missing/Gaps**
None identified - all frontend menu items have corresponding backend API endpoints.

---

## Recommendations

1. **Word Export:** Currently only Summary and Barang have Word export. Consider adding:
   - `/api/v1/laporan/export-word/pengajuan`
   - `/api/v1/laporan/export-word/penggunaan`
   - `/api/v1/laporan/export-word/stok`
   - `/api/v1/laporan/export-word/all`

2. **Approval Workflow:** Consider dedicated endpoints:
   - `POST /api/v1/pengajuan/{id}/approve`
   - `POST /api/v1/pengajuan/{id}/reject`
   (Currently handled via general update endpoint with status change)

3. **Manual Procurement:** If workflow is complex, consider:
   - `POST /api/v1/pengadaan/manual` with batch detail creation

4. **Testing:** All sidebar paths should have corresponding integration tests to ensure:
   - Role-based access control works correctly
   - Data flows from frontend to backend as expected
   - Response formats match frontend expectations
