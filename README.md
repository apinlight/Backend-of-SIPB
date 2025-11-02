# 📦 SIPB – Sistem Informasi dan Pencatatan Barang

> **Backend API untuk Manajemen Inventaris & Pencatatan Barang**  
> Laravel 12 | Service-Oriented Architecture | RESTful API

SIPB adalah API backend yang tangguh untuk aplikasi pencatatan, pengelolaan, dan pengawasan pergerakan barang dalam suatu organisasi. Dibangun dengan **Laravel 12** dan dirancang dengan **Service-Oriented Architecture**, sistem ini menyediakan endpoint yang aman, efisien, dan mudah dipelihara untuk diintegrasikan dengan frontend Vue.js.

---

## 🚀 Quick Start

```bash
# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env, then migrate
php artisan migrate --seed

# Start development server
php artisan serve  # http://localhost:8000
```

**📚 Complete documentation: [docs/INDEX.md](docs/INDEX.md)**

---

## ✨ Fitur Utama

- **Manajemen Inventaris Lengkap**: CRUD untuk Master Data (Barang, Jenis Barang, Batas Barang)
- **Siklus Hidup Barang**:
    - 📋 **Pengajuan**: Workflow permintaan barang baru
    - 📦 **Gudang**: Pencatatan stok per pengguna
    - 🔧 **Penggunaan Barang**: Auto-approve consumption tracking
- **Autentikasi & Otorisasi**: Sanctum + Spatie Permission (admin/manager/user)
- **Pelaporan & Analitik**: Endpoint laporan dengan ekspor Excel
- **Testing**: 16 automated tests dengan 100% business rules compliance

---

## 👥 Pemetaan Peran Pengguna

> **⚠️ Perhatian:** Business rules telah direvisi. Lihat [BUSINESS_RULES.md](BUSINESS_RULES.md) untuk dokumentasi lengkap.

| Peran | Deskripsi | Hak Akses |
|---|---|---|
| **Admin** | Operator pusat, full access | CRUD all data, approve pengajuan, manage users |
| **Manager** | Pengawas kantor pusat | **Read-only** global access (monitoring only) |
| **User** | Admin cabang operasional | Create pengajuan/penggunaan, view own data |

### Perubahan Penting

- ✅ **Manager sekarang read-only**: Tidak dapat create/update/delete/approve apapun
- ✅ **Penggunaan barang auto-approve**: Tidak ada workflow persetujuan (admin/manager hanya monitoring)
- ✅ **User dapat export laporan**: Laporan sendiri dapat diexport ke Excel

**Detail lengkap:** [BUSINESS_RULES.md](BUSINESS_RULES.md)

---

## 🏛️ Arsitektur

- **Service-Oriented**: Business logic di `app/Services/`
- **Thin Controllers**: Request/response handling only di `app/Http/Controllers/Api/`
- **Policies**: Authorization rules di `app/Policies/`
- **API Resources**: Standardized responses di `app/Http/Resources/`
- **Form Requests**: Validation & authorization di `app/Http/Requests/`

**Tech Stack:**
- Laravel 12 + Sanctum (stateless auth)
- Spatie Laravel Permission (role/permission management)
- MariaDB/MySQL (custom table names: tb_*)
- UUID/ULID primary keys

**Detailed architecture:** [docs/INDEX.md#architecture](docs/INDEX.md#architecture)

---

## 📖 Dokumentasi

| Dokumen | Deskripsi |
|---|---|
| **[docs/INDEX.md](docs/INDEX.md)** | 📚 Main documentation hub |
| **[BUSINESS_RULES.md](BUSINESS_RULES.md)** | 📋 Authoritative business rules |
| **[dokumentasi-api.md](dokumentasi-api.md)** | 🔌 Complete API reference |
| **[TEST_REPORT.md](TEST_REPORT.md)** | ✅ Latest test results |
| **[AGENT.md](AGENT.md)** | 🤖 Development agent guide |

### Archived Documentation

Dokumentasi historis dipindahkan ke `docs/archive/` untuk menjaga kebersihan root directory. Termasuk API verification reports, implementation summaries, dan change logs.

---

## 🧪 Testing

```bash
# Run all tests (16 tests, 71 assertions)
php artisan test

# Run specific test
php artisan test tests/Feature/Auth/LoginTest.php

# Format code
vendor/bin/pint
```

**Latest Results:** ✅ 16/16 tests passed (100% business rules compliance)  
**Details:** [TEST_REPORT.md](TEST_REPORT.md)

---

## 🚀 Deployment

**Prerequisites:**
- PHP 8.2+
- Composer
- MariaDB/MySQL
- Nginx (recommended)

**Production Checklist:**
1. Set `APP_ENV=production`, `APP_DEBUG=false`
2. Configure production database
3. Run `composer install --no-dev --optimize-autoloader`
4. Cache config/routes: `php artisan config:cache && php artisan route:cache`
5. Configure CORS for frontend origin
6. Setup queue worker (supervisor)

**Full deployment guide:** [docs/INDEX.md#deployment](docs/INDEX.md#deployment)

---

## 🔗 Integration

**Frontend:** Separate Vue.js 3 SPA di `frontend/`  
**Base API URL:** `/api/v1`  
**Auth:** Sanctum Bearer token required untuk protected endpoints

**Example Request:**
```http
GET /api/v1/barang HTTP/1.1
Host: localhost:8000
Authorization: Bearer {token}
Accept: application/json
```

**API Documentation:** [dokumentasi-api.md](dokumentasi-api.md)

---

## 📝 Development

**Common Commands:**
```bash
php artisan serve              # Dev server
php artisan test               # Run tests
vendor/bin/pint                # Format code
php artisan migrate:fresh --seed  # Reset DB
php artisan queue:listen --tries=1  # Process queue
```

**Contributing:**
- Follow PSR-12 standards
- Run `vendor/bin/pint` before commit
- Write tests for new features
- Update documentation as needed

**Development guide:** [docs/INDEX.md#development-guide](docs/INDEX.md#development-guide)

---

## 📄 License

[Add your license here]

---

**Last Updated:** November 3, 2025 | Laravel 12.x | PHP 8.2+  
**Documentation:** [docs/INDEX.md](docs/INDEX.md) | **API:** [dokumentasi-api.md](dokumentasi-api.md)