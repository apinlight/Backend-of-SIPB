<?php

namespace App\Services;

use App\Models\Barang;
use Exception;

class BarangService
{
    public function create(array $data): Barang
    {
        $data['batas_minimum'] = $data['batas_minimum'] ?? 5;
        return Barang::create($data);
    }

    public function update(Barang $barang, array $data): Barang
    {
        $barang->update($data);
        return $barang->fresh(['jenisBarang']);
    }

    public function delete(Barang $barang): void
    {
        // Check if barang is used in an active (pending or approved) pengajuan
        $hasActivePengajuan = $barang->detailPengajuan()
            ->whereHas('pengajuan', function ($q) {
                $q->whereIn('status_pengajuan', [
                    \App\Models\Pengajuan::STATUS_PENDING,
                    \App\Models\Pengajuan::STATUS_APPROVED
                ]);
            })->exists();

        if ($hasActivePengajuan) {
            throw new Exception('Cannot delete barang with active pengajuan.');
        }

        // Check if the barang has any stock in any gudang.
        if ($barang->gudangEntries()->exists()) {
             throw new Exception('Cannot delete barang that still has stock in a gudang.');
        }

        $barang->delete();
    }
}