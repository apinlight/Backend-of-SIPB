<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tb_cabang', function (Blueprint $table) {
            $table->boolean('is_pusat')->default(false)->after('nama_cabang')
                ->comment('Flag to identify central warehouse/pusat');
        });

        // Update existing "Pusat" cabang to be marked as central warehouse
        DB::table('tb_cabang')
            ->where('nama_cabang', 'like', '%Pusat%')
            ->update(['is_pusat' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_cabang', function (Blueprint $table) {
            $table->dropColumn('is_pusat');
        });
    }
};
