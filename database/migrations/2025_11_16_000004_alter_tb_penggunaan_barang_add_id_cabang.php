<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_penggunaan_barang', function (Blueprint $table) {
            if (!Schema::hasColumn('tb_penggunaan_barang', 'id_cabang')) {
                $table->string('id_cabang', 26)->nullable()->after('unique_id');
                $table->index('id_cabang');
            }
        });

        Schema::table('tb_penggunaan_barang', function (Blueprint $table) {
            if (Schema::hasColumn('tb_penggunaan_barang', 'id_cabang')) {
                $table->foreign('id_cabang')
                    ->references('id_cabang')
                    ->on('tb_cabang')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_penggunaan_barang', function (Blueprint $table) {
            if (Schema::hasColumn('tb_penggunaan_barang', 'id_cabang')) {
                $table->dropForeign(['id_cabang']);
                $table->dropIndex(['id_cabang']);
                $table->dropColumn('id_cabang');
            }
        });
    }
};
