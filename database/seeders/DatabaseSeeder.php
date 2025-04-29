<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Call the RoleSeeder to seed the roles table
        $this->call([
            RoleSeeder::class,
        ]);

        User::factory()->admin()->create([
            'unique_id' => 'ADMIN001',
            'username' => 'superadmin',
            'email' => 'admin@example.com', 
            'password' => 'password',
            'branch_name' => 'Head Office',
        ])->assignRole('admin');
    }
}
