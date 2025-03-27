<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Spatie\Permission\Models\Role;

class MigrateLegacyRolesToSpatie extends Migration
{
    public function up()
    {
        // Define legacy role mapping
        $roleMapping = [
            1 => 'admin',
            2 => 'manager',
            5 => 'user',
        ];

        // Create roles if they don't exist
        foreach ($roleMapping as $legacyId => $roleName) {
            Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web', // Adjust if using a different guard
            ]);
        }

        // Assign roles based on legacy role_id
        $users = User::all();
        foreach ($users as $user) {
            $legacyRoleId = $user->role_id;
            if (isset($roleMapping[$legacyRoleId])) {
                $user->assignRole($roleMapping[$legacyRoleId]);
            }
        }
    }

    public function down()
    {
        // Optionally remove assigned roles on rollback
        foreach (User::all() as $user) {
            $user->syncRoles([]);
        }
    }
}
