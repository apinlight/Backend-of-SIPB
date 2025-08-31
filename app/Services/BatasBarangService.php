<?php

namespace App\Services;

use App\Models\BatasBarang;
use App\Models\Gudang;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BatasBarangService
{
    public function create(array $data): BatasBarang
    {
        return BatasBarang::create($data);
    }

    public function update(BatasBarang $batasBarang, array $data): BatasBarang
    {
        $batasBarang->update($data);
        return $batasBarang->fresh();
    }

    /**
     * Efficiently checks the allocation limits for a user and a list of items.
     * Solves the N+1 query problem by fetching all data in two queries.
     */
    public function checkAllocation(string $userId, array $items): array
    {
        $itemIds = collect($items)->pluck('id_barang')->unique()->all();

        // 1. Fetch all current stock levels for the user and items in ONE query.
        $currentStocks = Gudang::where('unique_id', $userId)
            ->whereIn('id_barang', $itemIds)
            ->pluck('jumlah_barang', 'id_barang');

        // 2. Fetch all limits for the items in ONE query.
        $limits = BatasBarang::whereIn('id_barang', $itemIds)
            ->pluck('batas_barang', 'id_barang');

        $results = [];
        foreach ($items as $item) {
            $itemId = $item['id_barang'];
            $currentStock = $currentStocks->get($itemId, 0);
            $limit = $limits->get($itemId, PHP_INT_MAX);
            $requested = $item['jumlah'];
            $newTotal = $currentStock + $requested;

            $results[] = [
                'id_barang'     => $itemId,
                'current_stock' => $currentStock,
                'batas_barang'  => $limit,
                'requested'     => $requested,
                'new_total'     => $newTotal,
                'available'     => max(0, $limit - $currentStock),
                'is_valid'      => $newTotal <= $limit,
                'message'       => $newTotal > $limit ? "Melebihi batas ({$newTotal} > {$limit})" : 'Valid',
            ];
        }

        return $results;
    }
}