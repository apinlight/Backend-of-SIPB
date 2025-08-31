<?php
// database/seeders/DatabaseSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Barang;
use App\Models\JenisBarang;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // ✅ 1. Seed roles and permissions first
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

        // ✅ 2. Seed global settings
        $this->call([
            GlobalSettingSeeder::class,
        ]);

        // ✅ 3. Create admin user
        if (!User::where('unique_id', 'ADMIN001')->exists()) {
            User::factory()->create([
                'unique_id' => 'ADMIN001',
                'username' => 'superadmin',
                'email' => 'admin@example.com',
                'password' => 'password',
                'branch_name' => 'Head Office',
            ])->assignRole('admin');
        }

        // ✅ 4. Create manager user
        if (!User::where('unique_id', 'MANAGER001')->exists()) {
            User::factory()->create([
                'unique_id' => 'MANAGER001',
                'username' => 'supermanager',
                'email' => 'manager@example.com',
                'password' => 'password',
                'branch_name' => 'South Branch',
            ])->assignRole('manager');
        }

        // ✅ 5. Create regular user
        if (!User::where('unique_id', 'USER001')->exists()) {
            User::factory()->create([
                'unique_id' => 'USER001',
                'username' => 'superuser',
                'email' => 'user@example.com',
                'password' => 'password',
                'branch_name' => 'South Branch',
            ])->assignRole('user');
        }

        // ✅ 6. Create additional test users
        User::factory(5)->create()->each(function($user) {
            $user->assignRole('user');
        });

        // ✅ 7. Create jenis barang
        JenisBarang::factory(5)->create(); // Increased to 5 for more variety

        // ✅ 8. Create barang for each jenis
        JenisBarang::all()->each(function ($jenis) {
            Barang::factory(4)->create([ // Reduced to 4 per jenis for cleaner data
                'id_jenis_barang' => $jenis->id_jenis_barang,
                'harga_barang' => rand(10000, 500000),
            ]);
        });

        // ✅ 9. Seed batas barang
        $this->call([
            BatasBarangSeeder::class,
        ]);

        // ✅ 10. Seed sample transactional data (optional - for testing)
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                SampleDataSeeder::class,
            ]);
        }

        $this->command->info('🎯 Database seeding completed successfully!');
        $this->command->info('📋 Default credentials:');
        $this->command->info('   Admin: admin@example.com / password');
        $this->command->info('   Manager: manager@example.com / password');  
        $this->command->info('   User: user@example.com / password');
    }
}
