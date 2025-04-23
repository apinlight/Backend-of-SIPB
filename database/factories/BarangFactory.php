<?php

namespace Database\Factories;

use App\Models\Barang;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BarangFactory extends Factory
{
    protected $model = Barang::class;

    public function definition()
    {
        return [
            'id_barang' => 'BRG' . $this->faker->unique()->numberBetween(1000, 9999),
            'nama_barang' => $this->faker->word . ' ' . $this->faker->word,
        ];
    }
}
