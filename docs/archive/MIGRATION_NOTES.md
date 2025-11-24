# Database Seeders & Factories Update Summary

## Overview
Updated all seeders and factories to support the new Cabang entity architecture where branches are first-class entities separate from users.

## New Files Created

### 1. CabangSeeder.php
- **Purpose:** Seeds initial branch (Cabang) records
- **Seeds:**
  - CABANG001: Pusat (Head office)
  - CABANG002: South Branch
  - CABANG003: North Branch
  - CABANG004: East Branch
  - CABANG005: West Branch
  - CABANG006: Central Branch

### 2. CabangFactory.php
- **Purpose:** Factory for generating test Cabang instances
- **Fields:** 
  - `id_cabang`: ULID
  - `nama_cabang`: Faker city + " Branch"

## Updated Files

### DatabaseSeeder.php
**Changes:**
1. Added `CabangSeeder::class` call before user creation (step 3)
2. Updated Admin (ADMIN001):
   - Removed: `branch_name => 'Head Office'`
   - Added: `id_cabang => 'CABANG001'`
3. Updated Manager (MANAGER001):
   - Removed: `branch_name => 'South Branch'`
   - Added: `id_cabang => 'CABANG001'`
4. Updated User001:
   - Removed: `branch_name => 'South Branch'`
   - Added: `id_cabang => 'CABANG002'`
5. Updated branch users array (USR1001-USR1005):
   - Removed: `branch_name` field
   - Added: `id_cabang` with corresponding CABANG IDs
6. Updated Gudang seeding:
   - Changed from `unique_id => $user->unique_id` to `id_cabang => $user->id_cabang`
   - Added separate Gudang seeding for USER001
7. Renumbered steps after adding CabangSeeder

**Seeding Order:**
1. Roles & Permissions
2. Global Settings
3. **Cabang** (NEW)
4. Base Accounts (Admin, Manager, User001)
5. Branch Users (USR1001-USR1005)
6. Jenis Barang & Barang
7-8. Pengajuan & Gudang for branch users
9. Batas Barang

### SampleDataSeeder.php
**Changes:**
1. `seedGudangData()` method:
   - Added `whereNotNull('id_cabang')` filter for users
   - Changed composite key from `['unique_id' => $user->unique_id, ...]` to `['id_cabang' => $user->id_cabang, ...]`
   - Updated comments to reflect "user's cabang" instead of "per user"

### UserFactory.php
**Changes:**
1. Definition:
   - Removed: `branch_name => $this->faker->company`
   - Added: `id_cabang => null` (must be set explicitly)
2. Added `cabang(string $idCabang)` state method for setting specific branch
3. Kept existing `admin()` and `user()` state methods

**Usage Example:**
```php
User::factory()->cabang('CABANG002')->user()->create();
```

## Migration Dependencies

**Required Migrations (must run first):**
1. `2025_11_16_000001_create_tb_cabang_table.php`
2. `2025_11_16_000002_alter_tb_users_add_id_cabang_drop_branch_name.php`
3. `2025_11_16_000003_alter_tb_gudang_replace_unique_id_with_id_cabang.php`
4. `2025_11_16_000004_alter_tb_penggunaan_barang_add_id_cabang.php`

## Seeding Instructions

### Fresh Installation
```bash
# Run migrations
php artisan migrate:fresh

# Seed all data
php artisan db:seed

# Or specific seeder
php artisan db:seed --class=CabangSeeder
```

### Existing Database
```bash
# Run new migrations
php artisan migrate

# Seed only Cabang
php artisan db:seed --class=CabangSeeder

# Manually update existing users
UPDATE tb_users SET id_cabang = 'CABANG002' WHERE unique_id = 'USR1001';
UPDATE tb_users SET id_cabang = 'CABANG003' WHERE unique_id = 'USR1002';
# ... etc
```

## Data Mapping

| User ID | Username | Old Field | New Field | Cabang Name |
|---------|----------|-----------|-----------|-------------|
| ADMIN001 | superadmin | branch_name: 'Head Office' | id_cabang: 'CABANG001' | Pusat |
| MANAGER001 | supermanager | branch_name: 'South Branch' | id_cabang: 'CABANG001' | Pusat |
| USER001 | superuser | branch_name: 'South Branch' | id_cabang: 'CABANG002' | South Branch |
| USR1001 | budi | branch_name: 'South Branch' | id_cabang: 'CABANG002' | South Branch |
| USR1002 | siti | branch_name: 'North Branch' | id_cabang: 'CABANG003' | North Branch |
| USR1003 | agus | branch_name: 'East Branch' | id_cabang: 'CABANG004' | East Branch |
| USR1004 | lina | branch_name: 'West Branch' | id_cabang: 'CABANG005' | West Branch |
| USR1005 | yusuf | branch_name: 'Central Branch' | id_cabang: 'CABANG006' | Central Branch |

## Gudang Stock Association

**Before (OLD):**
- Gudang → `unique_id` (user FK)
- Stock belonged to individual users

**After (NEW):**
- Gudang → `id_cabang` (branch FK)
- Stock belongs to branches
- Multiple users can share the same branch's inventory

**Seeder Behavior:**
- Each branch (via its users) gets random stock for all barang items
- Stock quantity: 5-30 units per item (DatabaseSeeder)
- Stock quantity: 1-50 units per item (SampleDataSeeder)

## Testing

```php
// Create test user with cabang
$user = User::factory()
    ->cabang('CABANG002')
    ->user()
    ->create();

// Create test cabang
$cabang = Cabang::factory()->create([
    'nama_cabang' => 'Test Branch'
]);

// Assign user to cabang
$user->update(['id_cabang' => $cabang->id_cabang]);
```

## Notes

1. **Admin & Manager** are assigned to CABANG001 (Pusat) but do NOT record usage - they only monitor
2. **Branch users** (USR1001-USR1005) each have their own cabang with separate inventory
3. **Gudang records** are now tied to cabang, not individual users - this allows multiple users per branch in the future
4. **USER001** now belongs to CABANG002 (South Branch) for testing purposes
5. All seeders use `updateOrCreate()` or `firstOrCreate()` for idempotency - safe to run multiple times

## Date Created
November 16, 2025
