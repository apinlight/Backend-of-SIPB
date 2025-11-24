<?php

namespace Database\Seeders;

use App\Models\Cabang;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CabangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cabangList = [
            ['id_cabang' => 'CABANG001', 'nama_cabang' => 'Pusat'],
            ['id_cabang' => 'CABANG002', 'nama_cabang' => 'South Branch'],
            ['id_cabang' => 'CABANG003', 'nama_cabang' => 'North Branch'],
            ['id_cabang' => 'CABANG004', 'nama_cabang' => 'East Branch'],
            ['id_cabang' => 'CABANG005', 'nama_cabang' => 'West Branch'],
            ['id_cabang' => 'CABANG006', 'nama_cabang' => 'Central Branch'],
        ];

        foreach ($cabangList as $cabangData) {
            Cabang::firstOrCreate(
                ['id_cabang' => $cabangData['id_cabang']],
                $cabangData
            );
        }

        $this->command->info('✅ Cabang seeded successfully!');
    }
}
