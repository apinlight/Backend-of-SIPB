<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BatasPengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_barang'       => $this->id_barang,
            'batas_pengajuan' => $this->batas_pengajuan,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
