# Audit Perubahan Aturan Bisnis — 3 November 2025

## Ringkasan Perubahan Aturan

Berdasarkan aturan bisnis baru yang telah didokumentasikan di `BUSINESS_RULES.md`:

### Manager Role — Perubahan Kunci
- **Sebelumnya:** Manager memiliki hak create/update/delete di berbagai modul
- **Sekarang:** Manager **read-only** (view-only) untuk semua modul kecuali data pribadi
- **Fungsi:** Pengawas/oversight di kantor pusat; hanya satu manager
- **Scope:** Melihat semua data dari semua cabang (global view)

### Penggunaan Barang — Perubahan Alur
- **Sebelumnya:** Auto-approve + UI approve/reject (konflik)
- **Sekarang:** **Auto-approve** saat create, **tidak ada** approval workflow
- **Implikasi:** Hapus endpoint approve/reject dan UI terkait

### Pengadaan Barang — Pembatasan
- **Manager:** TIDAK berhak ajukan, ubah, hapus, atau setujui pengadaan
- **Manager:** Hanya view (oversight)
- **User:** Dapat ajukan dan ubah miliknya (sebelum disetujui)
- **Admin:** Full control termasuk approval

### Jenis Barang & Barang — Pembatasan
- **Manager:** TIDAK berhak create/update/delete
- **Manager:** Hanya view-only

### Laporan — Perluasan Akses
- **User:** Dapat melihat dan **mengekspor** laporan miliknya

---

## Backend Changes Required

### 1. Policies (app/Policies/)

#### ✅ JenisBarangPolicy.php
**Status:** Sudah benar
- `viewAny()`: admin, manager, user ✓
- `create/update/delete`: admin only ✓

#### ✅ BarangPolicy.php
**Status:** Sudah benar
- `viewAny()`: admin, manager, user ✓
- `create/update/delete`: admin only ✓

#### ❌ PengajuanPolicy.php
**Status:** PERLU DIUBAH
- `create()`: Saat ini `return true` → **ubah ke admin/user only** (manager tidak boleh create)
- `update()`: Saat ini manager dapat update → **ubah ke admin only** (manager tidak boleh approve/update)
- `view()`: Manager dapat view berdasarkan branch → **ubah ke view semua (global oversight)**

**File:** `app/Policies/PengajuanPolicy.php`

**Perubahan:**
```php
public function create(User $user): bool
{
    // ❌ OLD: return true;
    // ✅ NEW: Manager tidak boleh create pengajuan
    return $user->hasAnyRole(['admin', 'user']);
}

public function update(User $user, Pengajuan $pengajuan): Response
{
    // ❌ OLD: Manager dapat update pengajuan dari branch-nya
    // ✅ NEW: Hanya admin yang dapat approve/update pengajuan
    // (before() sudah handle admin)
    return Response::deny('You do not have permission to update this request.');
}

public function view(User $user, Pengajuan $pengajuan): bool
{
    // ✅ NEW: Manager dapat view semua (global oversight)
    if ($user->hasRole('manager')) {
        return true; // Manager melihat semua
    }
    
    // User hanya melihat miliknya
    return $user->unique_id === $pengajuan->unique_id;
}
```

#### ❌ PenggunaanBarangPolicy.php
**Status:** PERLU DIUBAH BESAR
- `create()`: Saat ini `return true` → **ubah ke admin/user only** (manager tidak boleh create)
- `update()`: Cek status pending → **sesuaikan** (auto-approve jadi tidak ada status pending)
- `delete()`: Cek approved → **ubah** agar admin selalu bisa, user hanya miliknya
- **`approve()`**: **HAPUS METHOD INI** (tidak dipakai lagi)
- `view()`: Manager cek branch → **ubah ke view semua** (global oversight)

**File:** `app/Policies/PenggunaanBarangPolicy.php`

