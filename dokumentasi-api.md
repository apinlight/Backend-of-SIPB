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

### Endpoint Autentikasi Terproteksi (tambahan)
- `POST /logout`: Logout dari sesi saat ini.
- `POST /logout-all`: Logout semua sesi.
- `GET /me`: Profil singkat user (token).
- `POST /refresh-token`: Refresh token akses.
- `POST /validate-token`: Validasi token aktif.
- `GET /sessions`: Daftar sesi aktif.
- `DELETE /sessions/{tokenId}`: Revoke satu sesi.
- `DELETE /sessions/expired`: Bersihkan sesi kadaluarsa.
- `POST /change-password`: Ganti password akun sendiri.

---

## Endpoint Utama

### Users
- `GET /users`: (Admin, Manager) Mendapatkan daftar semua pengguna.
- `POST /users`: (Admin) Membuat pengguna baru.
- `GET /users/{user}`: (Admin, Manager) Mendapatkan detail satu pengguna.
- `PUT /users/{user}`: (Admin) Memperbarui pengguna.
- `DELETE /users/{user}`: (Admin) Menghapus pengguna.

**Aksi Tambahan:**
- `POST /users/{user}/toggle-status`: **(Admin)** Mengaktifkan/menonaktifkan pengguna.
- `POST /users/{user}/reset-password`: **(Admin)** Mereset password pengguna.
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
- `POST /gudang`: **(Admin)** Menambahkan stok baru.
- `GET /gudang/{gudang}`: Melihat detail stok spesifik.
- `PUT /gudang/{gudang}`: **(Admin)** Memperbarui catatan stok.
- `DELETE /gudang/{gudang}`: **(Admin)** Menghapus catatan stok.

**Aksi Tambahan:**
- `POST /gudang/{unique_id}/{id_barang}/adjust-stock`: (Admin) Melakukan penyesuaian manual pada jumlah stok.

### Pengajuan (Procurement)
- `GET /pengajuan`: Melihat daftar pengajuan (sesuai hak akses).
- `POST /pengajuan`: Membuat pengajuan baru.
- `GET /pengajuan/{pengajuan}`: Melihat detail pengajuan (sesuai hak akses).
- `PUT /pengajuan/{pengajuan}`: **(Admin)** Memperbarui status pengajuan (approve/reject).
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
- `POST /penggunaan-barang/{penggunaan_barang}/approve`: **(Admin/Manager)** Menyetujui permintaan penggunaan.
- `POST /penggunaan-barang/{penggunaan_barang}/reject`: **(Admin/Manager)** Menolak permintaan penggunaan.
- `GET /penggunaan-barang/pending/approvals`: (Admin/Manager) Melihat daftar permintaan yang menunggu persetujuan.

### Stok (ketersediaan)
- `GET /stok/tersedia`: Mendapatkan daftar stok yang tersedia untuk pengguna saat ini.
- `GET /stok/tersedia/{id_barang}`: Mendapatkan jumlah stok spesifik yang tersedia.

### Utilitas Publik
- `GET /health`: Cek status API.
- `GET /online`: Ping API sederhana.

### Laporan (Reports)
- `GET /laporan/summary`: (Admin/Manager) Laporan ringkasan umum.
- `GET /laporan/barang`: (Admin/Manager) Laporan analisis per barang.
- `GET /laporan/pengajuan`: (Admin/Manager) Laporan analisis pengajuan.
- `GET /laporan/cabang`: (Admin/Manager) Laporan perbandingan antar cabang.
- `GET /laporan/penggunaan`: (Admin/Manager) Laporan analisis penggunaan barang.
- `GET /laporan/stok`: (Admin/Manager) Laporan status inventaris.
- `GET /laporan/stok-summary`: (Admin/Manager) Laporan ringkasan stok per barang.

**Ekspor Laporan:**
- `GET /laporan/export/summary`: (Admin/Manager) Export laporan ringkasan ke Excel.
- `GET /laporan/export/barang`: (Admin/Manager) Export laporan barang ke Excel.
- `GET /laporan/export/pengajuan`: (Admin/Manager) Export laporan pengajuan ke Excel.
- `GET /laporan/export/penggunaan`: (Admin/Manager) Export laporan penggunaan ke Excel.
- `GET /laporan/export/stok`: (Admin/Manager) Export laporan stok ke Excel.
- `GET /laporan/export/all`: (Admin/Manager) Export semua laporan dalam satu file Excel.

### Global Settings
- `GET /global-settings`: (Admin) Melihat semua pengaturan global.
- `GET /global-settings/monthly-limit`: (Admin) Melihat batas pengajuan bulanan.
- `PUT /global-settings/monthly-limit`: (Admin) Mengubah batas pengajuan bulanan.