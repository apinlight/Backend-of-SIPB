<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tb_jenis_barang', function (Blueprint $table) {
            $table->string('id_jenis_barang')->primary(); // ULID
            $table->string('nama_jenis_barang');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_jenis_barang');
    }
};
