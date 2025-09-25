# 📦 SIPB – Sistem Informasi dan Pencatatan Barang

SIPB adalah sebuah API backend yang tangguh untuk aplikasi pencatatan, pengelolaan, dan pengawasan pergerakan barang dalam suatu organisasi. Dibangun dengan **Laravel 12** dan dirancang dengan **Service-Oriented Architecture**, sistem ini menyediakan endpoint yang aman, efisien, dan mudah dipelihara untuk diintegrasikan dengan berbagai frontend, seperti Vue.js.

---

## ✨ Fitur Utama

- **Manajemen Inventaris Lengkap**: CRUD untuk Master Data (`Barang`, `Jenis Barang`, `Batas Barang`).
- **Siklus Hidup Barang**:
    - **Pengajuan (Procurement)**: Alur kerja untuk meminta barang baru, lengkap dengan validasi batas stok dan limit bulanan.
    - **Gudang (Stock Management)**: Pencatatan stok per pengguna dengan operasi yang aman secara transaksional.
    - **Penggunaan Barang (Consumption)**: Alur kerja untuk mencatat pemakaian barang, lengkap dengan alur persetujuan (approval).
- **Manajemen Pengguna & Peran**: Sistem autentikasi berbasis token (Laravel Sanctum) dengan tiga peran utama.
- **Pelaporan & Analitik**: Endpoint terdedikasi untuk menghasilkan laporan agregat (summary, stok, penggunaan, dll.) dengan filter dinamis.
- **Ekspor ke Excel**: Kemampuan untuk mengekspor semua laporan utama ke dalam format file `.xlsx` yang terformat dengan baik.

### Pemetaan Peran Pengguna

Sistem ini dirancang untuk tiga tipe pengguna di dunia nyata, yang dipetakan ke peran teknis sebagai berikut:

1.  **Admin Pusat (`admin`)**:
    - Bertanggung jawab atas pengelolaan stok pusat.
    - Menyetujui atau menolak pengajuan barang dari semua cabang.
    - Memiliki akses penuh ke semua laporan dan manajemen pengguna.

2.  **Manajer (`manager`)**:
    - Mengawasi semua aktivitas di dalam cabangnya (`branch_name`).
    - Dapat menyetujui atau menolak pengajuan yang dibuat oleh pengguna di cabangnya.
    - Memiliki akses ke laporan tingkat cabang dan keseluruhan.

3.  **Admin Cabang (`user`)**:
    - Pengguna operasional harian di setiap cabang.
    - Membuat `Pengajuan` barang baru berdasarkan kebutuhan.
    - Melaporkan `Penggunaan Barang` untuk memperbarui stok cabang.

---

## 🏛️ Pola Arsitektur

Aplikasi ini telah direfaktor secara ekstensif untuk mengikuti praktik terbaik dalam pengembangan perangkat lunak modern:

- **Service-Oriented Architecture**: Semua logika bisnis yang kompleks (misalnya, proses persetujuan, penyesuaian stok, validasi) dienkapsulasi dalam **Service Class** yang terdedikasi.
- **Thin Controllers**: Controller hanya bertanggung jawab untuk menangani request dan response HTTP, mendelegasikan semua pekerjaan ke service. Ini membuat controller bersih, mudah dibaca, dan fokus.
- **Form Requests**: Semua validasi dan otorisasi untuk request `POST` dan `PUT` ditangani oleh kelas **Form Request** khusus, memisahkan validasi dari logika controller.
- **Policies**: Semua aturan otorisasi (siapa yang dapat melihat, membuat, atau mengubah data) terpusat di dalam kelas **Policy**, menyediakan satu sumber kebenaran untuk perizinan.
- **Lean Models**: Model Eloquent difokuskan pada representasi data, relasi, dan query scope, sementara semua logika bisnis yang kompleks telah dipindahkan ke service.

---

## ⚙️ Teknologi & Konsep

- **Backend**: Laravel 12
- **Autentikasi**: Laravel Sanctum (Stateless API)
- **Otorisasi**: Spatie Laravel Permission (Roles & Permissions) & Laravel Policies
- **Database**: MariaDB / MySQL
- **Primary Keys**: ULID/UUID untuk kunci utama yang tidak berurutan dan aman.
- **API Response**: Standarisasi menggunakan Laravel API Resources.

---

## ✍️ Panduan Instalasi

1.  **Clone repository:**
    ```bash
    git clone [https://github.com/apinlight/Backend-of-SIPB.git](https://github.com/apinlight/Backend-of-SIPB.git)
    cd Backend-of-SIPB
    ```
2.  **Install dependencies:**
    ```bash
    composer install
    ```
3.  **Setup file environment:**
    ```bash
    cp .env.example .env
    ```
4.  **Generate key aplikasi:**
    ```bash
    php artisan key:generate
    ```
5.  **Konfigurasi `.env`:**
    - Atur koneksi database (DB_DATABASE, DB_USERNAME, DB_PASSWORD).
    - Atur URL frontend untuk CORS: `FRONTEND_URL=http://localhost:5173`
6.  **Jalankan migrasi dan seeder:**
    ```bash
    php artisan migrate --seed
    ```
7.  **Jalankan server:**
    ```bash
    php artisan serve
    ```

---

## ℹ️ Catatan

Dokumentasi API lengkap tersedia di file `dokumentasi-api.md`.