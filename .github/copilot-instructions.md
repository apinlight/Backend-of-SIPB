# Copilot Instructions for SIMBA Backend (Laravel)

## Project Overview
- **Framework:** Laravel 12 (API only, no Blade views)
- **Frontend:** Separate Vue.js app (see `frontend/`)
- **Architecture:** Service-Oriented, thin controllers, business logic in `app/Services/`
- **Auth:** Laravel Sanctum (stateless Bearer tokens)
- **Permissions:** Spatie Laravel Permission (roles: admin, manager, user)
- **Database:** MariaDB/MySQL, custom table names (e.g., `tb_users`, `tb_barang`), UUID/ULID primary keys

## Key Patterns & Conventions
- **Controllers:** Only handle HTTP request/response, delegate logic to Service classes (see `app/Http/Controllers/Api/`)
- **Services:** All business logic in `app/Services/`
- **Validation:** Use Form Request classes for validation/authorization, or inline `$request->validate()`
- **Authorization:** Use `$this->authorize()` in controllers, policies in `app/Policies/`, and Spatie role checks (`$user->hasRole()`)
- **API Responses:** Always use Laravel API Resources for output, never return models directly
- **Error Handling:** Return JSON with `status` and `message`, use proper HTTP codes
- **Security:** CORS restricted to frontend, passwords hashed with `Hash::make`, scope filtering by role
- **Comments:** Use `✅` prefix for security/critical code, otherwise keep comments minimal

## Developer Workflows
- **Install:** `composer install`
- **Dev Server:** `php artisan serve` (default: http://localhost:8000)
- **Test:** `php artisan test` or `vendor/bin/phpunit`
- **Single Test:** `vendor/bin/phpunit tests/Feature/Auth/LoginTest.php`
- **Format:** `vendor/bin/pint`
- **Build/Cache:** `php artisan config:cache && php artisan route:cache`
- **Migrate/Seed:** `php artisan migrate`, `php artisan db:seed`
- **Queue:** `php artisan queue:listen --tries=1`

## API & Integration
- **Base URL:** `/api/v1` (see `dokumentasi-api.md` for endpoints)
- **Token Auth:** All endpoints (except public auth) require `Authorization: Bearer <token>`
- **Frontend Integration:** Communicate via REST, expect JSON responses

## Notable Files & Directories
- `app/Services/` — Business logic
- `app/Http/Controllers/Api/` — API controllers
- `app/Models/` — Eloquent models (lean, focus on data/relations)
- `app/Policies/` — Authorization rules
- `routes/api.php` — Main API routes
- `dokumentasi-api.md` — Full API documentation

## Project-Specific Notes
- **Primary keys** are always UUID/ULID, never auto-increment
- **Table names** are custom (e.g., `tb_users`), not Laravel defaults
- **Role/permission logic** is enforced at both controller and policy level
- **Excel export** endpoints available for reports

---
For more, see `README.md`, `AGENT.md`, and `dokumentasi-api.md`.
