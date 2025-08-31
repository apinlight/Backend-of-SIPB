<?php
// database/migrations/2025_06_22_230958_add_keterangan_to_gudang_table.php

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
        Schema::table('tb_gudang', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('jumlah_barang');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_gudang', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
