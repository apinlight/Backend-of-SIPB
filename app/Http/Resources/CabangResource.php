<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CabangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_cabang' => $this->id_cabang,
            'nama_cabang' => $this->nama_cabang,
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'gudang_count' => $this->when(isset($this->gudang_count), $this->gudang_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
