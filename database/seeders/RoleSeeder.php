<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run()
    {
        // Define the roles you want to create.
        $roles = [
            'admin',
            'manager',
            'user',
        ];

        // Create each role if it does not already exist.
        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name'       => $role,
                'guard_name' => config('auth.defaults.guard'),
            ]);
        }
    }
}
