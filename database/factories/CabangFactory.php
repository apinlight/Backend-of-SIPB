<?php

namespace Database\Factories;

use App\Models\Cabang;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CabangFactory extends Factory
{
    protected $model = Cabang::class;

    public function definition()
    {
        return [
            'id_cabang' => (string) Str::ulid(),
            'nama_cabang' => $this->faker->city . ' Branch',
        ];
    }
}
