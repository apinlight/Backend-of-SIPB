<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\Pengajuan;
use App\Models\PenggunaanBarang;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
                $this->applyBranchAndRoleFiltersForRelated($query, $user, $filters, 'user');
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
        $query = Pengajuan::with(['user', 'details.barang']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $this->applyDateFilters($query, $filters);

        $details = $query->get()->map(function ($pengajuan) {
            return [
                'id_pengajuan' => $pengajuan->id_pengajuan,
                'user' => $pengajuan->user->toArray(),
                'status_pengajuan' => $pengajuan->status_pengajuan,
                'created_at' => $pengajuan->created_at,
                'updated_at' => $pengajuan->updated_at,
                'total_items' => $pengajuan->details->sum('jumlah'),
                'total_nilai' => $pengajuan->details->sum(fn($d) => ($d->barang->harga_barang ?? 0) * $d->jumlah),
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
        $byStatus = $details->groupBy('status_pengajuan')->map(function(Collection $items, $status) use ($details) {
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
        $query = PenggunaanBarang::with(['user', 'barang.jenisBarang', 'approver']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $this->applyDateFilters($query, $filters, 'tanggal_penggunaan');
        $query->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status));
        $query->when($filters['keperluan'] ?? null, fn($q, $keperluan) => $q->where('keperluan', 'like', "%{$keperluan}%"));
        $details = $query->orderBy('tanggal_penggunaan', 'desc')->get();

        $summary = [
            'total_penggunaan' => $details->count(),
            'total_approved' => $details->where('status', 'approved')->count(),
            'total_pending' => $details->where('status', 'pending')->count(),
            'total_rejected' => $details->where('status', 'rejected')->count(),
            'total_nilai' => $details->sum(fn($p) => ($p->barang->harga_barang ?? 0) * $p->jumlah_digunakan),
            'total_barang_digunakan' => $details->sum('jumlah_digunakan'),
        ];
        
        $byBarang = $details->groupBy('id_barang')->map(fn($items) => [
            'id_barang' => $items->first()->id_barang,
            'nama_barang' => $items->first()->barang->nama_barang,
            'jenis_barang' => $items->first()->barang->jenisBarang->nama_jenis_barang ?? null,
            'total_digunakan' => $items->sum('jumlah_digunakan'),
            'total_nilai' => $items->sum(fn($i) => ($i->barang->harga_barang ?? 0) * $i->jumlah_digunakan),
            'frekuensi_penggunaan' => $items->count(),
            'penggunaan_approved' => $items->where('status', 'approved')->sum('jumlah_digunakan'),
            'penggunaan_pending' => $items->where('status', 'pending')->sum('jumlah_digunakan'),
        ])->values();

        $byCabang = $details->groupBy('user.branch_name')->map(fn($items, $branch) => [
            'branch_name' => $branch,
            'total_penggunaan' => $items->count(),
            'total_approved' => $items->where('status', 'approved')->count(),
            'total_pending' => $items->where('status', 'pending')->count(),
            'total_rejected' => $items->where('status', 'rejected')->count(),
            'total_barang_digunakan' => $items->sum('jumlah_digunakan'),
            'total_nilai' => $items->sum(fn($i) => ($i->barang->harga_barang ?? 0) * $i->jumlah_digunakan),
        ])->values();

        return ['details' => $details, 'summary' => $summary, 'by_barang' => $byBarang, 'by_cabang' => $byCabang];
    }
    
    public function getStokReport(User $user, array $filters = []): array
    {
        $query = Gudang::with(['barang.jenisBarang', 'user']);
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
            'total_value' => $stocks->sum(fn($s) => $s->jumlah_barang * ($s->barang->harga_barang ?? 0)),
            'empty_stock' => $stocks->where('jumlah_barang', 0)->count(),
            'low_stock' => $stocks->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5)->count(),
            'normal_stock' => $stocks->where('jumlah_barang', '>', 5)->count(),
        ];

        $byBranch = $stocks->groupBy('user.branch_name')->map(function($items, $branchName) {
            return [
                'branch_name' => $branchName,
                'total_items' => $items->count(),
                'total_stock' => $items->sum('jumlah_barang'),
                'total_value' => $items->sum(fn($s) => $s->jumlah_barang * ($s->barang->harga_barang ?? 0)),
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
                if ($user->hasRole('manager')) {
                    $q->whereHas('user', fn($userQuery) => $userQuery->where('branch_name', $user->branch_name));
                }
            }], 'jumlah_barang');

        return $query->get()->map(function ($item) {
            $totalStock = $item->total_stock ?? 0;
            $item->stock_status = $this->getStockStatus($totalStock, $item->batas_minimum ?? 5);
            return $item;
        });
    }
    
    private function getStockStatus($currentStock, $minStock): string {
        if ($currentStock == 0) return 'Habis';
        if ($currentStock <= $minStock) return 'Rendah';
        return 'Normal';
    }

    private function applyDateFilters(Builder $query, array $filters, string $dateColumn = 'created_at'): void {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween($dateColumn, [$filters['start_date'], $filters['end_date']]);
        }
    }

    private function applyBranchAndRoleFilters(Builder $query, User $user, array $filters): void {
        $query->where(function ($q) use ($user, $filters) {
            if ($user->hasRole('admin')) {
                if (!empty($filters['branch'])) {
                    $q->whereHas('user', fn($userQuery) => $userQuery->where('branch_name', $filters['branch']));
                }
            } elseif ($user->hasRole('manager')) {
                if (!empty($filters['branch'])) {
                    $q->whereHas('user', fn($userQuery) => $userQuery->where('branch_name', $filters['branch']));
                }
            } else { // Pengguna biasa
                $q->where('unique_id', $user->unique_id);
            }
        });
    }

    private function applyBranchAndRoleFiltersForRelated(Builder $query, User $user, array $filters, string $relation): void {
        $query->whereHas($relation, function($q) use ($user, $filters) {
            if ($user->hasRole('manager')) {
                if (!empty($filters['branch'])) {
                    $q->where('branch_name', $filters['branch']);
                }
            } elseif ($user->hasRole('admin') && !empty($filters['branch'])) {
                $q->where('branch_name', $filters['branch']);
            }
        });
    }
}