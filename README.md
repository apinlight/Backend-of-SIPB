# 📦 SIPB – Sistem Informasi dan Pencatatan Barang

SIPB adalah aplikasi berbasis web untuk mencatat, mengelola, dan mengawasi pergerakan barang dalam suatu sistem internal organisasi. Dibangun dengan **Laravel** (backend API) dan **Vue.js** (frontend), serta mendukung autentikasi menggunakan **Laravel Sanctum** dan manajemen peran dengan **Spatie Laravel Permission**.

---

## 🔐 Fitur Autentikasi & Role-Based Access

- **Login & Logout** (Bearer Token, stateless, CORS handled)
- **Role Pengguna:**
  - **Admin**: akses penuh ke seluruh data
  - **User**: mengelola data dan pengajuannya sendiri
  - **Manager**: melihat data dari semua user dalam `branch_name` yang sama

---

## 🧩 Modul Utama

### 1. 📁 Database (Hanya Admin)
- `User` (CRUD)
- `Barang` (CRUD)
- `Jenis Barang` (CRUD)
- `Batas Barang` (CRUD)
- `Batas Pengajuan` (CRUD)

### 2. ✍️ Pengajuan
- **Admin**: approval dan monitoring pengajuan
- **User**: tambah, edit, dan hapus pengajuan miliknya
- **Manager**: hanya dapat melihat pengajuan user satu cabang

### 3. 📄 Riwayat
- **Admin**: melihat semua riwayat atau filter per user
- **User**: hanya melihat riwayat sendiri
- **Manager**: melihat riwayat semua user dalam satu `branch_name`

---

## ⚙️ Teknologi yang Digunakan

- **Backend**: Laravel 12
  - Laravel Sanctum (stateless API auth)
  - Spatie Laravel Permission (role & permission)
  - API Resource (standarisasi respons)
  - UUID/ULID sebagai primary key
- **Frontend**: Vue.js 3 (Vite), Axios
- **Database**: MariaDB
- **Security**:
  - CORS disesuaikan (`FRONTEND_URL`)
  - Password terenkripsi
  - Middleware, policy, dan guards untuk validasi akses

---

## ✅ Status
Fitur login/logout, role-based access, dan semua endpoint API utama sudah berjalan dengan baik.

---

Jika butuh akses demo atau dokumentasi API, bisa hubungi developer.
