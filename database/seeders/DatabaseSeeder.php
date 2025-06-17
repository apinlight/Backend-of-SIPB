<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Barang;
use App\Models\JenisBarang;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Seed roles
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        // Admin
        if (!User::where('unique_id', 'ADMIN001')->exists()) {
            User::factory()->create([
                'unique_id' => 'ADMIN001',
                'username' => 'superadmin',
                'email' => 'admin@example.com',
                'password' => 'password',
                'branch_name' => 'Head Office',
            ])->assignRole('admin');
        }

        // User 1
        if (!User::where('unique_id', 'USER001')->exists()) {
            User::factory()->create([
                'unique_id' => 'USER001',
                'username' => 'superuser',
                'email' => 'user@example.com',
                'password' => 'password',
                'branch_name' => 'South Branch',
            ])->assignRole('user');
        }

        // Manager 1
        if (!User::where('unique_id', 'MANAGER001')->exists()) {
            User::factory()->create([
                'unique_id' => 'MANAGER001',
                'username' => 'supermanager',
                'email' => 'manager@example.com',
                'password' => 'password',
                'branch_name' => 'South Branch',
            ])->assignRole('manager');
        }

        // 5 user biasa
        User::factory(5)->create()->each(function($user)
        {
            $user->assignRole('user');
        });

        // 3 jenis barang
        JenisBarang::factory(3)->create();

        // Barang untuk setiap jenis barang
        JenisBarang::all()->each(function ($jenis) {
            Barang::factory(5)->create([
                'id_jenis_barang' => $jenis->id_jenis_barang,
                'harga_barang' => rand(10000, 500000),
            ]);
        });
    }
}
