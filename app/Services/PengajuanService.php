<?php

namespace App\Services;

use App\Models\BatasBarang;
use App\Models\DetailPengajuan;
use App\Models\Gudang;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class PengajuanService
{
    // ✅ FIX: Instead of the old validation service, we inject the new settings service.
    protected $settingsService;

    public function __construct(GlobalSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function create(array $data, ?UploadedFile $file): Pengajuan
    {
        // ✅ FIX: The validation logic is now performed directly inside this service.
        if (isset($data['items'])) {
            $this->validatePengajuanLimits($data['unique_id'], $data['items']);
        }

        if ($file && ($data['tipe_pengajuan'] ?? 'biasa') === 'mandiri') {
            $data['bukti_file'] = $file->store('bukti-pengajuan', 'public');
        }

        $data['status_pengajuan'] = $data['status_pengajuan'] ?? Pengajuan::STATUS_PENDING;
        
        $pengajuan = Pengajuan::create($data);

        // If items are included, create them now in a single transaction.
        if (isset($data['items'])) {
            $pengajuan->details()->createMany($data['items']);
        }

        return $pengajuan;
    }

    public function updateStatus(Pengajuan $pengajuan, User $user, array $data): Pengajuan
    {
        $newStatus = $data['status_pengajuan'];

        if ($newStatus === Pengajuan::STATUS_APPROVED) {
            $stockErrors = $this->validateStockLimitsOnApproval($pengajuan);
            if (!empty($stockErrors)) {
                throw new Exception(json_encode($stockErrors));
            }
        }
        
        $this->updateStatusAndAudit($pengajuan, $user, $newStatus, $data);

        if ($newStatus === Pengajuan::STATUS_APPROVED && in_array($pengajuan->tipe_pengajuan, ['biasa', 'manual'])) {
            $this->transferStockToGudang($pengajuan);
        }

        return $pengajuan->fresh(['user', 'details.barang', 'approver', 'rejector']);
    }

    public function delete(Pengajuan $pengajuan): void
    {
        if (!$pengajuan->canBeDeleted()) {
            throw new Exception('Cannot delete an approved or completed pengajuan.');
        }
        if ($pengajuan->bukti_file && Storage::disk('public')->exists($pengajuan->bukti_file)) {
            Storage::disk('public')->delete($pengajuan->bukti_file);
        }
        $pengajuan->delete();
    }
    
    // --- PRIVATE HELPER METHODS ---

    private function validatePengajuanLimits(string $userId, array $items): void
    {
        $user = User::find($userId);
        if (!$user || $user->hasRole('admin')) return;

        $monthlyLimit = $this->settingsService->getMonthlyLimit();
        $currentMonthCount = Pengajuan::where('unique_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status_pengajuan', [Pengajuan::STATUS_PENDING, Pengajuan::STATUS_APPROVED])
            ->count();
        
        if ($currentMonthCount >= $monthlyLimit) {
            throw new Exception(json_encode(['monthly_limit' => "Monthly submission limit of {$monthlyLimit} has been reached."]));
        }
    }
    
    private function validateStockLimitsOnApproval(Pengajuan $pengajuan): array
    {
        $errors = [];
        foreach ($pengajuan->details as $detail) {
            $currentStock = Gudang::where('unique_id', $pengajuan->unique_id)
                ->where('id_barang', $detail->id_barang)
                ->value('jumlah_barang') ?? 0;
            $limit = BatasBarang::where('id_barang', $detail->id_barang)->value('batas_barang') ?? PHP_INT_MAX;
            $newTotal = $currentStock + $detail->jumlah;
            if ($newTotal > $limit) {
                $errors[] = "Stock for item {$detail->barang->nama_barang} will exceed limit ({$newTotal} > {$limit})";
            }
        }
        return $errors;
    }

    private function updateStatusAndAudit(Pengajuan $pengajuan, User $user, string $status, array $data): void
    {
        $updateData = ['status_pengajuan' => $status];
        if ($status === Pengajuan::STATUS_APPROVED) {
            $updateData['approved_at'] = now();
            $updateData['approved_by'] = $user->unique_id;
            $updateData['approval_notes'] = $data['approval_notes'] ?? null;
        } elseif ($status === Pengajuan::STATUS_REJECTED) {
            $updateData['rejected_at'] = now();
            $updateData['rejected_by'] = $user->unique_id;
            $updateData['rejection_reason'] = $data['rejection_reason'] ?? null;
        }
        $pengajuan->update($updateData);
    }
    
    protected function transferStockToGudang(Pengajuan $pengajuan): void
    {
        DB::transaction(function () use ($pengajuan) {
            foreach ($pengajuan->details as $detail) {
                $gudang = Gudang::firstOrCreate(
                    ['unique_id' => $pengajuan->unique_id, 'id_barang' => $detail->id_barang],
                    ['jumlah_barang' => 0]
                );
                $gudang->increment('jumlah_barang', $detail->jumlah);
            }
        });
    }
}