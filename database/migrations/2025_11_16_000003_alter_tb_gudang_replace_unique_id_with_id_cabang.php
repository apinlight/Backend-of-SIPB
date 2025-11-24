<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // As per instruction, development data can be dropped
        DB::table('tb_gudang')->truncate();

        // Check if unique_id column exists
        $hasUniqueId = DB::select("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'tb_gudang' 
            AND COLUMN_NAME = 'unique_id'
        ");

        if (!empty($hasUniqueId)) {
            // Drop foreign key constraint if it exists
            try {
                DB::statement('ALTER TABLE tb_gudang DROP FOREIGN KEY tb_gudang_unique_id_foreign');
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }

            // Drop primary key first (unique_id is part of composite primary key)
            try {
                DB::statement('ALTER TABLE tb_gudang DROP PRIMARY KEY');
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }

            // Drop index if it exists
            try {
                DB::statement('ALTER TABLE tb_gudang DROP INDEX tb_gudang_unique_id_id_barang_index');
            } catch (\Exception $e) {
                // Ignore if doesn't exist
            }

            // Drop the unique_id column
            DB::statement('ALTER TABLE tb_gudang DROP COLUMN unique_id');
        }

        // Check if id_cabang column already exists
        $hasIdCabang = DB::select("
            SELECT COLUMN_NAME 
            FROM information_schema.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'tb_gudang' 
            AND COLUMN_NAME = 'id_cabang'
        ");

        if (empty($hasIdCabang)) {
            // Add id_cabang column and recreate primary key
            Schema::table('tb_gudang', function (Blueprint $table) {
                $table->string('id_cabang', 26)->after('id_barang');
                $table->primary(['id_cabang', 'id_barang']);
                $table->foreign('id_cabang')
                    ->references('id_cabang')
                    ->on('tb_cabang')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('tb_gudang', function (Blueprint $table) {
            if (Schema::hasColumn('tb_gudang', 'id_cabang')) {
                $table->dropForeign(['id_cabang']);
                $table->dropIndex(['id_cabang', 'id_barang']);
                $table->dropColumn('id_cabang');
            }
            if (!Schema::hasColumn('tb_gudang', 'unique_id')) {
                $table->string('unique_id', 26)->after('id_barang');
                $table->index(['unique_id', 'id_barang']);
            }
        });
    }
};
