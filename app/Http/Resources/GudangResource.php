<?php
// app/Http/Resources/GudangResource.php
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
            'jumlah_barang' => $this->jumlah_barang,
            
            // ✅ ADD: Additional fields that might be needed
            'keterangan' => $this->keterangan ?? null,
            'tipe' => $this->tipe ?? 'biasa', // manual vs biasa
            
            // ✅ Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'barang' => new BarangResource($this->whenLoaded('barang')),
            
            // ✅ ADD: Calculated fields
            'total_nilai' => $this->when(
                $this->relationLoaded('barang') && $this->barang,
                ($this->barang->harga_barang ?? 0) * $this->jumlah_barang
            ),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
