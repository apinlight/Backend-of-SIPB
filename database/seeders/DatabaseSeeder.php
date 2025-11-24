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

        // 5. Realistic Branch Users
        $branchUsers = [
            [
                'unique_id' => 'USR1001',
                'username' => 'budi',
                'email' => 'budi.south@company.com',
                'id_cabang' => 'CABANG002', // South Branch
            ],
            [
                'unique_id' => 'USR1002',
                'username' => 'siti',
                'email' => 'siti.north@company.com',
                'id_cabang' => 'CABANG003', // North Branch
            ],
            [
                'unique_id' => 'USR1003',
                'username' => 'agus',
                'email' => 'agus.east@company.com',
                'id_cabang' => 'CABANG004', // East Branch
            ],
            [
                'unique_id' => 'USR1004',
                'username' => 'lina',
                'email' => 'lina.west@company.com',
                'id_cabang' => 'CABANG005', // West Branch
            ],
            [
                'unique_id' => 'USR1005',
                'username' => 'yusuf',
                'email' => 'yusuf.central@company.com',
                'id_cabang' => 'CABANG006', // Central Branch
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

        // 6. Jenis Barang & Barang (realistic)
        $jenisBarangList = [
            ['nama_jenis_barang' => 'Elektronik', 'is_active' => true],
            ['nama_jenis_barang' => 'ATK', 'is_active' => true],
            ['nama_jenis_barang' => 'Kebersihan', 'is_active' => true],
            ['nama_jenis_barang' => 'Makanan', 'is_active' => true],
            ['nama_jenis_barang' => 'Minuman', 'is_active' => true],
        ];
        $barangList = [
            // Elektronik
            ['nama_barang' => 'Laptop Lenovo ThinkPad', 'id_jenis_barang' => null, 'harga_barang' => 12000000, 'satuan' => 'unit', 'batas_minimum' => 2],
            ['nama_barang' => 'Printer Epson L3110', 'id_jenis_barang' => null, 'harga_barang' => 2500000, 'satuan' => 'unit', 'batas_minimum' => 1],
            ['nama_barang' => 'Proyektor BenQ', 'id_jenis_barang' => null, 'harga_barang' => 5000000, 'satuan' => 'unit', 'batas_minimum' => 1],
            // ATK
            ['nama_barang' => 'Pulpen Standard', 'id_jenis_barang' => null, 'harga_barang' => 3500, 'satuan' => 'pcs', 'batas_minimum' => 20],
            ['nama_barang' => 'Buku Tulis Sidu', 'id_jenis_barang' => null, 'harga_barang' => 6000, 'satuan' => 'pcs', 'batas_minimum' => 15],
            ['nama_barang' => 'Map Folder', 'id_jenis_barang' => null, 'harga_barang' => 2500, 'satuan' => 'pcs', 'batas_minimum' => 10],
            // Kebersihan
            ['nama_barang' => 'Sapu Lantai', 'id_jenis_barang' => null, 'harga_barang' => 18000, 'satuan' => 'pcs', 'batas_minimum' => 3],
            ['nama_barang' => 'Pel Lantai', 'id_jenis_barang' => null, 'harga_barang' => 22000, 'satuan' => 'pcs', 'batas_minimum' => 2],
            ['nama_barang' => 'Cairan Pembersih Lantai', 'id_jenis_barang' => null, 'harga_barang' => 15000, 'satuan' => 'liter', 'batas_minimum' => 5],
            // Makanan
            ['nama_barang' => 'Biskuit Roma Kelapa', 'id_jenis_barang' => null, 'harga_barang' => 12000, 'satuan' => 'pak', 'batas_minimum' => 10],
            ['nama_barang' => 'Roti Tawar Sari Roti', 'id_jenis_barang' => null, 'harga_barang' => 18000, 'satuan' => 'bungkus', 'batas_minimum' => 5],
            // Minuman
            ['nama_barang' => 'Air Mineral Aqua 600ml', 'id_jenis_barang' => null, 'harga_barang' => 4000, 'satuan' => 'botol', 'batas_minimum' => 24],
            ['nama_barang' => 'Teh Botol Sosro', 'id_jenis_barang' => null, 'harga_barang' => 5000, 'satuan' => 'botol', 'batas_minimum' => 12],
        ];
        $jenisMap = [];
        foreach ($jenisBarangList as $j) {
            $jb = JenisBarang::firstOrCreate([
                'nama_jenis_barang' => $j['nama_jenis_barang']
            ], $j);
            $jenisMap[$j['nama_jenis_barang']] = $jb->id_jenis_barang;
        }
        // Assign jenis to barang
        $barangJenis = [
            'Laptop Lenovo ThinkPad' => 'Elektronik',
            'Printer Epson L3110' => 'Elektronik',
            'Proyektor BenQ' => 'Elektronik',
            'Pulpen Standard' => 'ATK',
            'Buku Tulis Sidu' => 'ATK',
            'Map Folder' => 'ATK',
            'Sapu Lantai' => 'Kebersihan',
            'Pel Lantai' => 'Kebersihan',
            'Cairan Pembersih Lantai' => 'Kebersihan',
            'Biskuit Roma Kelapa' => 'Makanan',
            'Roti Tawar Sari Roti' => 'Makanan',
            'Air Mineral Aqua 600ml' => 'Minuman',
            'Teh Botol Sosro' => 'Minuman',
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

        $this->command->info('🎯 Database seeding completed with real-world data!');
        $this->command->info('📋 Default credentials:');
        $this->command->info('   Admin: admin@example.com / password');
        $this->command->info('   Manager: manager@example.com / password');
        $this->command->info('   User: user@example.com / password');
    }
}
