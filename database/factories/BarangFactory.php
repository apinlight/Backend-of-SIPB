<?php

namespace Database\Factories;

use App\Models\JenisBarang;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BarangFactory extends Factory
{
    public function definition(): array
    {
        return [
            'id_barang'       => 'BRG' . Str::ulid(),
            'nama_barang'     => $this->faker->words(3, true),
            'id_jenis_barang' => JenisBarang::factory(),
            'harga_barang'    => $this->faker->numberBetween(10000, 5000000),
            'deskripsi'       => $this->faker->paragraph(),
            'satuan'          => $this->faker->randomElement(['unit', 'pcs', 'kg', 'liter']),
            'batas_minimum'   => 5,
        ];
    }
}