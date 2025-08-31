<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanBarangInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // This resource now expects a pre-calculated data object from the service
        return [
            'barang' => BarangResource::collection($this->resource->barang),
            'userStock' => $this->resource->userStock,
            'adminStock' => $this->resource->adminStock,
            'barangLimits' => $this->resource->barangLimits,
            'monthlyInfo' => [
                'limit' => $this->resource->monthlyLimit,
                'pengajuanCount' => $this->resource->pengajuanCount,
                'pendingCount' => $this->resource->pendingCount,
            ],
        ];
    }
}