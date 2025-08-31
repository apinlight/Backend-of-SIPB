<?php
// database/migrations/2025_06_17_152707_drop_wrong_batas_pengajuan_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the wrong table structure
        Schema::dropIfExists('tb_batas_pengajuan');
    }

    public function down(): void
    {
        // Recreate if needed (but this was wrong anyway)
        Schema::create('tb_batas_pengajuan', function (Blueprint $table) {
            $table->string('id_barang')->primary();
            $table->integer('batas_pengajuan');
            $table->timestamps();
        });
    }
};
