# Dokumentasi API - SIPB

## Base URL
Semua endpoint diawali dengan base URL berikut:

```
/api/v1
```

---

## Autentikasi

Semua endpoint, kecuali yang ditandai publik, memerlukan header `Authorization: Bearer <token>`.

### Endpoint Autentikasi Publik
- `POST /register`: Registrasi pengguna baru.
- `POST /login`: Login untuk mendapatkan token.
- `POST /forgot-password`: Mengirim link reset password.
- `POST /reset-password`: Mereset password dengan token.

---

## Endpoint Utama

### Users
- `GET /users`: (Admin) Mendapatkan daftar semua pengguna.
- `POST /users`: (Admin) Membuat pengguna baru.
- `GET /users/{user}`: (Admin) Mendapatkan detail satu pengguna.
- `PUT /users/{user}`: (Admin) Memperbarui pengguna.
- `DELETE /users/{user}`: (Admin) Menghapus pengguna.

**Aksi Tambahan:**
- `POST /users/{user}/toggle-status`: (Admin/Manager) Mengaktifkan/menonaktifkan pengguna.
- `POST /users/{user}/reset-password`: (Admin/Manager) Mereset password pengguna.
- `GET /profile`: Mendapatkan profil pengguna yang sedang login.
- `PUT /profile`: Memperbarui profil pengguna yang sedang login.

### Barang (Items)
- `GET /barang`: Mendapatkan daftar semua barang.
- `POST /barang`: (Admin) Membuat barang baru.
- `GET /barang/{barang}`: Mendapatkan detail satu barang.
- `PUT /barang/{barang}`: (Admin) Memperbarui barang.
- `DELETE /barang/{barang}`: (Admin) Menghapus barang.

### Jenis Barang (Item Categories)
- `GET /jenis-barang`: Mendapatkan daftar kategori barang.
- `POST /jenis-barang`: (Admin) Membuat kategori baru.
- `GET /jenis-barang/{jenis_barang}`: Mendapatkan detail satu kategori.
- `PUT /jenis-barang/{jenis_barang}`: (Admin) Memperbarui kategori.
- `DELETE /jenis-barang/{jenis_barang}`: (Admin) Menghapus kategori.

**Aksi Tambahan:**
- `GET /jenis-barang/list/active`: Mendapatkan daftar kategori yang aktif saja.
- `POST /jenis-barang/{jenis_barang}/toggle-status`: (Admin) Mengubah status aktif/nonaktif kategori.

### Batas Barang (Item Limits)
- `GET /batas-barang`: (Admin) Mendapatkan daftar semua batas barang.
- `POST /batas-barang`: (Admin) Membuat batas barang baru.
- `GET /batas-barang/{batas_barang}`: (Admin) Mendapatkan detail satu batas barang.
- `PUT /batas-barang/{batas_barang}`: (Admin) Memperbarui batas barang.
- `DELETE /batas-barang/{batas_barang}`: (Admin) Menghapus batas barang.

**Aksi Tambahan:**
- `POST /batas-barang/check-allocation`: Memeriksa apakah permintaan barang baru akan melebihi batas.

### Gudang (Stock)
- `GET /gudang`: Melihat daftar stok (sesuai hak akses).
- `POST /gudang`: (Admin/Manager) Menambahkan stok baru.
- `GET /gudang/{unique_id}/{id_barang}`: Melihat detail stok spesifik.
- `PUT /gudang/{unique_id}/{id_barang}`: Memperbarui catatan stok.
- `DELETE /gudang/{unique_id}/{id_barang}`: Menghapus catatan stok.

**Aksi Tambahan:**
- `POST /gudang/{unique_id}/{id_barang}/adjust-stock`: (Admin) Melakukan penyesuaian manual pada jumlah stok.

### Pengajuan (Procurement)
- `GET /pengajuan`: Melihat daftar pengajuan (sesuai hak akses).
- `POST /pengajuan`: Membuat pengajuan baru.
- `GET /pengajuan/{pengajuan}`: Melihat detail pengajuan.
- `PUT /pengajuan/{pengajuan}`: (Admin) Memperbarui status pengajuan (approve/reject).
- `DELETE /pengajuan/{pengajuan}`: Menghapus pengajuan (jika status memungkinkan).

**Aksi Tambahan:**
- `GET /pengajuan/info/barang`: Mendapatkan semua data yang dibutuhkan untuk halaman pembuatan pengajuan.
- `GET /pengajuan/info/barang-history/{id_barang}`: Melihat riwayat pengajuan untuk barang tertentu.

### Detail Pengajuan (Procurement Items)
- `POST /detail-pengajuan`: Menambahkan item ke pengajuan yang ada.
- `PUT /detail-pengajuan/{detail_pengajuan}`: Memperbarui jumlah/keterangan item dalam pengajuan.
- `DELETE /detail-pengajuan/{detail_pengajuan}`: Menghapus item dari pengajuan.

### Penggunaan Barang (Item Consumption)
- `GET /penggunaan-barang`: Melihat daftar penggunaan barang (sesuai hak akses).
- `POST /penggunaan-barang`: Mencatat penggunaan barang baru.
- `GET /penggunaan-barang/{penggunaan_barang}`: Melihat detail penggunaan barang.
- `PUT /penggunaan-barang/{penggunaan_barang}`: Memperbarui catatan penggunaan (jika status pending).
- `DELETE /penggunaan-barang/{penggunaan_barang}`: Menghapus catatan penggunaan.

**Aksi Tambahan:**
- `POST /penggunaan-barang/{penggunaan_barang}/approve`: (Admin/Manager) Menyetujui permintaan penggunaan.
- `POST /penggunaan-barang/{penggunaan_barang}/reject`: (Admin/Manager) Menolak permintaan penggunaan.
- `GET /penggunaan-barang/pending/approvals`: (Admin/Manager) Melihat daftar permintaan yang menunggu persetujuan.
- `GET /stok/tersedia`: Mendapatkan daftar stok yang tersedia untuk pengguna saat ini.
- `GET /stok/tersedia/{id_barang}`: Mendapatkan jumlah stok spesifik yang tersedia.

### Laporan (Reports)
- `GET /laporan/summary`: (Admin/Manager) Laporan ringkasan umum.
- `GET /laporan/barang`: (Admin/Manager) Laporan analisis per barang.
- `GET /laporan/pengajuan`: (Admin/Manager) Laporan analisis pengajuan.
- `GET /laporan/cabang`: (Admin/Manager) Laporan perbandingan antar cabang.
- `GET /laporan/penggunaan`: (Admin/Manager) Laporan analisis penggunaan barang.
- `GET /laporan/stok`: (Admin/Manager) Laporan status inventaris.
- `GET /laporan/stok-summary`: (Admin/Manager) Laporan ringkasan stok per barang.

**Ekspor Laporan:**
- `GET /laporan/export/{type}`: (Admin/Manager) Mengekspor laporan tertentu ke Excel. (`type` bisa berupa: `summary`, `barang`, `pengajuan`, `penggunaan`, `stok`).

### Global Settings
- `GET /global-settings`: (Admin) Melihat semua pengaturan global.
- `GET /global-settings/monthly-limit`: (Admin) Melihat batas pengajuan bulanan.
- `PUT /global-settings/monthly-limit`: (Admin) Mengubah batas pengajuan bulanan.

