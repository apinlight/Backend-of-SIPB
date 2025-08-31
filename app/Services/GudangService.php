<?php

namespace App\Services;

use App\Models\Gudang;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GudangService
{
    /**
     * Creates a new stock record or adds to an existing one.
     */
    public function createOrUpdate(array $data): Gudang
    {
        return DB::transaction(function () use ($data) {
            $gudang = Gudang::firstOrNew(
                [
                    'unique_id' => $data['unique_id'],
                    'id_barang' => $data['id_barang'],
                ]
            );

            $gudang->jumlah_barang = ($gudang->jumlah_barang ?? 0) + $data['jumlah_barang'];
            $gudang->keterangan = $data['keterangan'] ?? $gudang->keterangan;
            $gudang->tipe = $data['tipe'] ?? 'biasa';
            $gudang->save();

            return $gudang;
        });
    }

    /**
     * Atomically adjusts the stock for a given record.
     */
    public function adjustStock(Gudang $gudang, User $admin, array $data): Gudang
    {
        return DB::transaction(function () use ($gudang, $admin, $data) {
            $oldStock = $gudang->jumlah_barang;
            $newStock = 0;
            $amount = $data['adjustment_amount'];

            switch ($data['adjustment_type']) {
                case 'add':
                    $newStock = $gudang->jumlah_barang + $amount;
                    break;
                case 'subtract':
                    $newStock = max(0, $gudang->jumlah_barang - $amount);
                    break;
                case 'set':
                    $newStock = $amount;
                    break;
            }

            $gudang->jumlah_barang = $newStock;
            $gudang->keterangan = $data['reason'];
            $gudang->save();

            Log::info('Stock adjustment made by admin', [
                'admin_id'            => $admin->unique_id,
                'gudang_id'           => $gudang->id, // Assuming 'id' is the primary key of the pivot table
                'target_user_id'      => $gudang->unique_id,
                'barang_id'           => $gudang->id_barang,
                'old_stock'           => $oldStock,
                'new_stock'           => $newStock,
                'adjustment_type'     => $data['adjustment_type'],
                'adjustment_amount'   => $amount,
                'reason'              => $data['reason']
            ]);

            return $gudang->fresh();
        });
    }
}