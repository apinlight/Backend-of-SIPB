<?php

namespace App\Services;

use App\Models\Gudang;
use App\Models\PenggunaanBarang;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class PenggunaanBarangService
{
    /**
     * Record a new item usage, automatically approving and decrementing stock.
     */
    public function recordUsage(User $user, array $data): PenggunaanBarang
    {
        return DB::transaction(function () use ($user, $data) {
            // ✅ Query stock record for this user's branch (cabang) and item
            $gudangRecord = Gudang::where('id_cabang', $user->id_cabang)
                ->where('id_barang', $data['id_barang'])
                ->lockForUpdate() // Essential for preventing race conditions
                ->first();

            if (!$gudangRecord) {
                throw new Exception("Stok tidak ditemukan untuk barang ini di gudang Anda.");
            }

            $currentStock = $gudangRecord->jumlah_barang;

            if ($currentStock < $data['jumlah_digunakan']) {
                throw new Exception("Stok tidak mencukupi. Tersedia: {$currentStock}, Diminta: {$data['jumlah_digunakan']}");
            }

            // Auto-approved for now (no pending approval workflow)
            $data['status'] = 'approved';
            $data['approved_by'] = $user->unique_id;
            $data['approved_at'] = now();

            // Ensure we record the cabang used
            $data['id_cabang'] = $user->id_cabang;

            $penggunaan = $user->penggunaanBarang()->create($data);

            $this->decrementStock($gudangRecord, $data['jumlah_digunakan']);

            return $penggunaan;
        });
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
            $gudangRecord->jumlah_barang = $newStock;
            $gudangRecord->save();
        }
    }
}
