<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->unique_id,
            'username' => $this->username,
            'email' => $this->email,
            'branch_name' => $this->branch_name,
            'roles' => $this->getRoleNames(),
        ];
    }
}
