<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // tb_users Table (for User Model)
        Schema::create('tb_users', function (Blueprint $table) {
            $table->string('unique_id')->primary();
            $table->string('username');
            $table->string('email')->unique();
            $table->string('password');
            $table->integer('role_id');
            $table->string('branch_name');
            $table->timestamps();
        });

        // // tb_jenis_barang Table (for JenisBarang Model)
        // Schema::create('tb_jenis_barang', function (Blueprint $table) {
        //     $table->string('id_jenis_barang')->primary();
        //     $table->string('nama_jenis_barang');
        //     $table->timestamps();
        // });
        
        // tb_barang Table (for Barang Model)
        Schema::create('tb_barang', function (Blueprint $table) {
            $table->string('id_barang')->primary();
            $table->string('nama_barang');
            $table->string('id_jenis_barang');
            $table->integer('harga_barang');
            $table->timestamps();
        });

        // tb_batas_barang Table (for BatasBarang Model)
        Schema::create('tb_batas_barang', function (Blueprint $table) {
            $table->string('id_barang')->primary();
            $table->integer('batas_barang');
            $table->integer('harga_barang')->nullable();
            $table->timestamps();

            $table->foreign('id_barang')->references('id_barang')->on('tb_barang')->onDelete('cascade');
        });

        // tb_pengajuan Table (for Pengajuan Model)
        Schema::create('tb_pengajuan', function (Blueprint $table) {
            $table->string('id_pengajuan');
            $table->string('unique_id');
            $table->string('status_pengajuan');
            $table->primary('id_pengajuan');
            $table->timestamps();

            $table->foreign('unique_id')->references('unique_id')->on('tb_users')->onDelete('cascade');
        });

        // tb_batas_pengajuan Table (for BatasPengajuan Model)
        Schema::create('tb_batas_pengajuan', function (Blueprint $table) {
            $table->string('id_barang')->primary();
            $table->integer('batas_pengajuan');
            $table->timestamps();

            $table->foreign('id_barang')->references('id_barang')->on('tb_barang')->onDelete('cascade');
        });

        // tb_detail_pengajuan Table (for DetailPengajuan Model)
        Schema::create('tb_detail_pengajuan', function (Blueprint $table) {
            $table->string('id_pengajuan');
            $table->string('id_barang');
            $table->primary(['id_pengajuan', 'id_barang']);
            $table->timestamps();

            $table->foreign('id_pengajuan')->references('id_pengajuan')->on('tb_pengajuan')->onDelete('cascade');
            $table->foreign('id_barang')->references('id_barang')->on('tb_barang')->onDelete('cascade');
        });

        // tb_gudang Table (for Gudang Model)
        Schema::create('tb_gudang', function (Blueprint $table) {
            $table->string('unique_id');
            $table->string('id_barang');
            $table->integer('jumlah_barang');
            $table->primary(['unique_id', 'id_barang']);
            $table->timestamps();

            $table->foreign('unique_id')->references('unique_id')->on('tb_users')->onDelete('cascade');
            $table->foreign('id_barang')->references('id_barang')->on('tb_barang')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
{
    Schema::dropIfExists('tb_detail_pengajuan');
    Schema::dropIfExists('tb_gudang');
    Schema::dropIfExists('tb_pengajuan');
    Schema::dropIfExists('tb_batas_pengajuan');
    Schema::dropIfExists('tb_batas_barang');
    Schema::dropIfExists('tb_users');
    Schema::dropIfExists('tb_jenis_barang');
    Schema::dropIfExists('tb_barang');
}
};