**Perubahan:**
```php
public function create(User $user): bool
{
    // ❌ OLD: return true;
    // ✅ NEW: Manager tidak boleh create penggunaan
    return $user->hasAnyRole(['admin', 'user']);
}

public function update(User $user, PenggunaanBarang $penggunaanBarang): Response
{
    // ✅ NEW: Admin selalu bisa; user hanya miliknya (tidak ada status check karena auto-approve)
    if ($user->unique_id === $penggunaanBarang->unique_id) {
        return Response::allow();
    }
    
    return Response::deny('You do not own this record.');
}

public function delete(User $user, PenggunaanBarang $penggunaanBarang): Response
{
    // ✅ Admin handled by before()
    // User tidak boleh delete (even if owned)
    return Response::deny('You cannot delete usage records.');
}

public function view(User $user, PenggunaanBarang $penggunaanBarang): bool
{
    // ✅ NEW: Manager dapat view semua (global oversight)
    if ($user->hasRole('manager')) {
        return true;
    }
    
    // User hanya melihat miliknya
    return $user->unique_id === $penggunaanBarang->unique_id;
}

// ❌ REMOVE: public function approve() — tidak dipakai lagi
```

---

### 2. Controllers (app/Http/Controllers/Api/)

#### ❌ PenggunaanBarangController.php
**File:** `app/Http/Controllers/Api/PenggunaanBarangController.php`

**Perubahan:**
1. **Hapus** method `approve()` dan `reject()` (tidak dipakai)
2. **Implement** method `getAvailableStock()` dan `getStockForItem($id)` untuk endpoint stok

**Action:**
```php
// ❌ REMOVE these methods:
// public function approve(...)
// public function reject(...)
// public function pendingApprovals(...) // jika ada

// ✅ ADD these methods:
public function getAvailableStock(Request $request): JsonResponse
{
    $user = $request->user();
    
    // Admin/Manager: semua stok; User: stok di area/cabangnya
    $query = Gudang::with('barang.jenisBarang');
    
    if ($user->hasRole('user')) {
        $query->where('unique_id', $user->unique_id);
    }
    
    $stok = $query->select('id_barang', 'unique_id', 'jumlah_barang')
        ->get()
        ->map(fn($item) => [
            'id_barang' => $item->id_barang,
            'nama_barang' => $item->barang->nama ?? 'Unknown',
            'jumlah_tersedia' => $item->jumlah_barang,
            'unique_id' => $item->unique_id,
        ]);
    
    return response()->json($stok);
}

public function getStockForItem(Request $request, string $id_barang): JsonResponse
{
    $user = $request->user();
    
    $query = Gudang::where('id_barang', $id_barang)->with('barang');
    
    if ($user->hasRole('user')) {
        $query->where('unique_id', $user->unique_id);
    }
    
    $stok = $query->first();
    
    if (!$stok) {
        return response()->json(['message' => 'Stok tidak ditemukan'], 404);
    }
    
    return response()->json([
        'id_barang' => $stok->id_barang,
        'nama_barang' => $stok->barang->nama ?? 'Unknown',
        'jumlah_tersedia' => $stok->jumlah_barang,
        'unique_id' => $stok->unique_id,
    ]);
}
```

---

### 3. Services (app/Services/)

#### ❌ PenggunaanBarangService.php
**File:** `app/Services/PenggunaanBarangService.php`

**Perubahan:**
1. **Hapus** method `approve()` dan `reject()`
2. Pertahankan `recordUsage()` (already auto-approves — correct behavior)

**Action:**
```php
// ❌ REMOVE these methods:
// public function approve(...)
// public function reject(...)

// ✅ KEEP: recordUsage() — sudah benar (auto-approve)
```

---

### 4. Routes (routes/api.php)

**File:** `routes/api.php`

**Perubahan:**
1. **Hapus** route approve/reject penggunaan barang
2. **Pastikan** endpoint stok tersedia sudah mapped ke method controller yang baru

