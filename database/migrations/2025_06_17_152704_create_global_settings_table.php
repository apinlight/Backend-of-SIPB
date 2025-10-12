<?php

// database/migrations/2025_06_17_152704_create_global_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->string('setting_value');
            $table->string('setting_description')->nullable();
            $table->timestamps();
        });

        // Insert default monthly pengajuan limit
        DB::table('tb_global_settings')->insert([
            'setting_key' => 'monthly_pengajuan_limit',
            'setting_value' => '5',
            'setting_description' => 'Maximum pengajuan per user per month',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_global_settings');
    }
};
