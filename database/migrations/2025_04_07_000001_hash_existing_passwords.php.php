<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HashExistingPasswords extends Migration
{
    public function up()
    {
        // Fetch all users (adjust the query as needed)
        $users = DB::table('tb_users')->get();

        foreach ($users as $user) {
            // Only hash if it's not already hashed (you may need a check here)
            DB::table('tb_users')
                ->where('unique_id', $user->unique_id)
                ->update([
                    'password' => Hash::make($user->password)
                ]);
        }
    }

    public function down()
    {
        // The down() method cannot revert hashed passwords back to plaintext.
        // You might leave it empty or handle it based on your application's needs.
    }
}