**Action:**
```php
// ❌ REMOVE:
// Route::post('{penggunaan_barang}/approve', ...)
// Route::post('{penggunaan_barang}/reject', ...)
// Route::get('pending/approvals', ...)

// ✅ ENSURE these exist (already declared, now implemented):
Route::prefix('stok')->group(function () {
    Route::get('tersedia', [PenggunaanBarangController::class, 'getAvailableStock']);
    Route::get('tersedia/{id_barang}', [PenggunaanBarangController::class, 'getStockForItem']);
});
```

---

### 5. Resources (app/Http/Resources/)

#### ❌ PenggunaanBarangResource.php
**File:** `app/Http/Resources/PenggunaanBarangResource.php`

**Perubahan:**
- **Expose** `user.unique_id` untuk FE permission guard

**Action:**
```php
'user' => [
    'unique_id' => $this->user->unique_id, // ✅ ADD
    'name' => $this->user->name,
    // ... other fields
],
```

---

### 6. Laporan/Export Controllers

**File:** Likely `app/Http/Controllers/Api/LaporanController.php`

**Perubahan:**
- Endpoint ekspor untuk **user** harus filter hanya data miliknya
- Admin/Manager dapat ekspor semua data

**Action:**
```php
// Di method export (e.g., exportPenggunaan, exportPenggunaanBarang):
$user = $request->user();

if ($user->hasRole('user')) {
    // ✅ Filter by unique_id untuk user
    $query->where('unique_id', $user->unique_id);
}
// Admin/Manager: tidak perlu filter (semua data)
```

---

## Frontend Changes Required

### 1. Router Guards (src/router/)

#### ❌ index.js
**File:** `src/router/index.js`

**Perubahan:**
```javascript
// Pengajuan Barang: remove manager from roles
{
  path: '/user/pengajuan',
  name: 'PengajuanBarang',
  component: () => import('@/pages/user/PengajuanBarang.vue'),
  meta: { 
    requiresAuth: true, 
    roles: ['user', 'admin'], // ❌ REMOVE 'manager'
    title: 'Pengajuan Barang' 
  }
},

// Persetujuan Pengadaan: admin only (manager tidak approve)
{
  path: '/admin/persetujuan',
  name: 'PersetujuanPengadaan',
  component: () => import('@/pages/admin/PersetujuanPengadaan.vue'),
  meta: { 
    requiresAuth: true, 
    roles: ['admin'], // ❌ REMOVE 'manager'
    title: 'Persetujuan Pengadaan' 
  }
},

// Jenis Barang: admin only
{
  path: '/jenis-barang',
  name: 'JenisBarang',
  component: () => import('@/pages/JenisBarang.vue'),
  meta: { 
    requiresAuth: true, 
    roles: ['admin'], // ✅ Already correct
    title: 'Jenis Barang' 
  }
},
```

#### ❌ penggunaan.js
**File:** `src/router/penggunaan.js`

**Status:** Sudah benar (admin, manager, user dapat view)
**Catatan:** Manager view-only; logic di component

---

### 2. Components/Pages

#### ❌ src/pages/shared/PenggunaanBarang.vue
**Perubahan:**
1. **Hapus** UI approve/reject (tombol, modal, actions)
2. **Sembunyikan** tombol create untuk manager
3. **Fix** permission guard untuk edit (pakai `user.unique_id` dari resource)

#### ❌ src/components/PenggunaanBarangTable.vue
**Perubahan:**
1. **Hapus** kolom/tombol approve/reject
2. **Update** `canEdit` check:
```javascript
// ❌ OLD:
canEdit: computed(() => item.user?.unique_id === currentUser.unique_id)

// ✅ NEW (setelah resource di-update):
canEdit: computed(() => {
  const userStore = useUserStore();
  // Admin selalu bisa edit; user hanya miliknya; manager tidak bisa
  if (userStore.user.role === 'admin') return true;
  if (userStore.user.role === 'manager') return false;
  return item.user?.unique_id === userStore.user.unique_id;
})
```

#### ❌ src/components/PenggunaanBarangForm.vue
**Perubahan:**
1. **Disable/hide** untuk manager (tidak boleh create)
2. Pastikan path endpoint stok benar: `/api/v1/stok/tersedia`

