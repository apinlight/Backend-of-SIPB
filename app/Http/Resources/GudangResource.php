<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GudangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'unique_id'     => $this->unique_id,
            'id_barang'     => $this->id_barang,
            'jumlah_barang' => $this->jumlah_barang,
            'user'          => new UserResource($this->whenLoaded('user')),
            'barang'        => new BarangResource($this->whenLoaded('barang')),
        ];
    }
}
