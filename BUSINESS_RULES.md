# Aturan Bisnis Sistem Informasi Pencatatan Barang (SIPB)

**Tanggal Revisi:** 3 November 2025  
**Status:** Authoritative — Dokumen ini adalah sumber kebenaran untuk semua aturan bisnis aplikasi.

---

## Prinsip Dasar

- **Identitas:** Semua primary key menggunakan UUID/ULID (tidak ada auto-increment).
- **Tabel:** Nama tabel custom (tb_users, tb_barang, dll), bukan default Laravel.
- **API:** REST JSON di `/api/v1`; semua output via Laravel API Resources.
- **Autentikasi:** Laravel Sanctum (stateless Bearer token); semua endpoint protected kecuali public auth.
- **Otorisasi:** Spatie Laravel Permission; role: `admin`, `manager`, `user`.
- **Keamanan:** CORS dibatasi ke origin frontend; password di-hash dengan `Hash::make`.

---

## Roles dan Karakteristik

### Admin
- **Jumlah:** Dapat banyak (multi-admin).
- **Akses:** Seluruh data di semua modul tanpa batasan cabang/area.
- **Wewenang:** CRUD penuh pada semua entitas; manage users, roles, permissions; konfigurasi sistem.

### Manager
- **Jumlah:** **Hanya satu**, berlokasi di kantor pusat.
- **Fungsi:** Pengawas keseluruhan operasional (oversight).
- **Akses:** **Read-only** untuk semua data (tidak ada create/update/delete kecuali data miliknya sendiri pada modul tertentu).
- **Scope:** Melihat semua data dari semua cabang/area (global view).

### User
- **Jumlah:** Dapat banyak (per cabang/area).
- **Akses:** Data milik sendiri atau terikat cabang/area (scoped).
- **Wewenang:** Terbatas pada operasional harian (penggunaan barang, laporan miliknya).

---

## Aturan per Modul

### 1. Authentication & Session

| Aksi | Admin | Manager | User | Implementasi |
|---|---|---|---|---|
| Login (Sanctum) | ✓ | ✓ | ✓ | BE: Sanctum; FE: Axios + token |
| Logout | ✓ | ✓ | ✓ | FE: hapus token, reset store |
| Akses rute protected | ✓ | ✓ | ✓ | FE: route meta + interceptor; 401 → logout |

### 2. Users & Roles Management

| Aksi | Admin | Manager | User | Catatan |
|---|---|---|---|---|
| Lihat profil sendiri | ✓ | ✓ | ✓ | — |
| Daftar pengguna | ✓ | ✓ (view-only, semua) | — | Manager: read-only global |
| Buat pengguna | ✓ | — | — | Admin only |
| Ubah pengguna | ✓ | — | — | Admin only |
| Hapus pengguna | ✓ | — | — | Admin only |
| Kelola role/permission | ✓ | — | — | Spatie; admin only |

**Implementasi:**
- BE: Policy `UserPolicy` → `viewAny()` untuk admin/manager; `create/update/delete` hanya admin.
- FE: Halaman user management hanya muncul di admin route; manager dapat view list (jika UI disediakan).

---

### 3. Jenis Barang

| Aksi | Admin | Manager | User | Catatan |
|---|---|---|---|---|
| Lihat/daftar | ✓ | ✓ (view-only) | — | Manager: read-only |
| Buat | ✓ | — | — | Admin only |
| Ubah | ✓ | — | — | Admin only |
| Hapus | ✓ | — | — | Admin only |

**Implementasi:**
- BE: Policy `JenisBarangPolicy` → `viewAny()` untuk admin/manager; `create/update/delete` hanya admin.
- FE: Form create/edit/delete hanya muncul untuk admin.

---

### 4. Barang

| Aksi | Admin | Manager | User | Catatan |
|---|---|---|---|---|
| Lihat/daftar | ✓ (semua) | ✓ (view-only, semua) | ✓ (scoped: area/cabang) | Manager: read-only global |
| Buat | ✓ | — | — | Admin only |
| Ubah | ✓ | — | — | Admin only |
| Hapus | ✓ | — | — | Admin only |

**Field wajib:** id_jenis_barang, satuan, deskripsi, batas_minimum.

**Implementasi:**
- BE: Policy `BarangPolicy` → `viewAny()` untuk semua (scoped untuk user); `create/update/delete` hanya admin.
- BE: Model scope `forUser($user)` → admin: semua; manager: semua (read-only); user: filter by cabang/area.
- FE: Form create/edit/delete hanya muncul untuk admin; manager dan user hanya view list/detail.

