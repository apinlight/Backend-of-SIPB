<?php
// database/migrations/2025_06_24_213356_create_tb_penggunaan_barang_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tb_penggunaan_barang', function (Blueprint $table) {
            $table->id('id_penggunaan');
            $table->string('unique_id'); // User who used the item
            $table->string('id_barang');
            $table->integer('jumlah_digunakan');
            $table->string('keperluan'); // Purpose of usage
            $table->date('tanggal_penggunaan');
            $table->text('keterangan')->nullable();
            $table->string('approved_by')->nullable(); // If usage needs approval
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->timestamps();

            $table->foreign('unique_id')->references('unique_id')->on('tb_users')->onDelete('cascade');
            $table->foreign('id_barang')->references('id_barang')->on('tb_barang')->onDelete('cascade');
            
            $table->index(['unique_id', 'id_barang']);
            $table->index('tanggal_penggunaan');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tb_penggunaan_barang');
    }
};
