<?php

namespace App\Services;

use App\Models\BatasBarang;
use App\Models\Gudang;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class PengajuanService
{
    protected $settingsService;

    public function __construct(GlobalSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function create(array $data, ?UploadedFile $file): Pengajuan
    {
        $this->validateMonthlyLimit($data['unique_id']);
        if ($file && ($data['tipe_pengajuan'] ?? 'biasa') === 'mandiri') {
            $data['bukti_file'] = $file->store('bukti-pengajuan', 'public');
        }
        $data['status_pengajuan'] = $data['status_pengajuan'] ?? Pengajuan::STATUS_PENDING;
        $pengajuan = Pengajuan::create($data);
        if (isset($data['items'])) {
            $pengajuan->details()->createMany($data['items']);
        }
        return $pengajuan;
    }

    public function updateStatus(Pengajuan $pengajuan, User $approver, array $data): Pengajuan
    {
        $newStatus = $data['status_pengajuan'];

        if ($newStatus === Pengajuan::STATUS_APPROVED) {
            $stockErrors = $this->validateStockLimitsOnApproval($pengajuan, $approver);
            if (!empty($stockErrors)) {
                throw new Exception(json_encode($stockErrors));
            }
        }
        
        $this->updateStatusAndAudit($pengajuan, $approver, $newStatus, $data);

        if ($newStatus === Pengajuan::STATUS_APPROVED && in_array($pengajuan->tipe_pengajuan, ['biasa', 'manual'])) {
            $this->transferStock($pengajuan, $approver);
        }
        return $pengajuan->fresh(['user', 'details.barang', 'approver', 'rejector']);
    }

    public function delete(Pengajuan $pengajuan): void
    {
        if (!$pengajuan->canBeDeleted()) { throw new Exception('Cannot delete an approved or completed pengajuan.'); }
        if ($pengajuan->bukti_file && Storage::disk('public')->exists($pengajuan->bukti_file)) { Storage::disk('public')->delete($pengajuan->bukti_file); }
        $pengajuan->delete();
    }
    
    private function validateMonthlyLimit(string $userId): void
    {
        $user = User::find($userId);
        if (!$user || $user->hasRole('admin')) return;
        $monthlyLimit = $this->settingsService->getMonthlyLimit();
        $currentMonthCount = Pengajuan::where('unique_id', $userId)
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->whereIn('status_pengajuan', [Pengajuan::STATUS_PENDING, Pengajuan::STATUS_APPROVED])->count();
        if ($currentMonthCount >= $monthlyLimit) {
            throw new Exception(json_encode(['monthly_limit' => "Monthly submission limit of {$monthlyLimit} has been reached."]));
        }
    }
    
    private function validateStockLimitsOnApproval(Pengajuan $pengajuan, User $approver): array
    {
        $errors = [];
        foreach ($pengajuan->details as $detail) {
            $sourceStock = Gudang::where('unique_id', $approver->unique_id)
                ->where('id_barang', $detail->id_barang)->value('jumlah_barang') ?? 0;
            if ($sourceStock < $detail->jumlah) {
                $errors[] = "Insufficient central stock for item {$detail->barang->nama_barang}. Available: {$sourceStock}, Requested: {$detail->jumlah}";
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
    
    protected function transferStock(Pengajuan $pengajuan, User $approver): void
    {
        DB::transaction(function () use ($pengajuan, $approver) {
            foreach ($pengajuan->details as $detail) {
                // ✅ PERBAIKAN FINAL: Buat query dengan composite key untuk memperbarui sumber.
                $updatedRows = Gudang::where('unique_id', $approver->unique_id)
                    ->where('id_barang', $detail->id_barang)
                    ->decrement('jumlah_barang', $detail->jumlah);

                // Ini adalah pengaman penting. Jika baris tidak ada atau decrement gagal,
                // kita lempar error.
                if ($updatedRows === 0) {
                    throw new Exception("Gagal mengurangi stok untuk item {$detail->id_barang}. Stok sumber mungkin tidak ada atau tidak mencukupi.");
                }

                // ✅ PERBAIKAN: Gunakan updateOrCreate dengan DB::raw untuk keamanan dan konsistensi.
                Gudang::updateOrCreate(
                    ['unique_id' => $pengajuan->unique_id, 'id_barang' => $detail->id_barang],
                    ['jumlah_barang' => DB::raw("jumlah_barang + {$detail->jumlah}")]
                );
            }
        });
    }
}