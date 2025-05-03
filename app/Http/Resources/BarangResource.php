<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_barang' => $this->id_barang,
            'nama_barang' => $this->nama_barang,
            'harga_barang' => $this->harga_barang,
            'jenis_barang' => [
                'id' => $this->jenisBarang->id_jenis_barang ?? null,
                'nama' => $this->jenisBarang->nama_jenis_barang ?? null,
            ],
        ];
    }
}