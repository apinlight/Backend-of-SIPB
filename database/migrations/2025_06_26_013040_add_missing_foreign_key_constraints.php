<?php

// database/migrations/2025_06_26_013040_add_missing_foreign_key_constraints.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add missing foreign key constraint for tb_barang
        Schema::table('tb_barang', function (Blueprint $table) {
            $table->foreign('id_jenis_barang')
                ->references('id_jenis_barang')
                ->on('tb_jenis_barang')
                ->onDelete('restrict') // Prevent deletion if referenced
                ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::table('tb_barang', function (Blueprint $table) {
            $table->dropForeign(['id_jenis_barang']);
        });
    }
};
