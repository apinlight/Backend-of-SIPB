<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JenisBarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_jenis_barang' => $this->id_jenis_barang,
            'nama_jenis_barang' => $this->nama_jenis_barang,
            'deskripsi' => $this->deskripsi,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
