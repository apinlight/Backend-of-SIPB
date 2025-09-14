<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JenisBarang>
 */
class JenisBarangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_jenis_barang' => 'JB' . Str::ulid(),
            'nama_jenis_barang' => $this->faker->unique()->word(),
            'deskripsi' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}