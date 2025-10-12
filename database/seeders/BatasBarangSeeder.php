<?php

// database/seeders/BatasBarangSeeder.php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\BatasBarang;
use Illuminate\Database\Seeder;

class BatasBarangSeeder extends Seeder
{
    public function run(): void
    {
        // Get all existing barang
        $barangList = Barang::all();

        foreach ($barangList as $barang) {
            // Set random limits for each barang (for testing)
            $batasBarang = rand(10, 100); // Random limit between 10-100

            BatasBarang::updateOrCreate(
                ['id_barang' => $barang->id_barang],
                ['batas_barang' => $batasBarang]
            );
        }

        $this->command->info('Batas barang seeded for '.$barangList->count().' items!');
    }
}
