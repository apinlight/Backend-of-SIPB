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

    public function getBarangReport(User $user, array $filters = []): Collection
    {
        $query = Barang::with(['jenisBarang'])
            ->withSum(['gudangEntries as stok_saat_ini' => function ($query) use ($user, $filters) {
                $this->applyBranchAndRoleFiltersForRelated($query, $user, $filters, 'user');
            }], 'jumlah_barang')
            ->withSum(['detailPengajuan as total_pengadaan' => function ($query) use ($user, $filters) {
                $this->applyBranchAndRoleFiltersForRelated($query, $user, $filters, 'pengajuan.user');
                $this->applyDateFilters($query, $filters);
            }], 'jumlah');

        return $query->get()->map(function ($barang) {
            $stokSaatIni = $barang->stok_saat_ini ?? 0;
            $totalPengadaan = $barang->total_pengadaan ?? 0;
            $hargaBarang = $barang->harga_barang ?? 0;

            return [
                'id_barang' => $barang->id_barang,
                'nama_barang' => $barang->nama_barang,
                'harga_barang' => $hargaBarang,
                'jenis_barang' => $barang->jenisBarang,
                'total_pengadaan' => $totalPengadaan,
                'nilai_pengadaan' => $totalPengadaan * $hargaBarang,
                'stok_saat_ini' => $stokSaatIni,
                'nilai_stok' => $stokSaatIni * $hargaBarang,
                'batas_minimum' => $barang->batas_minimum ?? 5,
            ];
        });
    }

    public function getPengajuanReport(User $user, array $filters = []): Collection
    {
        $query = Pengajuan::with(['user', 'details.barang']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $this->applyDateFilters($query, $filters);

        return $query->get()->map(function ($pengajuan) {
            return [
                'id_pengajuan' => $pengajuan->id_pengajuan,
                'user' => $pengajuan->user,
                'status_pengajuan' => $pengajuan->status_pengajuan,
                'created_at' => $pengajuan->created_at,
                'updated_at' => $pengajuan->updated_at,
                'total_items' => $pengajuan->details->sum('jumlah'),
                'total_nilai' => $pengajuan->details->sum(fn($detail) => ($detail->barang->harga_barang ?? 0) * $detail->jumlah),
            ];
        });
    }

    public function getCabangReport(User $user, array $filters = []): Collection
    {
        $query = Pengajuan::with(['user', 'details.barang']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $this->applyDateFilters($query, $filters);
        
        return $query->get()->groupBy('user.branch_name')->map(function ($pengajuanPerCabang, $branchName) {
            $totalNilai = $pengajuanPerCabang->reduce(function($carry, $pengajuan) {
                return $carry + $pengajuan->details->sum(fn($detail) => ($detail->barang->harga_barang ?? 0) * $detail->jumlah);
            }, 0);

            return [
                'branch_name' => $branchName,
                'total_pengajuan' => $pengajuanPerCabang->count(),
                'total_disetujui' => $pengajuanPerCabang->where('status_pengajuan', Pengajuan::STATUS_APPROVED)->count(),
                'total_menunggu' => $pengajuanPerCabang->where('status_pengajuan', Pengajuan::STATUS_PENDING)->count(),
                'total_ditolak' => $pengajuanPerCabang->where('status_pengajuan', Pengajuan::STATUS_REJECTED)->count(),
                'total_selesai' => $pengajuanPerCabang->where('status_pengajuan', Pengajuan::STATUS_COMPLETED)->count(),
                'total_nilai' => $totalNilai
            ];
        })->values();
    }

    public function getPenggunaanReport(User $user, array $filters = []): array
    {
        $query = PenggunaanBarang::with(['user', 'barang.jenisBarang', 'approver']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $this->applyDateFilters($query, $filters, 'tanggal_penggunaan');

        $query->when($filters['status'] ?? null, fn($q, $status) => $q->where('status', $status));
        $query->when($filters['keperluan'] ?? null, fn($q, $keperluan) => $q->where('keperluan', 'like', "%{$keperluan}%"));
        
        $penggunaanData = $query->orderBy('tanggal_penggunaan', 'desc')->get();
        
        $summary = [
            'total_penggunaan' => $penggunaanData->count(),
            'total_approved' => $penggunaanData->where('status', 'approved')->count(),
            'total_pending' => $penggunaanData->where('status', 'pending')->count(),
            'total_rejected' => $penggunaanData->where('status', 'rejected')->count(),
            'total_nilai' => $penggunaanData->sum(fn($p) => ($p->barang->harga_barang ?? 0) * $p->jumlah_digunakan),
            'total_barang_digunakan' => $penggunaanData->sum('jumlah_digunakan'),
        ];

        return [
            'summary' => $summary,
            'details' => $penggunaanData,
        ];
    }

    public function getStokReport(User $user, array $filters = []): array
    {
        $query = Gudang::with(['barang.jenisBarang', 'user']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);

        $query->when($filters['stock_level'] ?? null, function ($q, $level) {
            switch ($level) {
                case 'empty': return $q->where('jumlah_barang', 0);
                case 'low': return $q->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5); // Example threshold
                case 'normal': return $q->where('jumlah_barang', '>', 5);
            }
        });

        $stocks = $query->get();
        $summary = [
            'total_items' => $stocks->count(),
            'total_stock' => $stocks->sum('jumlah_barang'),
            'total_value' => $stocks->sum(fn($s) => $s->jumlah_barang * ($s->barang->harga_barang ?? 0)),
        ];

        return [
            'summary' => $summary,
            'details' => $stocks,
        ];
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

    private function applyDateFilters(Builder $query, array $filters, string $dateColumn = 'created_at'): void
    {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween($dateColumn, [$filters['start_date'], $filters['end_date']]);
        }
    }

    private function applyBranchAndRoleFilters(Builder $query, User $user, array $filters): void
    {
        $query->where(function ($q) use ($user, $filters) {
            if ($user->hasRole('admin')) {
                if (!empty($filters['branch'])) {
                    $q->whereHas('user', fn($userQuery) => $userQuery->where('branch_name', $filters['branch']));
                }
                // If no branch filter, admin sees all, so no query constraint needed here.
            } elseif ($user->hasRole('manager')) {
                $q->whereHas('user', fn($userQuery) => $userQuery->where('branch_name', $user->branch_name));
            } else { // Regular user
                $q->where('unique_id', $user->unique_id);
            }
        });
    }

    private function applyBranchAndRoleFiltersForRelated(Builder $query, User $user, array $filters, string $relation): void
    {
        $query->whereHas($relation, function($q) use ($user, $filters) {
            if ($user->hasRole('manager')) {
                $q->where('branch_name', $user->branch_name);
            } elseif ($user->hasRole('admin') && !empty($filters['branch'])) {
                $q->where('branch_name', $filters['branch']);
            }
        });
    }
    
    private function getStockStatus($currentStock, $minStock): string
    {
        if ($currentStock == 0) return 'out_of_stock';
        if ($currentStock <= $minStock) return 'low_stock';
        return 'normal';
    }
}