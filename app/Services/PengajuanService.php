<?php

namespace App\Services;

use App\Models\Pengajuan;
use App\Models\DetailPengajuan;
use App\Models\User;
use App\Models\Gudang;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class PengajuanService
{
    protected $validationService;

    public function __construct(PengajuanValidationService $validationService)
    {
        $this->validationService = $validationService;
    }

    public function create(array $data, ?UploadedFile $file): Pengajuan
    {
        if (isset($data['items'])) {
            $errors = $this->validationService->validatePengajuanLimits($data['unique_id'], $data['items']);
            if (!empty($errors)) {
                throw new Exception(json_encode($errors));
            }
        }

        if ($file && ($data['tipe_pengajuan'] ?? 'biasa') === 'mandiri') {
            $data['bukti_file'] = $file->store('bukti-pengajuan', 'public');
        }

        $data['status_pengajuan'] = $data['status_pengajuan'] ?? 'Menunggu Persetujuan';
        
        return Pengajuan::create($data);
    }

    public function updateStatus(Pengajuan $pengajuan, User $user, array $data): Pengajuan
    {
        $newStatus = $data['status_pengajuan'];

        if ($newStatus === 'Disetujui') {
            $stockErrors = $pengajuan->validateStockLimits();
            if (!empty($stockErrors)) {
                throw new Exception(json_encode($stockErrors));
            }
        }

        // Use the audit method from the model
        $pengajuan->updateStatus($newStatus, [
            'approval_notes' => $data['approval_notes'] ?? null,
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        // If approved, move the stock atomically
        if ($newStatus === 'Disetujui' && in_array($pengajuan->tipe_pengajuan, ['biasa', 'manual'])) {
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

    /**
     * Adds an item to a pengajuan, or updates the quantity if it already exists.
     */
    public function addItem(Pengajuan $pengajuan, array $itemData): DetailPengajuan
    {
        if (!$pengajuan->isMutable()) {
            throw new Exception('Cannot modify an approved or rejected pengajuan.');
        }

        $detail = $pengajuan->details()->updateOrCreate(
            [
                'id_barang' => $itemData['id_barang'],
            ],
            [
                'jumlah' => DB::raw("jumlah + {$itemData['jumlah']}"),
                'keterangan' => $itemData['keterangan'] ?? null,
            ]
        );

        return $detail->refresh();
    }

    /**
     * Updates an existing item within a pengajuan.
     */
    public function updateItem(DetailPengajuan $detail, array $itemData): DetailPengajuan
    {
        if (!$detail->pengajuan->isMutable()) {
            throw new Exception('Cannot modify an approved or rejected pengajuan.');
        }
        
        $detail->update($itemData);
        return $detail->fresh();
    }

    /**
     * Removes an item from a pengajuan.
     */
    public function removeItem(DetailPengajuan $detail): void
    {
        if (!$detail->pengajuan->isMutable()) {
            throw new Exception('Cannot modify an approved or rejected pengajuan.');
        }

        $detail->delete();
    }
}