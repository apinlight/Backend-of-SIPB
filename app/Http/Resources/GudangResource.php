<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GudangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'unique_id' => $this->unique_id,
            'id_barang' => $this->id_barang,
            'jumlah_barang' => (int) $this->jumlah_barang,
            'keterangan' => $this->keterangan,
            'tipe' => $this->tipe,

            // Relationships
            'user' => UserResource::make($this->whenLoaded('user')),
            'barang' => BarangResource::make($this->whenLoaded('barang')),

            'total_nilai' => $this->whenLoaded('barang', fn () => ($this->barang->harga_barang ?? 0) * $this->jumlah_barang),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
