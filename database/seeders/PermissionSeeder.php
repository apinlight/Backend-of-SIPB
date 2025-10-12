<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // ✅ BARANG - All can view, only admin can modify
            'view barang',
            'manage barang', // Admin only

            // ✅ PENGAJUAN - Scoped permissions
            'create pengajuan', // User only
            'edit own pengajuan', // User only (before approval)
            'view own pengajuan', // User - own pengajuan
            'view branch pengajuan', // Manager - same branch
            'view all pengajuan', // Admin - all pengajuan
            'approve pengajuan', // Admin only
            'delete own pengajuan', // User only (before approval)

            // ✅ LAPORAN/EXPORT - Scoped permissions
            'export individual', // User - own data only
            'export branch', // Manager - same branch only
            'export global', // Admin - all data
            'view own laporan', // User scope
            'view branch laporan', // Manager scope
            'view all laporan', // Admin scope

            // ✅ USER MANAGEMENT
            'manage users', // Admin only
            'view branch users', // Manager - same branch

            // ✅ GUDANG
            'view own gudang', // User
            'view branch gudang', // Manager
            'view all gudang', // Admin
            'manage gudang', // Admin only

            // ✅ SYSTEM MANAGEMENT
            'manage jenis barang', // Admin only
            'manage batas', // Admin only
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => config('auth.defaults.guard'),
            ]);
        }

        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $user = Role::firstOrCreate(['name' => 'user']);

        // ✅ ADMIN - Gets all permissions
        $admin->syncPermissions(Permission::all());

        // ✅ MANAGER - Can view branch scope, NO approval rights
        $manager->syncPermissions([
            'view barang', // Can view barang (reference data)
            'view branch pengajuan', // Can view branch pengajuan
            'view branch users', // Can view branch users
            'view branch gudang', // Can view branch gudang
            'view branch laporan', // Can view branch laporan
            'export branch', // Can export branch scope
            'export individual', // Can export individual scope
        ]);

        // ✅ USER - Can create/edit own pengajuan, view own data
        $user->syncPermissions([
            'view barang', // Can view barang (reference data)
            'create pengajuan', // Can create pengajuan
            'edit own pengajuan', // Can edit own pengajuan (before approval)
            'view own pengajuan', // Can view own pengajuan
            'delete own pengajuan', // Can cancel own pengajuan (before approval)
            'view own gudang', // Can view own gudang
            'view own laporan', // Can view own laporan
            'export individual', // Can export own data only
        ]);
    }
}
