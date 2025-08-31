<?php
// database/migrations/2025_06_22_230959_add_is_active_to_jenis_barang_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tb_jenis_barang', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('nama_jenis_barang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_jenis_barang', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
