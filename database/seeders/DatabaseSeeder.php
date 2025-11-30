<?php

// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Roles & Permissions
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);

    // ...existing code...

        // 2. Global Settings
        $this->call([
            GlobalSettingSeeder::class,
        ]);

        // 3. Cabang (branches)
        $this->call([
            CabangSeeder::class,
        ]);

        // 4. Base Accounts (do not change)
        if (!User::where('unique_id', 'ADMIN001')->exists()) {
            User::query()->create([
                'unique_id' => 'ADMIN001',
                'username' => 'superadmin',
                'email' => 'admin@example.com',
                'password' => 'password',
                'id_cabang' => 'CABANG001', // Pusat
                'is_active' => true,
            ])->assignRole('admin');
        }
        if (!User::where('unique_id', 'MANAGER001')->exists()) {
            User::query()->create([
                'unique_id' => 'MANAGER001',
                'username' => 'supermanager',
                'email' => 'manager@example.com',
                'password' => 'password',
                'id_cabang' => 'CABANG001', // Pusat
                'is_active' => true,
            ])->assignRole('manager');
        }
        if (!User::where('unique_id', 'USER001')->exists()) {
            User::query()->create([
                'unique_id' => 'USER001',
                'username' => 'superuser',
                'email' => 'user@example.com',
                'password' => 'password',
                'id_cabang' => 'CABANG002', // South Branch
                'is_active' => true,
            ])->assignRole('user');
        }

        // 5. Realistic Branch Users - KSPPS Al Huda
        $branchUsers = [
            [
                'unique_id' => 'USR1001',
                'username' => 'ahmad_garung',
                'email' => 'ahmad.garung@ksppsalhuda.com',
                'id_cabang' => 'CABANG002', // Garung
            ],
            [
                'unique_id' => 'USR1002',
                'username' => 'fatimah_kertek',
                'email' => 'fatimah.kertek@ksppsalhuda.com',
                'id_cabang' => 'CABANG003', // Kertek
            ],
            [
                'unique_id' => 'USR1003',
                'username' => 'usman_kaliwiro',
                'email' => 'usman.kaliwiro@ksppsalhuda.com',
                'id_cabang' => 'CABANG004', // Kaliwiro
            ],
            [
                'unique_id' => 'USR1004',
                'username' => 'siti_mojotengah',
                'email' => 'siti.mojotengah@ksppsalhuda.com',
                'id_cabang' => 'CABANG005', // Mojotengah
            ],
            [
                'unique_id' => 'USR1005',
                'username' => 'yusuf_selomerto',
                'email' => 'yusuf.selomerto@ksppsalhuda.com',
                'id_cabang' => 'CABANG006', // Selomerto
            ],
        ];
        foreach ($branchUsers as $u) {
            $user = User::updateOrCreate([
                'unique_id' => $u['unique_id']
            ], [
                'username' => $u['username'],
                'email' => $u['email'],
                'password' => 'password',
                'id_cabang' => $u['id_cabang'],
                'is_active' => true,
            ]);
            $user->assignRole('user');
        }

        // 6. Jenis Barang & Barang - KSPPS Al Huda
        $jenisBarangList = [
            ['nama_jenis_barang' => 'Elektronik & Komputer', 'is_active' => true],
            ['nama_jenis_barang' => 'ATK (Alat Tulis Kantor)', 'is_active' => true],
            ['nama_jenis_barang' => 'Kebersihan & Sanitasi', 'is_active' => true],
            ['nama_jenis_barang' => 'Konsumsi Kantor', 'is_active' => true],
            ['nama_jenis_barang' => 'Furniture & Perlengkapan', 'is_active' => true],
        ];
        $barangList = [
            // Elektronik & Komputer
            ['nama_barang' => 'Laptop Asus Vivobook', 'id_jenis_barang' => null, 'harga_barang' => 8500000, 'satuan' => 'unit', 'batas_minimum' => 1],
            ['nama_barang' => 'Printer Canon Pixma G3010', 'id_jenis_barang' => null, 'harga_barang' => 2300000, 'satuan' => 'unit', 'batas_minimum' => 1],
            ['nama_barang' => 'Mouse Wireless Logitech', 'id_jenis_barang' => null, 'harga_barang' => 150000, 'satuan' => 'pcs', 'batas_minimum' => 3],
            ['nama_barang' => 'Keyboard USB Standard', 'id_jenis_barang' => null, 'harga_barang' => 85000, 'satuan' => 'pcs', 'batas_minimum' => 2],
            ['nama_barang' => 'Flash Disk 32GB', 'id_jenis_barang' => null, 'harga_barang' => 75000, 'satuan' => 'pcs', 'batas_minimum' => 5],
            // ATK
            ['nama_barang' => 'Pulpen Snowman', 'id_jenis_barang' => null, 'harga_barang' => 3000, 'satuan' => 'pcs', 'batas_minimum' => 30],
            ['nama_barang' => 'Pensil 2B Faber Castell', 'id_jenis_barang' => null, 'harga_barang' => 2500, 'satuan' => 'pcs', 'batas_minimum' => 20],
            ['nama_barang' => 'Buku Folio Bergaris', 'id_jenis_barang' => null, 'harga_barang' => 8000, 'satuan' => 'pcs', 'batas_minimum' => 15],
            ['nama_barang' => 'Kertas HVS A4 70gr', 'id_jenis_barang' => null, 'harga_barang' => 45000, 'satuan' => 'rim', 'batas_minimum' => 10],
            ['nama_barang' => 'Map Plastik Folio', 'id_jenis_barang' => null, 'harga_barang' => 2000, 'satuan' => 'pcs', 'batas_minimum' => 20],
            ['nama_barang' => 'Tinta Stempel Artline', 'id_jenis_barang' => null, 'harga_barang' => 12000, 'satuan' => 'botol', 'batas_minimum' => 5],
            ['nama_barang' => 'Amplop Coklat F4', 'id_jenis_barang' => null, 'harga_barang' => 500, 'satuan' => 'pcs', 'batas_minimum' => 50],
            ['nama_barang' => 'Stapler HD-10', 'id_jenis_barang' => null, 'harga_barang' => 18000, 'satuan' => 'pcs', 'batas_minimum' => 5],
            ['nama_barang' => 'Isi Stapler No.10', 'id_jenis_barang' => null, 'harga_barang' => 3500, 'satuan' => 'box', 'batas_minimum' => 10],
            // Kebersihan & Sanitasi
            ['nama_barang' => 'Sapu Ijuk', 'id_jenis_barang' => null, 'harga_barang' => 20000, 'satuan' => 'pcs', 'batas_minimum' => 3],
            ['nama_barang' => 'Pel Kain', 'id_jenis_barang' => null, 'harga_barang' => 25000, 'satuan' => 'pcs', 'batas_minimum' => 2],
            ['nama_barang' => 'Pembersih Lantai So Klin', 'id_jenis_barang' => null, 'harga_barang' => 18000, 'satuan' => 'liter', 'batas_minimum' => 5],
            ['nama_barang' => 'Sabun Cuci Tangan Lifebuoy', 'id_jenis_barang' => null, 'harga_barang' => 15000, 'satuan' => 'botol', 'batas_minimum' => 8],
            ['nama_barang' => 'Tisu Paseo 250 Sheet', 'id_jenis_barang' => null, 'harga_barang' => 12000, 'satuan' => 'box', 'batas_minimum' => 10],
            ['nama_barang' => 'Lap Microfiber', 'id_jenis_barang' => null, 'harga_barang' => 8000, 'satuan' => 'pcs', 'batas_minimum' => 10],
            // Konsumsi Kantor
            ['nama_barang' => 'Kopi Kapal Api Special Mix', 'id_jenis_barang' => null, 'harga_barang' => 25000, 'satuan' => 'box', 'batas_minimum' => 5],
            ['nama_barang' => 'Teh Celup Sariwangi', 'id_jenis_barang' => null, 'harga_barang' => 18000, 'satuan' => 'box', 'batas_minimum' => 5],
            ['nama_barang' => 'Gula Pasir Gulaku', 'id_jenis_barang' => null, 'harga_barang' => 15000, 'satuan' => 'kg', 'batas_minimum' => 3],
            ['nama_barang' => 'Air Mineral Galon Aqua', 'id_jenis_barang' => null, 'harga_barang' => 20000, 'satuan' => 'galon', 'batas_minimum' => 5],
            ['nama_barang' => 'Biskuit Khong Guan', 'id_jenis_barang' => null, 'harga_barang' => 28000, 'satuan' => 'kaleng', 'batas_minimum' => 3],
            // Furniture & Perlengkapan
            ['nama_barang' => 'Kursi Kantor Putar', 'id_jenis_barang' => null, 'harga_barang' => 650000, 'satuan' => 'unit', 'batas_minimum' => 1],
            ['nama_barang' => 'Meja Kerja Kayu', 'id_jenis_barang' => null, 'harga_barang' => 1200000, 'satuan' => 'unit', 'batas_minimum' => 1],
            ['nama_barang' => 'Rak Arsip Besi', 'id_jenis_barang' => null, 'harga_barang' => 1500000, 'satuan' => 'unit', 'batas_minimum' => 1],
        ];
        $jenisMap = [];
        foreach ($jenisBarangList as $j) {
            $jb = JenisBarang::firstOrCreate([
                'nama_jenis_barang' => $j['nama_jenis_barang']
            ], $j);
            $jenisMap[$j['nama_jenis_barang']] = $jb->id_jenis_barang;
        }
        // Assign jenis to barang - KSPPS
        $barangJenis = [
            'Laptop Asus Vivobook' => 'Elektronik & Komputer',
            'Printer Canon Pixma G3010' => 'Elektronik & Komputer',
            'Mouse Wireless Logitech' => 'Elektronik & Komputer',
            'Keyboard USB Standard' => 'Elektronik & Komputer',
            'Flash Disk 32GB' => 'Elektronik & Komputer',
            'Pulpen Snowman' => 'ATK (Alat Tulis Kantor)',
            'Pensil 2B Faber Castell' => 'ATK (Alat Tulis Kantor)',
            'Buku Folio Bergaris' => 'ATK (Alat Tulis Kantor)',
            'Kertas HVS A4 70gr' => 'ATK (Alat Tulis Kantor)',
            'Map Plastik Folio' => 'ATK (Alat Tulis Kantor)',
            'Tinta Stempel Artline' => 'ATK (Alat Tulis Kantor)',
            'Amplop Coklat F4' => 'ATK (Alat Tulis Kantor)',
            'Stapler HD-10' => 'ATK (Alat Tulis Kantor)',
            'Isi Stapler No.10' => 'ATK (Alat Tulis Kantor)',
            'Sapu Ijuk' => 'Kebersihan & Sanitasi',
            'Pel Kain' => 'Kebersihan & Sanitasi',
            'Pembersih Lantai So Klin' => 'Kebersihan & Sanitasi',
            'Sabun Cuci Tangan Lifebuoy' => 'Kebersihan & Sanitasi',
            'Tisu Paseo 250 Sheet' => 'Kebersihan & Sanitasi',
            'Lap Microfiber' => 'Kebersihan & Sanitasi',
            'Kopi Kapal Api Special Mix' => 'Konsumsi Kantor',
            'Teh Celup Sariwangi' => 'Konsumsi Kantor',
            'Gula Pasir Gulaku' => 'Konsumsi Kantor',
            'Air Mineral Galon Aqua' => 'Konsumsi Kantor',
            'Biskuit Khong Guan' => 'Konsumsi Kantor',
            'Kursi Kantor Putar' => 'Furniture & Perlengkapan',
            'Meja Kerja Kayu' => 'Furniture & Perlengkapan',
            'Rak Arsip Besi' => 'Furniture & Perlengkapan',
        ];
        foreach ($barangList as &$b) {
            $b['id_jenis_barang'] = $jenisMap[$barangJenis[$b['nama_barang']]];
        }
        unset($b);
        foreach ($barangList as $b) {
            \App\Models\Barang::firstOrCreate([
                'nama_barang' => $b['nama_barang'],
                'id_jenis_barang' => $b['id_jenis_barang'],
            ], $b);
        }

        // 8. Realistic Pengajuan & Gudang for branch users (after all users/barang are created)
        $pengajuanStatus = ['Menunggu Persetujuan', 'Disetujui', 'Ditolak', 'Selesai'];
        $allBarang = \App\Models\Barang::all();
        foreach ($branchUsers as $u) {
            $user = \App\Models\User::where('unique_id', $u['unique_id'])->first();
            // Seed 2 pengajuan per user
            for ($i = 1; $i <= 2; $i++) {
                $pengajuanId = 'PGJ' . $u['unique_id'] . $i;
                $status = $pengajuanStatus[$i % count($pengajuanStatus)];
                $pengajuan = \App\Models\Pengajuan::updateOrCreate([
                    'id_pengajuan' => $pengajuanId
                ], [
                    'unique_id' => $user->unique_id,
                    'status_pengajuan' => $status,
                    'tipe_pengajuan' => $i % 2 === 0 ? 'manual' : 'biasa',
                    'keterangan' => 'Pengajuan ke-' . $i . ' oleh ' . $user->username,
                ]);
                // Add 2-3 detail items per pengajuan
                $barangForPengajuan = $allBarang->random( min(3, $allBarang->count()) );
                foreach ($barangForPengajuan as $barang) {
                    \App\Models\DetailPengajuan::updateOrCreate([
                        'id_pengajuan' => $pengajuanId,
                        'id_barang' => $barang->id_barang,
                    ], [
                        'jumlah' => rand(2, 10),
                        'keterangan' => 'Permintaan barang ' . $barang->nama_barang,
                    ]);
                }
            }
            // Seed Gudang stock for each barang for this user's cabang
            foreach ($allBarang as $barang) {
                \App\Models\Gudang::updateOrCreate([
                    'id_cabang' => $user->id_cabang,
                    'id_barang' => $barang->id_barang,
                ], [
                    'jumlah_barang' => rand(5, 30),
                    'keterangan' => 'Stok awal untuk ' . $barang->nama_barang,
                ]);
            }
        }

        // Also seed Gudang for USER001 (the default user)
        $user001 = \App\Models\User::where('unique_id', 'USER001')->first();
        if ($user001 && $user001->id_cabang) {
            foreach ($allBarang as $barang) {
                \App\Models\Gudang::updateOrCreate([
                    'id_cabang' => $user001->id_cabang,
                    'id_barang' => $barang->id_barang,
                ], [
                    'jumlah_barang' => rand(10, 40),
                    'keterangan' => 'Stok awal untuk ' . $barang->nama_barang,
                ]);
            }
        }

        // 9. Batas Barang
        $this->call([
            BatasBarangSeeder::class,
        ]);

        // 10. Realistic Pengajuan & Gudang (see SampleDataSeeder for more advanced logic)
        // You may optionally add more realistic transactional data here or in a new seeder.

        $this->command->info('🎯 Database seeding completed for KSPPS Al Huda Wonosobo!');
        $this->command->info('📋 Default credentials (3 super user - jangan diubah):');
        $this->command->info('   Super Admin: admin@example.com / password');
        $this->command->info('   Super Manager: manager@example.com / password');
        $this->command->info('   Super User: user@example.com / password');
        $this->command->info('🏢 Cabang: Pusat, Garung, Kertek, Kaliwiro, Mojotengah, Selomerto');
    }
}
