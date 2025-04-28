<?php

namespace Database\Factories;
use App\Models\JenisBarang;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
class JenisBarangFactory extends Factory
{
    protected $model = JenisBarang::class;

    public function definition()
    {
        return [
            'id_jenis_barang' => 'JB' . $this->faker->unique()->numberBetween(1000, 9999),
            'nama_jenis_barang' => $this->faker->word,
        ];
    }
}