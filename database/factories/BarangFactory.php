<?php

namespace Database\Factories;

use App\Models\JenisBarang;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Barang>
 */
class BarangFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_barang' => 'BRG' . Str::ulid(),
            'nama_barang' => $this->faker->words(3, true),
            'id_jenis_barang' => JenisBarang::factory(), // Automatically create a category for it
            'deskripsi' => $this->faker->paragraph(),
            'satuan' => $this->faker->randomElement(['unit', 'pcs', 'kg', 'liter']),
            'batas_minimum' => 5,
            // ✅ FIX: Add the missing 'harga_barang' field to satisfy the database.
            'harga_barang' => $this->faker->numberBetween(10000, 5000000),
        ];
    }
}