#### ❌ src/stores/penggunaanBarangStore.js
**Perubahan:**
1. **Implement** `savePenggunaan()` (create vs update)
2. **Perbaiki** path endpoint stok ke `/stok/tersedia`
3. **Hapus** action `approvePenggunaan` dan `rejectPenggunaan`

**Action:**
```javascript
// ✅ ADD:
async savePenggunaan(data) {
  const endpoint = data.id_penggunaan_barang 
    ? `/penggunaan-barang/${data.id_penggunaan_barang}` 
    : '/penggunaan-barang';
  const method = data.id_penggunaan_barang ? 'put' : 'post';
  
  const response = await api[method](endpoint, data);
  return response.data;
},

// ✅ FIX endpoint path:
async fetchAvailableStock() {
  // ❌ OLD: '/penggunaan-barang/stok/tersedia'
  // ✅ NEW:
  const response = await api.get('/stok/tersedia');
  return response.data;
},

// ❌ REMOVE:
// async approvePenggunaan(id) { ... }
// async rejectPenggunaan(id, reason) { ... }
```

#### ❌ src/pages/user/PengajuanBarang.vue
**Perubahan:**
- **Sembunyikan** form create untuk manager (jika ada conditional render)

---

### 3. Laporan Pages

#### ❌ src/pages/shared/LaporanPengadaan.vue (atau equivalent)
**Perubahan:**
- **Tambahkan** user ke roles meta (jika belum)
- Tombol ekspor muncul untuk semua role; backend enforce scope

**Router:**
```javascript
{
  path: '/laporan',
  name: 'Laporan',
  component: () => import('@/pages/shared/LaporanPengadaan.vue'),
  meta: {
    requiresAuth: true,
    roles: ['admin', 'manager', 'user'], // ✅ ADD 'user'
    title: 'Laporan'
  }
}
```

---

## Implementation Priority

### Phase 1: Backend Core (Blocking Issues)
1. ✅ Update `PengajuanPolicy.php` (manager tidak create/approve)
2. ✅ Update `PenggunaanBarangPolicy.php` (manager tidak create; hapus approve)
3. ✅ Hapus method approve/reject dari `PenggunaanBarangService.php`
4. ✅ Implement `getAvailableStock()` dan `getStockForItem()` di `PenggunaanBarangController.php`
5. ✅ Hapus route approve/reject dari `routes/api.php`
6. ✅ Expose `user.unique_id` di `PenggunaanBarangResource.php`

### Phase 2: Frontend Alignment
7. ✅ Update router guards (hapus manager dari pengajuan/persetujuan)
8. ✅ Implement `savePenggunaan()` di store, fix endpoint stok path
9. ✅ Hapus UI approve/reject di PenggunaanBarang components
10. ✅ Update permission guards (canEdit, canCreate) per role
11. ✅ Tambahkan user ke laporan routes

### Phase 3: Laporan/Export
12. ✅ Update LaporanController untuk filter by user role

---

## Testing Checklist

### Backend
- [ ] Manager tidak dapat create pengajuan (403)
- [ ] Manager tidak dapat approve pengajuan (403)
- [ ] Manager tidak dapat create penggunaan barang (403)
- [ ] Manager tidak dapat update penggunaan barang (403)
- [ ] Manager dapat view semua data (200)
- [ ] Endpoint `/api/v1/stok/tersedia` return data (200)
- [ ] User dapat create penggunaan barang (auto-approve, 201)
- [ ] User dapat ekspor laporan miliknya (200)

### Frontend
- [ ] Manager tidak melihat tombol create di Pengajuan
- [ ] Manager tidak melihat tombol create di Penggunaan Barang
- [ ] Manager tidak melihat tombol approve/reject
- [ ] User dapat create penggunaan barang
- [ ] User dapat melihat dan ekspor laporan
- [ ] Form penggunaan load stok dari `/api/v1/stok/tersedia`
- [ ] Edit guard pakai `user.unique_id` dari resource

---

**Status:** Audit selesai; ready for implementation.
