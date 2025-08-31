<?php
// database/migrations/2025_06_22_230957_add_keterangan_to_detail_pengajuan_table.php

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
        Schema::table('tb_detail_pengajuan', function (Blueprint $table) {
            $table->text('keterangan')->nullable()->after('jumlah');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_detail_pengajuan', function (Blueprint $table) {
            $table->dropColumn('keterangan');
        });
    }
};
