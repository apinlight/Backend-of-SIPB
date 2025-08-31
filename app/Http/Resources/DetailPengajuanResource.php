<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailPengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pengajuan' => $this->id_pengajuan,
            'id_barang' => $this->id_barang,
            'jumlah' => (int) $this->jumlah,
            'keterangan' => $this->keterangan,

            // Simple calculation is acceptable here
            'total_harga' => $this->whenLoaded('barang', fn() => ($this->barang->harga_barang ?? 0) * $this->jumlah),
            
            // Relationships
            'barang' => BarangResource::make($this->whenLoaded('barang')),
            // Avoid circular dependency by not including the full PengajuanResource here unless necessary
            'pengajuan' => $this->whenLoaded('pengajuan', [
                'id_pengajuan' => $this->pengajuan->id_pengajuan,
                'status_pengajuan' => $this->pengajuan->status_pengajuan,
            ]),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}