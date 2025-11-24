# AGENT.md - SIPB Laravel Project Guide

## Commands
- **Test**: `vendor/bin/phpunit` or `php artisan test` 
- **Single Test**: `vendor/bin/phpunit tests/Feature/Auth/LoginTest.php`
- **Format Code**: `vendor/bin/pint` (Laravel Pint)
- **Build/Check**: `composer install && php artisan config:cache && php artisan route:cache`
- **Dev Server**: `php artisan serve` (runs on :8000)
- **Queue**: `php artisan queue:listen --tries=1`
- **Database**: `php artisan migrate`, `php artisan db:seed`

## Architecture
- **Framework**: Laravel 12 API with Vue.js frontend
- **Auth**: Laravel Sanctum (stateless Bearer tokens)
- **Permissions**: Spatie Laravel Permission (admin/manager/user roles)
- **Database**: MariaDB with custom table names (tb_users, tb_barang, etc.)
- **Primary Keys**: UUID/ULID strings, not auto-increment integers
- **Main Models**: User, Barang, Pengajuan, JenisBarang, BatasBarang, DetailPengajuan
- **Key Controllers**: UserController, BarangController, PengajuanController in app/Http/Controllers/Api/

## Code Style
- **Namespace**: PSR-4 autoloading, App\ namespace
- **Authorization**: Use `$this->authorize()` in controllers + Policies
- **Role Checks**: `$user->hasRole(['admin', 'manager'])` via Spatie
- **Validation**: Inline `$request->validate()` arrays with Rule::unique patterns
- **Resources**: API Resources for responses, not direct model returns
- **Security**: CORS via FRONTEND_URL, scope filtering per role, Hash::make for passwords
- **Comments**: Use ✅ prefix for security/critical sections, minimal other comments
- **Error Handling**: Return JSON with status/message structure, use proper HTTP codes
