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

## ✍️ Cara Setup Proyek (Installation & Environment)

1. **Clone repository:**
   ```bash
   git clone https://github.com/apinlight/Backend-of-SIPB.git
   cd Backend-of-SIPB
   ```
2. **Install dependencies:**
   ```bash
   composer install
   ```
3. **Copy dan atur file enviroment:**
   ```bash
   cp .env.example .env
   ```
4. **Generate key:**
   ```bash
   php artisan key:generate
   ```
5. **Konfigurasi `.env`:**
   * Atur koneksi database:
     ```bash
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=sipb
     DB_USERNAME=root
     DB_PASSWORD=
     ```
   * Atur URL frontend untuk CORS:
     ```bash
     FRONTEND_URL=http://localhost:5173
     ```
6. **Jalankan migrasi dan seeder (opsional):**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
7. **Jalankan server:**
  ```bash
  php artisan serve
  ```

---

## ✅ Status
Fitur login/logout, role-based access, dan semua endpoint API utama sudah berjalan dengan baik.

---

## ℹ️ Catatan
Untuk saat ini belum tersedia link demo production. Semua pengujian dilakukan di environment development.
Jika butuh akses demo atau dokumentasi API, bisa hubungi developer.
