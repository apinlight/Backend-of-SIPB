<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tb_cabang', function (Blueprint $table) {
            $table->string('id_cabang', 26)->primary();
            $table->string('nama_cabang');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_cabang');
    }
};
