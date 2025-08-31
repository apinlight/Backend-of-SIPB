<?php
// app/Http/Resources/DetailPengajuanResource.php
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
            'jumlah' => $this->jumlah,
            
            // ✅ ADD: Calculated fields
            'total_harga' => $this->when(
                $this->relationLoaded('barang') && $this->barang,
                ($this->barang->harga_barang ?? 0) * $this->jumlah
            ),
            
            // ✅ ADD: Stock information for approval process
            'stok_tersedia' => $this->when(
                isset($this->stok_tersedia),
                $this->stok_tersedia
            ),
            
            // ✅ Relationships
            'barang' => new BarangResource($this->whenLoaded('barang')),
            'pengajuan' => new PengajuanResource($this->whenLoaded('pengajuan')),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
