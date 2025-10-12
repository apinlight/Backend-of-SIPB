<?php

// database/migrations/2025_06_17_152705_add_audit_fields_to_pengajuan.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tb_pengajuan', function (Blueprint $table) {
            $table->string('bukti_file')->nullable()->after('tipe_pengajuan');
            $table->string('approved_by')->nullable()->after('bukti_file');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->string('rejected_by')->nullable()->after('approved_at');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
            $table->text('approval_notes')->nullable()->after('rejection_reason');
            $table->text('keterangan')->nullable()->after('approval_notes');
        });
    }

    public function down(): void
    {
        Schema::table('tb_pengajuan', function (Blueprint $table) {
            $table->dropColumn([
                'bukti_file', 'approved_by', 'approved_at',
                'rejected_by', 'rejected_at', 'rejection_reason',
                'approval_notes', 'keterangan',
            ]);
        });
    }
};