---

### 5. Pengadaan Barang (Procurement)

| Aksi | Admin | Manager | User | Catatan |
|---|---|---|---|---|
| Ajukan pengadaan | ✓ | — | ✓ | User dapat mengajukan; Manager TIDAK |
| Lihat/daftar | ✓ (semua) | ✓ (view-only, semua) | ✓ (miliknya) | Manager: oversight, tidak ajukan |
| Ubah | ✓ | — | ✓ (miliknya, sebelum disetujui) | User edit draft-nya |
| Hapus | ✓ | — | — | Admin only |
| Setujui/Tolak | ✓ | — | — | Admin only; Manager TIDAK berwenang approve |

**Implementasi:**
- BE: Policy `PengadaanBarangPolicy` → `create` hanya admin/user; `update` admin atau creator (sebelum approved); `approve/reject` hanya admin.
- BE: Model scope `forUser($user)` → admin: semua; manager: semua (read-only); user: miliknya.
- FE: Form ajukan untuk admin/user; tombol approve/reject hanya untuk admin.

---

### 6. Penggunaan Barang (Usage/Consumption)

| Aksi | Admin | Manager | User | Catatan |
|---|---|---|---|---|
| Buat | ✓ | — | ✓ | User mencatat penggunaan; Manager TIDAK |
| Lihat/daftar | ✓ (semua) | ✓ (view-only, semua) | ✓ (miliknya) | Manager: oversight only |
| Ubah | ✓ | — | ✓ (miliknya) | User edit record-nya; Manager TIDAK |
| Hapus | ✓ | — | — | Admin only |
| **Persetujuan** | **TIDAK PERLU** | **TIDAK PERLU** | **TIDAK PERLU** | Auto-approved; tidak ada approval workflow |

**Aturan bisnis:**
- Saat user membuat penggunaan barang, stok langsung dikurangi (auto-approved).
- **Tidak ada** proses approval oleh admin atau manager.
- Endpoint approve/reject **tidak digunakan** dan dapat dihapus atau di-deprecate.
- Manager hanya dapat melihat (monitoring/oversight), tidak dapat create/update/delete.

**Validasi:**
- `quantity > 0` dan `quantity <= stok_tersedia`.
- Setelah create, record immutable kecuali oleh admin (untuk koreksi).

**Implementasi:**
- BE: Policy `PenggunaanBarangPolicy` → `create` hanya admin/user; `update` admin atau creator; `delete` admin only; **hapus** method `approve/reject`.
- BE: Service `recordUsage()` sudah auto-approve (current behavior is correct); **hapus** method `approve()` dan `reject()`.
- BE: Routes: **hapus** endpoint approve/reject penggunaan barang.
- FE: **Hapus** UI approve/reject; form create hanya untuk admin/user; edit hanya creator (admin/user).

---

### 7. Stok (Inventory)

| Aksi | Admin | Manager | User | Catatan |
|---|---|---|---|---|
| Lihat stok tersedia | ✓ | ✓ | ✓ | Endpoint `/api/v1/stok/tersedia` |
| Penyesuaian manual stok | ✓ | — | — | Admin only; via adjustment endpoint (jika ada) |

**Implementasi:**
- BE: Endpoint `/api/v1/stok/tersedia` → return `{ id_barang, nama_barang, jumlah_tersedia }` filtered by user scope.
- BE: Controller `PenggunaanBarangController` atau dedicated `StokController` → implement `getAvailableStock()` dan `getStockForItem($id)`.
- FE: Dropdown/autocomplete saat create penggunaan barang memakai endpoint ini.

---

### 8. Laporan & Ekspor

| Aksi | Admin | Manager | User | Catatan |
|---|---|---|---|---|
| Ekspor Excel laporan | ✓ (semua data) | ✓ (semua data, read-only) | ✓ (miliknya) | User: laporan penggunaan miliknya |

**Implementasi:**
- BE: Endpoint ekspor → filter by role: admin/manager (semua data); user (miliknya).
- FE: Tombol ekspor muncul untuk semua role; backend enforce scope.

---

## Scope Visibilitas Data

| Role | Scope | Catatan |
|---|---|---|
| Admin | Semua data, tidak ada batasan | Full access |
| Manager | Semua data dari semua cabang/area, **read-only** (kecuali data pribadi) | Global oversight; tidak create/update/delete pada master data atau transaksi |
| User | Hanya data miliknya atau terikat cabang/area | Scoped by branch/area atau creator |

