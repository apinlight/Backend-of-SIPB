# 📚 SIPB Backend Documentation

**Sistem Informasi dan Pencatatan Barang (SIPB)**  
Laravel 12 API Backend

---

## 📋 Table of Contents

1. [Quick Start](#quick-start)
2. [Project Overview](#project-overview)
3. [Business Rules](#business-rules)
4. [API Documentation](#api-documentation)
5. [Development Guide](#development-guide)
6. [Testing](#testing)
7. [Deployment](#deployment)

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- MariaDB/MySQL
- Node.js (for asset compilation, optional)

### Installation

```bash
# Clone repository
git clone <repository-url>
cd backend

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_DATABASE=sipb
# DB_USERNAME=root
# DB_PASSWORD=

# Run migrations and seeders
php artisan migrate
php artisan db:seed

# Start development server
php artisan serve
```

### Common Commands

```bash
# Development
php artisan serve                # Start dev server (http://localhost:8000)

# Testing
php artisan test                 # Run all tests
vendor/bin/phpunit              # Alternative test runner
vendor/bin/pint                 # Format code (Laravel Pint)

# Database
php artisan migrate             # Run migrations
php artisan db:seed             # Run seeders
php artisan migrate:fresh --seed # Fresh database

# Cache
php artisan config:cache        # Cache configuration
php artisan route:cache         # Cache routes
php artisan cache:clear         # Clear application cache

# Queue
php artisan queue:listen --tries=1  # Process queue jobs
```

---

## 📖 Project Overview

### Architecture

- **Framework:** Laravel 12 (API only, no Blade views)
- **Frontend:** Separate Vue.js 3 SPA (see `frontend/`)
- **Authentication:** Laravel Sanctum (stateless Bearer tokens)
- **Authorization:** Spatie Laravel Permission (roles: admin, manager, user)
- **Database:** MariaDB/MySQL with custom table names (tb_users, tb_barang, etc.)
- **Primary Keys:** UUID/ULID (no auto-increment)

### Key Patterns

- **Controllers:** Thin controllers, delegate logic to Service classes
- **Services:** Business logic in `app/Services/`
- **Policies:** Authorization rules in `app/Policies/`
- **Resources:** API responses via Laravel API Resources
- **Validation:** Form Request classes or inline validation
- **Error Handling:** JSON responses with status/message, proper HTTP codes

### Directory Structure

```
app/
├── Exports/              # Excel export classes
├── Http/
│   ├── Controllers/Api/  # API controllers
│   ├── Requests/        # Form request validation
│   └── Resources/       # API resource transformers
├── Models/              # Eloquent models
├── Policies/            # Authorization policies
└── Services/            # Business logic services

config/                  # Configuration files
database/
├── migrations/          # Database migrations
├── seeders/            # Database seeders
└── factories/          # Model factories

routes/
├── api.php             # API routes (/api/v1)
├── auth.php            # Authentication routes
├── console.php         # Console commands
└── web.php             # Web routes (minimal)

tests/
├── Feature/            # Feature tests
└── Unit/               # Unit tests

docs/                   # Documentation (this folder)
```

---

## 📋 Business Rules

**→ See [BUSINESS_RULES.md](BUSINESS_RULES.md) for complete business rules documentation.**

### Role Summary

| Role | Description | Permissions |
|---|---|---|
| **Admin** | Full system access | CRUD all entities, manage users, approve requests |
| **Manager** | Kantor pusat oversight | Read-only access to all data (monitoring) |
| **User** | Operational staff | Create requests, record usage, view own data |

### Key Rules

1. **Manager Role:**
   - Read-only access (no create/update/delete except own profile)
   - Global visibility (can view all branches)
   - Cannot create/approve pengajuan or penggunaan

2. **Penggunaan Barang:**
   - Auto-approved on creation
   - Stock decremented immediately
   - No approval workflow (manager/admin oversight only)

3. **Data Scoping:**
   - Admin: all data
   - Manager: all data (read-only)
   - User: own data or branch-scoped

---

## 🔌 API Documentation

**→ See [dokumentasi-api.md](dokumentasi-api.md) for complete API reference.**

### Base URL

```
http://localhost:8000/api/v1
```

### Authentication

All protected endpoints require Sanctum Bearer token:

```http
Authorization: Bearer {token}
```

### Quick Reference

#### Authentication
- `POST /auth/login` - Login
- `POST /auth/logout` - Logout
- `POST /auth/register` - Register (if enabled)

#### Barang (Items)
- `GET /barang` - List items
- `POST /barang` - Create item (admin only)
- `GET /barang/{id}` - Get item
- `PUT /barang/{id}` - Update item (admin only)
- `DELETE /barang/{id}` - Delete item (admin only)

#### Penggunaan Barang (Usage)
- `GET /penggunaan-barang` - List usage (scoped by role)
- `POST /penggunaan-barang` - Record usage (auto-approved)
- `GET /penggunaan-barang/{id}` - Get usage
- `PUT /penggunaan-barang/{id}` - Update usage (admin or owner)

#### Stock
- `GET /stok/tersedia` - Get available stock (scoped by role)
- `GET /stok/tersedia/{id_barang}` - Get stock for specific item

#### Laporan (Reports)
- `GET /laporan/penggunaan` - Usage report
- `GET /laporan/export/penggunaan` - Export usage to Excel

**→ Full endpoint list with request/response examples: [dokumentasi-api.md](dokumentasi-api.md)**

---

## 💻 Development Guide

### Code Style

**PSR-12 Compliance** with Laravel conventions. Use `vendor/bin/pint` to format.

### Controller Pattern

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BarangService;
use Illuminate\Http\JsonResponse;

class BarangController extends Controller
{
    public function __construct(
        protected BarangService $barangService
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Barang::class);
        
        $items = $this->barangService->getAll();
        
        return BarangResource::collection($items)->response();
    }
}
```

### Service Pattern

```php
<?php

namespace App\Services;

use App\Models\Barang;
use Illuminate\Support\Facades\DB;

class BarangService
{
    public function create(array $data): Barang
    {
        return DB::transaction(function () use ($data) {
            // Business logic here
            return Barang::create($data);
        });
    }
}
```

### Policy Pattern

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Barang;

class BarangPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }
}
```

### API Resource Pattern

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BarangResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id_barang' => $this->id_barang,
            'nama_barang' => $this->nama_barang,
            'jenis_barang' => JenisBarangResource::make(
                $this->whenLoaded('jenisBarang')
            ),
        ];
    }
}
```

### Database Conventions

- **Table Names:** Custom (tb_users, tb_barang)
- **Primary Keys:** UUID/ULID strings
- **Timestamps:** created_at, updated_at
- **Soft Deletes:** Where applicable

### Security Best Practices

1. **Authorization:** Always use policies + `$this->authorize()`
2. **Validation:** Form Requests or inline validation
3. **Scope Filtering:** Use model scopes (e.g., `forUser()`)
4. **CORS:** Restricted to frontend origin
5. **Rate Limiting:** Applied to API routes
6. **SQL Injection:** Use query builder/Eloquent (no raw queries without bindings)

---

## 🧪 Testing

**→ See [TEST_REPORT.md](TEST_REPORT.md) for latest test results.**

### Run Tests

```bash
# All tests
php artisan test

# Specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# With coverage (requires Xdebug)
php artisan test --coverage

# Parallel execution (faster)
php artisan test --parallel
```

### Test Structure

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class ExampleTest extends TestCase
{
    public function test_example(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/barang');
        
        $response->assertStatus(200);
    }
}
```

### Current Test Status

- ✅ **16 tests passed** (71 assertions)
- ✅ Auth tests: 9 passed
- ✅ CORS tests: 3 passed
- ✅ Export tests: 2 passed
- ✅ Pengajuan tests: 2 passed

---

## 🚀 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false` in `.env`
- [ ] Configure production database
- [ ] Set strong `APP_KEY`
- [ ] Configure CORS allowed origins
- [ ] Run `composer install --no-dev --optimize-autoloader`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Setup queue worker (supervisor)
- [ ] Configure web server (Nginx recommended)

### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name api.example.com;
    root /var/www/sipb/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Queue Worker (Supervisor)

```ini
[program:sipb-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sipb/backend/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sipb/backend/storage/logs/worker.log
```

### Environment Variables

Key variables to configure in production `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.example.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=sipb_production
DB_USERNAME=sipb_user
DB_PASSWORD=<strong-password>

SANCTUM_STATEFUL_DOMAINS=example.com
SESSION_DOMAIN=.example.com

FRONTEND_URL=https://example.com

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
```

---

## 📚 Additional Documentation

- **[BUSINESS_RULES.md](BUSINESS_RULES.md)** — Complete business rules reference
- **[dokumentasi-api.md](dokumentasi-api.md)** — Full API documentation
- **[TEST_REPORT.md](TEST_REPORT.md)** — Latest test results
- **[AGENT.md](AGENT.md)** — Development agent guide
- **[README.md](README.md)** — Project readme

### Archived Documentation

Older/redundant documentation moved to `docs/archive/`:
- API verification reports
- Implementation summaries
- Historical change logs

---

## 🤝 Contributing

1. Follow PSR-12 coding standards
2. Run `vendor/bin/pint` before committing
3. Write tests for new features
4. Update documentation as needed
5. Use conventional commit messages

---

## 📄 License

[Add your license here]

---

**Last Updated:** November 3, 2025  
**Laravel Version:** 12.x  
**PHP Version:** 8.2+
