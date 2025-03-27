<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
    }
}
