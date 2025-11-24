<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanBarangInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // This resource expects the new structure from PengajuanService::getInfoForForm()
        return [
            'barang' => BarangResource::collection($this->resource->barang),
            'user_info' => $this->resource->user_info,
            'monthly_info' => $this->resource->monthly_info,
        ];
    }
}
