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
            ['id_cabang' => 'CABANG001', 'nama_cabang' => 'Kantor Pusat Wonosobo', 'is_pusat' => true],
            ['id_cabang' => 'CABANG002', 'nama_cabang' => 'Cabang Garung'],
            ['id_cabang' => 'CABANG003', 'nama_cabang' => 'Cabang Kertek'],
            ['id_cabang' => 'CABANG004', 'nama_cabang' => 'Cabang Kaliwiro'],
            ['id_cabang' => 'CABANG005', 'nama_cabang' => 'Cabang Mojotengah'],
            ['id_cabang' => 'CABANG006', 'nama_cabang' => 'Cabang Selomerto'],
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
