<?php

// database/migrations/2025_06_26_013144_add_database_indexes_for_performance.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Indexes for tb_pengajuan table
        Schema::table('tb_pengajuan', function (Blueprint $table) {
            $table->index('status_pengajuan', 'idx_pengajuan_status');
            $table->index('tipe_pengajuan', 'idx_pengajuan_tipe');
            $table->index('created_at', 'idx_pengajuan_created');
            $table->index(['unique_id', 'status_pengajuan'], 'idx_pengajuan_user_status');
            $table->index('approved_by', 'idx_pengajuan_approved_by');
        });

        // 2. Indexes for tb_detail_pengajuan table
        Schema::table('tb_detail_pengajuan', function (Blueprint $table) {
            $table->index('id_pengajuan', 'idx_detail_pengajuan');
            $table->index('id_barang', 'idx_detail_barang');
        });

        // 3. Indexes for tb_barang table
        Schema::table('tb_barang', function (Blueprint $table) {
            $table->index('nama_barang', 'idx_barang_nama');
            $table->index('id_jenis_barang', 'idx_barang_jenis');
            $table->index('harga_barang', 'idx_barang_harga');
        });

        // 4. Indexes for tb_gudang table
        Schema::table('tb_gudang', function (Blueprint $table) {
            $table->index('id_barang', 'idx_gudang_barang');
            $table->index('unique_id', 'idx_gudang_user');
            $table->index('created_at', 'idx_gudang_created');
            $table->index('jumlah_barang', 'idx_gudang_jumlah');
        });

        // 5. Indexes for tb_users table
        Schema::table('tb_users', function (Blueprint $table) {
            $table->index('branch_name', 'idx_users_branch');
            $table->index('email', 'idx_users_email');
            $table->index('username', 'idx_users_username');
        });

        // 6. Indexes for tb_jenis_barang table
        Schema::table('tb_jenis_barang', function (Blueprint $table) {
            $table->index('nama_jenis_barang', 'idx_jenis_nama');
            $table->index('is_active', 'idx_jenis_active');
        });

        // 7. Indexes for tb_penggunaan_barang table
        Schema::table('tb_penggunaan_barang', function (Blueprint $table) {
            $table->index('status', 'idx_penggunaan_status');
            $table->index('tanggal_penggunaan', 'idx_penggunaan_tanggal');
            $table->index('approved_by', 'idx_penggunaan_approved_by');
            $table->index(['unique_id', 'status'], 'idx_penggunaan_user_status');
        });

        // 8. Indexes for tb_batas_barang table
        Schema::table('tb_batas_barang', function (Blueprint $table) {
            $table->index('batas_barang', 'idx_batas_barang_limit');
        });
    }

    public function down()
    {
        // Drop indexes in reverse order
        Schema::table('tb_batas_barang', function (Blueprint $table) {
            $table->dropIndex('idx_batas_barang_limit');
        });

        Schema::table('tb_penggunaan_barang', function (Blueprint $table) {
            $table->dropIndex('idx_penggunaan_status');
            $table->dropIndex('idx_penggunaan_tanggal');
            $table->dropIndex('idx_penggunaan_approved_by');
            $table->dropIndex('idx_penggunaan_user_status');
        });

        Schema::table('tb_jenis_barang', function (Blueprint $table) {
            $table->dropIndex('idx_jenis_nama');
            $table->dropIndex('idx_jenis_active');
        });

        Schema::table('tb_users', function (Blueprint $table) {
            $table->dropIndex('idx_users_branch');
            $table->dropIndex('idx_users_email');
            $table->dropIndex('idx_users_username');
        });

        Schema::table('tb_gudang', function (Blueprint $table) {
            $table->dropIndex('idx_gudang_barang');
            $table->dropIndex('idx_gudang_user');
            $table->dropIndex('idx_gudang_created');
            $table->dropIndex('idx_gudang_jumlah');
        });

        Schema::table('tb_barang', function (Blueprint $table) {
            $table->dropIndex('idx_barang_nama');
            $table->dropIndex('idx_barang_jenis');
            $table->dropIndex('idx_barang_harga');
        });

        Schema::table('tb_detail_pengajuan', function (Blueprint $table) {
            $table->dropIndex('idx_detail_pengajuan');
            $table->dropIndex('idx_detail_barang');
        });

        Schema::table('tb_pengajuan', function (Blueprint $table) {
            $table->dropIndex('idx_pengajuan_status');
            $table->dropIndex('idx_pengajuan_tipe');
            $table->dropIndex('idx_pengajuan_created');
            $table->dropIndex('idx_pengajuan_user_status');
            $table->dropIndex('idx_pengajuan_approved_by');
        });
    }
};
