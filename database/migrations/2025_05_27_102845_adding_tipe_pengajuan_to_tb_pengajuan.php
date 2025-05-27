<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tb_pengajuan', function (Blueprint $table) {
            $table->string('tipe_pengajuan')->default('biasa')->after('status_pengajuan');
        });
    }
    public function down(): void
    {
        Schema::table('tb_pengajuan', function (Blueprint $table) {
            $table->dropColumn('tipe_pengajuan');
        });
    }
};