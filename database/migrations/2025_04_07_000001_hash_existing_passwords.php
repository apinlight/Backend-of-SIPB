<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HashExistingPasswords extends Migration
{
    public function up()
    {
        // Fetch all users
        $users = DB::table('tb_users')->get();

        foreach ($users as $user) {
            // Check if the password is not already hashed
            if (!Hash::check($user->password, $user->password)) {
                // Hash the password and update the user record
                DB::table('tb_users')
                    ->where('unique_id', $user->unique_id)
                    ->update([
                        'password' => Hash::make($user->password),
                    ]);
            }
        }
    }

    public function down()
    {
        // The down() method cannot revert hashed passwords back to plaintext.
        // You might leave it empty or handle it based on your application's needs.
    }
}