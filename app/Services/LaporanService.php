<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Pengajuan;
use App\Models\PenggunaanBarang;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LaporanService
{
    public function getSummaryReport(User $user, array $filters = []): array
    {
        $query = Pengajuan::query();
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $this->applyDateFilters($query, $filters);

        $pengajuanData = $query->with('details.barang')->get();

        $totalNilai = $pengajuanData->reduce(function ($carry, $pengajuan) {
            return $carry + $pengajuan->details->sum(function ($detail) {
                return ($detail->barang->harga_barang ?? 0) * $detail->jumlah;
            });
        }, 0);

        return [
            'total_pengajuan' => $pengajuanData->count(),
            'total_disetujui' => $pengajuanData->where('status_pengajuan', Pengajuan::STATUS_APPROVED)->count(),
            'total_menunggu' => $pengajuanData->where('status_pengajuan', Pengajuan::STATUS_PENDING)->count(),
            'total_ditolak' => $pengajuanData->where('status_pengajuan', Pengajuan::STATUS_REJECTED)->count(),
            'total_selesai' => $pengajuanData->where('status_pengajuan', Pengajuan::STATUS_COMPLETED)->count(),
            'total_nilai' => $totalNilai,
        ];
    }

    public function getBarangReport(User $user, array $filters = []): array
    {
        $query = Barang::with(['jenisBarang'])
            ->withSum(['gudangEntries as stok_saat_ini' => function ($query) use ($user, $filters) {
                // Filter by cabang for Gudang (no user relation anymore)
                if ($user->hasRole(\App\Enums\Role::ADMIN) || $user->hasRole(\App\Enums\Role::MANAGER)) {
                    if (!empty($filters['id_cabang'])) {
                        $query->where('id_cabang', $filters['id_cabang']);
                    }
                    // Otherwise show all (admin/manager can see all)
                } else {
                    // Regular user: only their cabang
                    $query->where('id_cabang', $user->id_cabang);
                }
            }], 'jumlah_barang')
            ->withSum(['detailPengajuan as total_pengadaan' => function ($query) use ($user, $filters) {
                $this->applyBranchAndRoleFiltersForRelated($query, $user, $filters, 'pengajuan.user');
                $this->applyDateFilters($query, $filters);
            }], 'jumlah');

        $details = $query->get()->map(function ($barang) {
            $stokSaatIni = $barang->stok_saat_ini ?? 0;
            $totalPengadaan = $barang->total_pengadaan ?? 0;
            $hargaBarang = $barang->harga_barang ?? 0;
            $batasMinimum = $barang->batas_minimum ?? 5;

            return [
                'id_barang' => $barang->id_barang,
                'nama_barang' => $barang->nama_barang,
                'harga_barang' => $hargaBarang,
                'jenis_barang' => $barang->jenisBarang,
                'total_pengadaan' => $totalPengadaan,
                'nilai_pengadaan' => $totalPengadaan * $hargaBarang,
                'stok_saat_ini' => $stokSaatIni,
                'nilai_stok' => $stokSaatIni * $hargaBarang,
                'batas_minimum' => $batasMinimum,
                'status_stok' => $this->getStockStatus($stokSaatIni, $batasMinimum),
            ];
        });

        $summary = [
            'total_items' => $details->count(),
            'total_pengadaan' => $details->sum('total_pengadaan'),
            'total_nilai_pengadaan' => $details->sum('nilai_pengadaan'),
            'total_stok_saat_ini' => $details->sum('stok_saat_ini'),
            'total_nilai_stok' => $details->sum('nilai_stok'),
            'items_stok_habis' => $details->where('stok_saat_ini', 0)->count(),
            'items_stok_rendah' => $details->where('status_stok', 'Rendah')->count(),
        ];

        return ['details' => $details, 'summary' => $summary];
    }

    public function getPengajuanReport(User $user, array $filters = []): array
    {
        $query = Pengajuan::with(['user.cabang', 'details.barang']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $this->applyDateFilters($query, $filters);

        $details = $query->get()->map(function ($pengajuan) {
            // Calculate total_nilai by summing (harga_barang * jumlah) for each detail
            $totalNilai = $pengajuan->details->reduce(function ($carry, $detail) {
                // Ensure barang relationship is loaded and harga_barang exists
                if ($detail->barang && $detail->jumlah > 0) {
                    $harga = $detail->barang->harga_barang ?? 0;
                    return $carry + ($harga * $detail->jumlah);
                }
                return $carry;
            }, 0);

            return [
                'id_pengajuan' => $pengajuan->id_pengajuan,
                'user' => $pengajuan->user->toArray(),
                'status_pengajuan' => $pengajuan->status_pengajuan,
                'created_at' => $pengajuan->created_at,
                'updated_at' => $pengajuan->updated_at,
                'total_items' => $pengajuan->details->sum('jumlah'),
                'total_nilai' => $totalNilai,
            ];
        });

        $summary = [
            'total_pengajuan' => $details->count(),
            'disetujui' => $details->where('status_pengajuan', 'Disetujui')->count(),
            'menunggu' => $details->where('status_pengajuan', 'Menunggu Persetujuan')->count(),
            'ditolak' => $details->where('status_pengajuan', 'Ditolak')->count(),
            'selesai' => $details->where('status_pengajuan', 'Selesai')->count(),
            'total_items' => $details->sum('total_items'),
            'total_nilai' => $details->sum('total_nilai'),
            'avg_nilai' => $details->avg('total_nilai'),
        ];

        // ✅ PERBAIKAN FINAL: Tambahkan type hint 'Collection' pada $items.
        // Ini secara eksplisit memberitahu linter bahwa $items adalah sebuah objek Collection,
        // sehingga semua error akan hilang.
        $byStatus = $details->groupBy('status_pengajuan')->map(function (Collection $items, $status) use ($details) {
            return [
                'status' => $status,
                'count' => $items->count(),
                'total_items' => $items->sum('total_items'),
                'total_nilai' => $items->sum('total_nilai'),
                'avg_nilai' => $items->avg('total_nilai'),
                'percentage' => $details->count() > 0 ? round(($items->count() / $details->count()) * 100, 1) : 0,
            ];
        })->values();

        return ['details' => $details, 'summary' => $summary, 'by_status' => $byStatus];
    }

    public function getPenggunaanReport(User $user, array $filters = []): array
    {
        $query = PenggunaanBarang::with(['user', 'cabang', 'barang.jenisBarang', 'approver']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $this->applyDateFilters($query, $filters, 'tanggal_penggunaan');
        $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
        $query->when($filters['keperluan'] ?? null, fn ($q, $keperluan) => $q->where('keperluan', 'like', "%{$keperluan}%"));
        $details = $query->orderBy('tanggal_penggunaan', 'desc')->get();

        $summary = [
            'total_penggunaan' => $details->count(),
            'total_approved' => $details->where('status', 'approved')->count(),
            'total_pending' => $details->where('status', 'pending')->count(),
            'total_rejected' => $details->where('status', 'rejected')->count(),
            'total_nilai' => $details->sum(fn ($p) => ($p->barang->harga_barang ?? 0) * $p->jumlah_digunakan),
            'total_barang_digunakan' => $details->sum('jumlah_digunakan'),
        ];

        $byBarang = $details->groupBy('id_barang')->map(fn ($items) => [
            'id_barang' => $items->first()->id_barang,
            'nama_barang' => $items->first()->barang->nama_barang,
            'jenis_barang' => $items->first()->barang->jenisBarang->nama_jenis_barang ?? null,
            'total_digunakan' => $items->sum('jumlah_digunakan'),
            'total_nilai' => $items->sum(fn ($i) => ($i->barang->harga_barang ?? 0) * $i->jumlah_digunakan),
            'frekuensi_penggunaan' => $items->count(),
            'penggunaan_approved' => $items->where('status', 'approved')->sum('jumlah_digunakan'),
            'penggunaan_pending' => $items->where('status', 'pending')->sum('jumlah_digunakan'),
        ])->values();

        $byCabang = $details->groupBy('id_cabang')->map(fn ($items, $idCabang) => [
            'id_cabang' => $idCabang,
            'nama_cabang' => optional($items->first()->cabang)->nama_cabang,
            'total_penggunaan' => $items->count(),
            'total_approved' => $items->where('status', 'approved')->count(),
            'total_pending' => $items->where('status', 'pending')->count(),
            'total_rejected' => $items->where('status', 'rejected')->count(),
            'total_barang_digunakan' => $items->sum('jumlah_digunakan'),
            'total_nilai' => $items->sum(fn ($i) => ($i->barang->harga_barang ?? 0) * $i->jumlah_digunakan),
        ])->values();

        return ['details' => $details, 'summary' => $summary, 'by_barang' => $byBarang, 'by_cabang' => $byCabang];
    }

    public function getStokReport(User $user, array $filters = []): array
    {
        $query = Gudang::with(['barang.jenisBarang', 'cabang']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $query->when($filters['stock_level'] ?? null, function ($q, $level) {
            $batasRendah = 5; // Example threshold
            switch ($level) {
                case 'empty': return $q->where('jumlah_barang', 0);
                case 'low': return $q->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', $batasRendah);
                case 'normal': return $q->where('jumlah_barang', '>', $batasRendah);
            }
        });

        $stocks = $query->get();

        $summary = [
            'total_items' => $stocks->count(),
            'total_stock' => $stocks->sum('jumlah_barang'),
            'total_value' => $stocks->sum(fn ($s) => $s->jumlah_barang * ($s->barang->harga_barang ?? 0)),
            'empty_stock' => $stocks->where('jumlah_barang', 0)->count(),
            'low_stock' => $stocks->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5)->count(),
            'normal_stock' => $stocks->where('jumlah_barang', '>', 5)->count(),
        ];

        $byBranch = $stocks->groupBy('cabang.id_cabang')->map(function ($items, $idCabang) {
            return [
                'id_cabang' => $idCabang,
                'nama_cabang' => optional($items->first()->cabang)->nama_cabang,
                'total_items' => $items->count(),
                'total_stock' => $items->sum('jumlah_barang'),
                'total_value' => $items->sum(fn ($s) => $s->jumlah_barang * ($s->barang->harga_barang ?? 0)),
                'empty_stock' => $items->where('jumlah_barang', 0)->count(),
                'low_stock' => $items->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5)->count(),
            ];
        })->values();

        return ['stocks' => $stocks, 'summary' => $summary, 'by_branch' => $byBranch];
    }

    public function getStockSummaryReport(User $user, array $filters = []): Collection
    {
        $query = Barang::with(['jenisBarang'])
            ->withSum(['gudangEntries as total_stock' => function ($q) use ($user) {
                if ($user->hasRole(\App\Enums\Role::MANAGER)) {
                    $q->whereHas('user', fn ($userQuery) => $userQuery->where('id_cabang', $user->id_cabang));
                }
            }], 'jumlah_barang');

        return $query->get()->map(function ($item) {
            $totalStock = $item->total_stock ?? 0;
            $item->stock_status = $this->getStockStatus($totalStock, $item->batas_minimum ?? 5);

            return $item;
        });
    }

    public function getCabangReport(User $user, array $filters = []): array
    {
        // Get pengajuan data grouped by branch
        $pengajuanQuery = Pengajuan::with(['user', 'details.barang']);
        $this->applyBranchAndRoleFilters($pengajuanQuery, $user, $filters);
        $this->applyDateFilters($pengajuanQuery, $filters);
        $pengajuanData = $pengajuanQuery->get();

        // Get stok data grouped by branch
        $stokQuery = Gudang::with(['barang', 'cabang']);
        $this->applyBranchAndRoleFilters($stokQuery, $user, $filters);
        $stokData = $stokQuery->get();

        // Group by branch
        $branchReport = [];
        $branches = $pengajuanData->pluck('user.id_cabang')->merge($stokData->pluck('cabang.id_cabang'))
            ->unique()
            ->filter()
            ->values();

        foreach ($branches as $idCabang) {
            $branchPengajuan = $pengajuanData->where('user.id_cabang', $idCabang);
            $branchStok = $stokData->where('cabang.id_cabang', $idCabang);

            $totalNilaiPengajuan = $branchPengajuan->reduce(function ($carry, $pengajuan) {
                return $carry + $pengajuan->details->sum(function ($detail) {
                    return ($detail->barang->harga_barang ?? 0) * $detail->jumlah;
                });
            }, 0);

            $totalStok = $branchStok->sum('jumlah_barang');
            $totalNilaiStok = $branchStok->sum(function ($stok) {
                return $stok->jumlah_barang * ($stok->barang->harga_barang ?? 0);
            });

            $branchReport[] = [
                'id_cabang' => $idCabang,
                'nama_cabang' => optional($branchStok->first()->cabang)->nama_cabang,
                'total_pengajuan' => $branchPengajuan->count(),
                'pengajuan_disetujui' => $branchPengajuan->where('status_pengajuan', Pengajuan::STATUS_APPROVED)->count(),
                'pengajuan_menunggu' => $branchPengajuan->where('status_pengajuan', Pengajuan::STATUS_PENDING)->count(),
                'pengajuan_ditolak' => $branchPengajuan->where('status_pengajuan', Pengajuan::STATUS_REJECTED)->count(),
                'pengajuan_selesai' => $branchPengajuan->where('status_pengajuan', Pengajuan::STATUS_COMPLETED)->count(),
                'total_nilai_pengajuan' => $totalNilaiPengajuan,
                'total_items_stok' => $branchStok->count(),
                'total_stok' => $totalStok,
                'total_nilai_stok' => $totalNilaiStok,
                'stok_habis' => $branchStok->where('jumlah_barang', 0)->count(),
                'stok_rendah' => $branchStok->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5)->count(),
            ];
        }

        return $branchReport;
    }

    private function getStockStatus($currentStock, $minStock): string
    {
        if ($currentStock == 0) {
            return 'Habis';
        }
        if ($currentStock <= $minStock) {
            return 'Rendah';
        }

        return 'Normal';
    }

    private function applyDateFilters(Builder $query, array $filters, string $dateColumn = 'created_at'): void
    {
        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween($dateColumn, [$filters['start_date'], $filters['end_date']]);
        }
    }

    private function applyBranchAndRoleFilters(Builder $query, User $user, array $filters): void
    {
        $query->where(function ($q) use ($user, $filters) {
            if ($user->hasRole(\App\Enums\Role::ADMIN)) {
                if (! empty($filters['id_cabang'])) {
                    $q->whereHas('user', fn ($userQuery) => $userQuery->where('id_cabang', $filters['id_cabang']));
                }
            } elseif ($user->hasRole(\App\Enums\Role::MANAGER)) {
                if (! empty($filters['id_cabang'])) {
                    $q->whereHas('user', fn ($userQuery) => $userQuery->where('id_cabang', $filters['id_cabang']));
                }
            } else { // Pengguna biasa
                $q->where('unique_id', $user->unique_id);
            }
        });
    }

    private function applyBranchAndRoleFiltersForRelated(Builder $query, User $user, array $filters, string $relation): void
    {
        $query->whereHas($relation, function ($q) use ($user, $filters) {
            if ($user->hasRole(\App\Enums\Role::MANAGER)) {
                if (! empty($filters['id_cabang'])) {
                    $q->where('id_cabang', $filters['id_cabang']);
                }
            } elseif ($user->hasRole(\App\Enums\Role::ADMIN) && ! empty($filters['id_cabang'])) {
                $q->where('id_cabang', $filters['id_cabang']);
            }
        });
    }
}
