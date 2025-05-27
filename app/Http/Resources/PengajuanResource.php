<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pengajuan'     => $this->id_pengajuan,
            'unique_id'        => $this->unique_id,
            'status_pengajuan' => $this->status_pengajuan,
            'tipe_pengajuan'   => $this->tipe_pengajuan,
            'user'             => new UserResource($this->whenLoaded('user')),
            'details'          => DetailPengajuanResource::collection($this->whenLoaded('details')),
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,
        ];
    }
}
