<?php
// app/Http/Resources/BarangResource.php
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
            'id_jenis_barang' => $this->id_jenis_barang, // ✅ ADD: For form editing
            
            // ✅ FIX: Use jenis_barang (what frontend expects)
            'jenis_barang' => $this->whenLoaded('jenis_barang', [
                'id_jenis_barang' => $this->jenis_barang->id_jenis_barang ?? null,
                'nama_jenis_barang' => $this->jenis_barang->nama_jenis_barang ?? null,
            ]),
            
            // ✅ ADD: Additional fields that might be needed
            'total_stock' => $this->when(
                isset($this->total_stock), 
                $this->total_stock
            ),
            'is_low_stock' => $this->when(
                method_exists($this, 'isLowStock'), 
                $this->isLowStock()
            ),
            'batas_barang' => $this->whenLoaded('batasBarang', 
                $this->batasBarang->batas_barang ?? null
            ),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
