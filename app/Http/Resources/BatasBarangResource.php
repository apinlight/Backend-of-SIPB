<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatasBarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_barang' => $this->id_barang,
            'batas_barang' => $this->batas_barang,
            
            // ✅ ADD: Relationships
            'barang' => new BarangResource($this->whenLoaded('barang')),
            
            // ✅ ADD: Dynamic stock fields (when requested)
            'current_stock' => $this->when(
                isset($this->current_stock), 
                $this->current_stock
            ),
            'available_allocation' => $this->when(
                isset($this->available_allocation), 
                $this->available_allocation
            ),
            'allocation_percentage' => $this->when(
                isset($this->allocation_percentage), 
                $this->allocation_percentage
            ),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
