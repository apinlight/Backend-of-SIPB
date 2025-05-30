<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cache permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Buat permissions
        $permissions = [
            // CRUD Data Master
            'manage users',
            'manage barang',
            'manage jenis barang',
            'manage batas barang',
            'manage batas pengajuan',
            'manage gudang',
            'view all riwayat',
            'approve pengajuan',
            'deny pengajuan',

            // Manager
            'view branch gudang',
            'view branch pengajuan',
            'view branch riwayat',

            // User
            'create pengajuan',
            'edit own pengajuan',
            'view own gudang',
            'view own riwayat',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                [
                    'name' => $permission,
                    'guard_name' => config('auth.defaults.guard'),                    
                ]);
        }

        // Roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $user = Role::firstOrCreate(['name' => 'user']);

        // Assign permissions to roles
        $admin->syncPermissions([
            'manage users',
            'manage barang',
            'manage jenis barang',
            'manage batas barang',
            'manage batas pengajuan',
            'manage gudang',
            'view all riwayat',
            'approve pengajuan',
            'deny pengajuan',
        ]);

        $manager->syncPermissions([
            'view branch gudang',
            'view branch pengajuan',
            'view branch riwayat',
        ]);

        $user->syncPermissions([
            'create pengajuan',
            'edit own pengajuan',
            'view own gudang',
            'view own riwayat',
        ]);
    }
}