**Implementasi:**
- BE: Model scopes `forUser($user)` dan policies enforce scope.
- BE: Query builder filter by `user_id`, `cabang_id`, atau `area_id` sesuai role.

---

## Konflik dan Gap yang Teridentifikasi (sebelum revisi ini)

### ❌ Konflik 1: Penggunaan Barang — Approval Workflow
- **Status sebelumnya:** Service auto-approve, tapi UI menampilkan approve/reject.
- **Solusi:** Aturan bisnis baru menegaskan **tidak ada approval**; hapus endpoint dan UI approve/reject.

### ❌ Gap 2: Endpoint Stok Tersedia
- **Status:** Rute dideklarasikan, handler belum ada.
- **Solusi:** Implementasikan controller method untuk return stok tersedia.

### ❌ Gap 3: Resource Penggunaan Barang — `user.unique_id`
- **Status:** FE cek `item.user?.unique_id`, tapi resource tidak expose field ini.
- **Solusi:** Expose `user.unique_id` di `PenggunaanBarangResource` (atau gunakan `user.id`).

### ❌ Gap 4: Manager Permission Overreach
- **Status sebelumnya:** Manager memiliki create/update/delete di beberapa modul.
- **Solusi:** Semua policies dan guards diubah agar manager hanya view (read-only) kecuali data pribadi.

---

## Checklist Implementasi

### Backend (Laravel)

- [ ] **Policies:** Update semua policy (Barang, JenisBarang, PengadaanBarang, PenggunaanBarang, User):
  - `viewAny()`: admin + manager (manager read-only).
  - `create/update/delete`: sesuai aturan per modul (umumnya admin only atau admin + user untuk transaksi).
  
- [ ] **Controllers:**
  - Hapus/deprecate method `approve()` dan `reject()` di `PenggunaanBarangController`.
  - Implementasikan `getAvailableStock()` dan `getStockForItem($id)` untuk endpoint stok.
  
- [ ] **Services:**
  - `PenggunaanBarangService`: hapus method `approve()` dan `reject()`; pertahankan `recordUsage()` (auto-approve).
  
- [ ] **Routes (`routes/api.php`):**
  - Hapus route approve/reject penggunaan barang.
  - Pastikan endpoint stok tersedia terdaftar dan mapped ke controller method yang ada.
  
- [ ] **Resources:**
  - `PenggunaanBarangResource`: expose `user.unique_id` atau `user.id` untuk FE guard.
  - `BarangResource`: sudah OK (expose `id_jenis_barang`).
  
- [ ] **Middleware/Guards:**
  - Review route middleware untuk enforce role di level routing (sudah ada via Sanctum + Spatie).

### Frontend (Vue.js)

- [ ] **Router (`src/router/`):**
  - Update route meta untuk enforce role sesuai aturan baru:
    - Jenis Barang, Barang, Pengadaan: create/edit/delete hanya admin.
    - Penggunaan Barang: create/edit hanya admin/user; manager view-only.
    - Laporan: semua role dapat akses (scope enforced BE).
  
- [ ] **Components/Pages:**
  - Hapus UI approve/reject di Penggunaan Barang (components/PenggunaanBarangTable.vue, pages/shared/PenggunaanBarang.vue).
  - Sembunyikan tombol create/edit/delete untuk manager di modul Barang, Jenis Barang, Pengadaan.
  - User: tampilkan create/edit untuk Penggunaan dan Pengadaan; view-only untuk Barang.
  
- [ ] **Stores (Pinia):**
  - `penggunaanBarangStore.js`: implement `savePenggunaan()`, perbaiki path endpoint stok ke `/stok/tersedia`.
  - Hapus action approve/reject penggunaan barang.
  
- [ ] **Permission Checks:**
  - Update kondisional `canEdit`, `canDelete`, `canApprove` berdasarkan role dan aturan baru.
  - Ganti cek `item.user?.unique_id` dengan field yang di-expose resource.

---

## Catatan Tambahan

- **Manager sebagai Pengawas:** Manager tidak ikut dalam operasional create/update/delete transaksi atau master data; hanya monitoring dan laporan.
- **User Operasional:** User adalah pelaku utama transaksi (penggunaan barang, pengajuan pengadaan).
- **Admin Full Control:** Admin mengelola master data, user management, approval pengadaan, dan koreksi data.

---

**Dokumen ini harus di-review dan di-update seiring evolusi sistem.**
