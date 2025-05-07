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
            User::factory()->admin()->create([
                'unique_id' => 'ADMIN001',
                'username' => 'superadmin',
                'email' => 'admin@example.com',
                'password' => 'password',
                'branch_name' => 'Head Office',
            ])->assignRole('admin');
        }

        // 5 user biasa
        User::factory(5)->user()->create();

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
