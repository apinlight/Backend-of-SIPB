<?php

namespace App\Services;

use App\Models\PenggunaanBarang;
use App\Models\Gudang;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class PenggunaanBarangService
{
    /**
     * Record a new item usage, automatically approving and decrementing stock.
     */
    public function recordUsage(User $user, array $data): PenggunaanBarang
    {
        return DB::transaction(function () use ($user, $data) {
            $gudangRecord = Gudang::where('unique_id', $user->unique_id)
                ->where('id_barang', $data['id_barang'])
                ->lockForUpdate() // Essential for preventing race conditions
                ->first();

            $currentStock = $gudangRecord ? $gudangRecord->jumlah_barang : 0;

            if ($currentStock < $data['jumlah_digunakan']) {
                throw new Exception("Stok tidak mencukupi. Tersedia: {$currentStock}, Diminta: {$data['jumlah_digunakan']}");
            }

            // Based on your original code, it seems to be auto-approved.
            // A more complex system might set the status to 'pending' here.
            $data['status'] = 'approved';
            $data['approved_by'] = $user->unique_id;
            $data['approved_at'] = now();

            $penggunaan = $user->penggunaanBarang()->create($data);

            $this->decrementStock($gudangRecord, $data['jumlah_digunakan']);

            return $penggunaan;
        });
    }

    /**
     * Approve a pending usage request and decrement stock.
     */
    public function approve(PenggunaanBarang $penggunaan, User $approver): PenggunaanBarang
    {
        return DB::transaction(function () use ($penggunaan, $approver) {
            $gudangRecord = Gudang::where('unique_id', $penggunaan->unique_id)
                ->where('id_barang', $penggunaan->id_barang)
                ->lockForUpdate()
                ->first();

            $currentStock = $gudangRecord ? $gudangRecord->jumlah_barang : 0;

            if ($currentStock < $penggunaan->jumlah_digunakan) {
                throw new Exception("Stok tidak mencukupi untuk approval. Tersedia: {$currentStock}");
            }

            $penggunaan->update([
                'status' => 'approved',
                'approved_by' => $approver->unique_id,
                'approved_at' => now(),
            ]);

            $this->decrementStock($gudangRecord, $penggunaan->jumlah_digunakan);

            return $penggunaan;
        });
    }

    /**
     * Reject a pending usage request.
     */
    public function reject(PenggunaanBarang $penggunaan, User $rejector, ?string $reason = null): PenggunaanBarang
    {
        $keterangan = $penggunaan->keterangan;
        if ($reason) {
            $keterangan .= ' | Ditolak: ' . $reason;
        }

        $penggunaan->update([
            'status' => 'rejected',
            'approved_by' => $rejector->unique_id,
            'approved_at' => now(),
            'keterangan' => $keterangan,
        ]);

        return $penggunaan;
    }

    /**
     * Helper to atomically decrement stock.
     */
    private function decrementStock(Gudang $gudangRecord, int $amount): void
    {
        $newStock = $gudangRecord->jumlah_barang - $amount;
        if ($newStock <= 0) {
            $gudangRecord->delete();
        } else {
            // Use decrement for an atomic update.
            $gudangRecord->decrement('jumlah_barang', $amount);
        }
    }
}