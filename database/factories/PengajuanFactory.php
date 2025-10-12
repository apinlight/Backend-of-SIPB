<?php

namespace Database\Factories;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pengajuan>
 */
class PengajuanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_pengajuan' => 'PGJ'.Str::ulid(),
            'unique_id' => User::factory(), // Automatically create a new User for this Pengajuan
            'status_pengajuan' => Pengajuan::STATUS_PENDING,
            'tipe_pengajuan' => $this->faker->randomElement(['biasa', 'manual']),
            'keterangan' => $this->faker->sentence(),
        ];
    }
}
