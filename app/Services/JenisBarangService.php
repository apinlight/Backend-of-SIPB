<?php

namespace App\Services;

use App\Models\JenisBarang;
use Exception;

class JenisBarangService
{
    public function create(array $data): JenisBarang
    {
        $data['is_active'] = $data['is_active'] ?? true;

        return JenisBarang::create($data);
    }

    public function update(JenisBarang $jenisBarang, array $data): JenisBarang
    {
        $jenisBarang->update($data);

        return $jenisBarang->fresh();
    }

    public function delete(JenisBarang $jenisBarang): void
    {
        // Check if the category is used by any items.
        if ($jenisBarang->barang()->exists()) {
            throw new Exception('Cannot delete a category that is currently in use by items.');
        }

        $jenisBarang->delete();
    }

    public function toggleStatus(JenisBarang $jenisBarang): JenisBarang
    {
        $jenisBarang->update(['is_active' => ! $jenisBarang->is_active]);

        return $jenisBarang;
    }
}
