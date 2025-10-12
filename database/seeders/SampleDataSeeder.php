<?php

// database/seeders/SampleDataSeeder.php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\DetailPengajuan;
use App\Models\Gudang;
use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedGudangData();
        $this->seedPengajuanData();
        $this->command->info('Sample data seeded successfully!');
    }

    private function seedGudangData(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['user', 'manager']);
        })->get();

        $barangList = Barang::take(10)->get(); // Get first 10 barang

        foreach ($users as $user) {
            foreach ($barangList->random(5) as $barang) { // Random 5 items per user
                // ✅ FIX: Use a more explicit approach that works perfectly with composite keys.
                // This finds the record or creates a new instance in memory.
                $gudang = Gudang::firstOrNew([
                    'unique_id' => $user->unique_id,
                    'id_barang' => $barang->id_barang,
                ]);

                // We then set the value and save. This works for both new and existing records.
                $gudang->jumlah_barang = rand(1, 50);
                $gudang->save();
            }
        }
    }

    private function seedPengajuanData(): void
    {
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['user', 'manager']);
        })->get();

        $barangList = Barang::take(15)->get();
        $statuses = ['Menunggu Persetujuan', 'Disetujui', 'Ditolak', 'Selesai'];

        foreach ($users as $user) {
            // Create 2-3 pengajuan per user
            for ($i = 0; $i < rand(2, 3); $i++) {
                $pengajuanId = 'PGJ'.time().rand(100, 999);

                $pengajuan = Pengajuan::create([
                    'id_pengajuan' => $pengajuanId,
                    'unique_id' => $user->unique_id,
                    'status_pengajuan' => $statuses[array_rand($statuses)],
                    'tipe_pengajuan' => rand(0, 1) ? 'biasa' : 'manual',
                    'keterangan' => 'Sample pengajuan for testing',
                ]);

                // Add 2-4 detail items per pengajuan
                $selectedBarang = $barangList->random(rand(2, 4));
                foreach ($selectedBarang as $barang) {
                    DetailPengajuan::create([
                        'id_pengajuan' => $pengajuanId,
                        'id_barang' => $barang->id_barang,
                        'jumlah' => rand(1, 10),
                    ]);
                }
            }
        }
    }
}
