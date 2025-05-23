<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatasBarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_barang'    => $this->id_barang,
            'batas_barang' => $this->batas_barang,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
