<?php

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
        Schema::table('tb_barang', function (Blueprint $table) {
            // Add the columns that are in your model but not your original migration.
            $table->text('deskripsi')->nullable()->after('harga_barang');
            $table->string('satuan', 50)->nullable()->after('deskripsi');
            $table->unsignedInteger('batas_minimum')->default(5)->after('satuan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_barang', function (Blueprint $table) {
            $table->dropColumn(['deskripsi', 'satuan', 'batas_minimum']);
        });
    }
};