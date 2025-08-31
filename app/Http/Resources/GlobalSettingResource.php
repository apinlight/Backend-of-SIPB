<?php
// app/Http/Resources/GlobalSettingResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GlobalSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'setting_key' => $this->setting_key,
            'setting_value' => $this->setting_value,
            'setting_description' => $this->setting_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
