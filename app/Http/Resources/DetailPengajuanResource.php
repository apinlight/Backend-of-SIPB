<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\BarangResource;
use App\Http\Resources\PengajuanResource;

class DetailPengajuanResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'id_barang' => $this->id_barang,
            'jumlah'    => $this->jumlah,
            'barang'    => new BarangResource($this->whenLoaded('barang')),
            'pengajuan' => new PengajuanResource($this->whenLoaded('pengajuan')),
        ];
    }
}
