# 📦 SIPB – Sistem Informasi dan Pencatatan Barang

SIPB adalah sebuah API backend yang tangguh untuk aplikasi pencatatan, pengelolaan, dan pengawasan pergerakan barang dalam suatu organisasi. Dibangun dengan **Laravel 12** dan dirancang dengan **Service-Oriented Architecture**, sistem ini menyediakan endpoint yang aman, efisien, dan mudah dipelihara untuk diintegrasikan dengan berbagai frontend, seperti Vue.js.

---

## ✨ Fitur Utama

- **Manajemen Inventaris Lengkap**: CRUD untuk Master Data (`Barang`, `Jenis Barang`, `Batas Barang`).
- **Siklus Hidup Barang**:
    - **Pengajuan (Procurement)**: Alur kerja untuk meminta barang baru.
    - **Gudang (Stock Management)**: Pencatatan stok per pengguna.
    - **Penggunaan Barang (Consumption)**: Alur kerja untuk mencatat pemakaian barang.
- **Manajemen Pengguna & Peran**: Sistem autentikasi berbasis token (Laravel Sanctum).
- **Pelaporan & Analitik**: Endpoint terdedikasi untuk menghasilkan laporan agregat.
- **Ekspor ke Excel**: Kemampuan untuk mengekspor laporan ke format `.xlsx`.

### Pemetaan Peran Pengguna

Sistem ini dirancang untuk tiga tipe pengguna di dunia nyata, yang dipetakan ke peran teknis sebagai berikut:

1.  **Admin Pusat (`admin`)**:
    - **Operator Utama:** Bertanggung jawab atas pengelolaan stok pusat dan semua operasi sistem.
    - **Pemberi Persetujuan Tunggal:** Merupakan satu-satunya peran yang dapat **menyetujui atau menolak** `Pengajuan` barang dari semua cabang.
    - **Akses Penuh:** Memiliki akses penuh ke semua laporan dan manajemen pengguna.

2.  **Manajer (`manager`)**:
    - **Pemantau Pusat:** Berada di kantor pusat dan memiliki hak akses untuk **melihat dan memantau** data.
    - **Akses Laporan Global:** Dapat melihat dan mengekspor laporan dari **seluruh cabang** untuk keperluan analisis.
    - **Read-Only:** Tidak memiliki hak untuk melakukan operasi seperti membuat atau menyetujui pengajuan.

3.  **Admin Cabang (`user`)**:
    - **Operator Cabang:** Pengguna operasional harian di setiap kantor cabang.
    - **Membuat Permintaan:** Membuat `Pengajuan` barang baru berdasarkan kebutuhan cabangnya.
    - **Melaporkan Penggunaan:** Melaporkan `Penggunaan Barang` untuk memperbarui data stok di cabangnya.

---

## 🏛️ Pola Arsitektur

Aplikasi ini telah direfaktor secara ekstensif untuk mengikuti praktik terbaik dalam pengembangan perangkat lunak modern:

- **Service-Oriented Architecture**: Semua logika bisnis yang kompleks dienkapsulasi dalam **Service Class**.
- **Thin Controllers**: Controller hanya bertanggung jawab untuk menangani request dan response HTTP.
- **Form Requests**: Semua validasi dan otorisasi ditangani oleh kelas Form Request.
- **Policies**: Semua aturan otorisasi terpusat di dalam kelas Policy.
- **Lean Models**: Model Eloquent difokuskan pada representasi data, relasi, dan query scope.

---

## ⚙️ Teknologi & Konsep

- **Backend**: Laravel 12
- **Autentikasi**: Laravel Sanctum (Stateless API)
- **Otorisasi**: Spatie Laravel Permission & Laravel Policies
- **Database**: MariaDB / MySQL
- **Primary Keys**: ULID/UUID
- **API Response**: Standarisasi menggunakan Laravel API Resources.

---

## ✍️ Panduan Instalasi

1.  **Clone repository:**
    ```bash
    git clone [https://github.com/apinlight/Backend-of-SIPB.git](https://github.com/apinlight/Backend-of-SIPB.git)
    cd Backend-of-SIPB
    ```
2.  **Install dependencies:** `composer install`
3.  **Setup file environment:** `cp .env.example .env`
4.  **Generate key aplikasi:** `php artisan key:generate`
5.  **Konfigurasi `.env`:** Atur koneksi database dan `FRONTEND_URL`.
6.  **Jalankan migrasi dan seeder:** `php artisan migrate --seed`
7.  **Jalankan server:** `php artisan serve`

---

## ℹ️ Catatan

Dokumentasi API lengkap tersedia di file `dokumentasi-api.md`.