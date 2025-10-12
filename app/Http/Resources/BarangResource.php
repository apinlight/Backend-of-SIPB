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
            'harga_barang' => (float) $this->harga_barang,
            'deskripsi' => $this->deskripsi,
            'satuan' => $this->satuan,
            'batas_minimum' => (int) $this->batas_minimum,
            'jenis_barang' => JenisBarangResource::make($this->whenLoaded('jenisBarang')),

            // Include data only when it has been explicitly calculated and added by the service
            'total_stock' => $this->when(isset($this->total_stock), (int) $this->total_stock),
            'stock_status' => $this->when(isset($this->stock_status), $this->stock_status),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
