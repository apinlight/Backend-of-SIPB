<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'unique_id' => $this->unique_id,
            'username' => $this->username,
            'email' => $this->email,
            'id_cabang' => $this->id_cabang,
            'cabang' => $this->whenLoaded('cabang', fn () => new CabangResource($this->cabang)),
            'is_active' => $this->is_active,
            // Conditionally include roles, mapped to a simple array of names
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
