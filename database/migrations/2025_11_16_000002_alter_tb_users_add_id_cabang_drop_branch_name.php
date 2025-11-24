<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_users', function (Blueprint $table) {
            if (Schema::hasColumn('tb_users', 'branch_name')) {
                $table->dropColumn('branch_name');
            }
            if (!Schema::hasColumn('tb_users', 'id_cabang')) {
                $table->string('id_cabang', 26)->nullable()->after('password');
                $table->index('id_cabang');
            }
        });

        // Add FK constraint separately to avoid issues if column just added
        Schema::table('tb_users', function (Blueprint $table) {
            if (Schema::hasColumn('tb_users', 'id_cabang')) {
                $table->foreign('id_cabang')
                    ->references('id_cabang')
                    ->on('tb_cabang')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tb_users', function (Blueprint $table) {
            if (Schema::hasColumn('tb_users', 'id_cabang')) {
                $table->dropForeign(['id_cabang']);
                $table->dropIndex(['id_cabang']);
                $table->dropColumn('id_cabang');
            }
            if (!Schema::hasColumn('tb_users', 'branch_name')) {
                $table->string('branch_name')->nullable()->after('password');
            }
        });
    }
};
