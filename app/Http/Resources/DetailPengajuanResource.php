<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailPengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_barang' => $this->id_barang,
            'jumlah' => $this->jumlah,
        ];
    }
}
