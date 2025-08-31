<?php

namespace App\Services;

use App\Models\Pengajuan;
use App\Models\PenggunaanBarang;
use App\Models\Gudang;
use App\Models\Barang;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanService
{
    /**
     * Generates a summary of all pengajuan using an efficient database query.
     */
    public function getSummaryReport(User $user, array $filters): array
    {
        $query = Pengajuan::query()
            ->selectRaw("
                COUNT(*) as total_pengajuan,
                SUM(CASE WHEN status_pengajuan = 'Disetujui' THEN 1 ELSE 0 END) as total_disetujui,
                SUM(CASE WHEN status_pengajuan = 'Menunggu Persetujuan' THEN 1 ELSE 0 END) as total_menunggu,
                SUM(CASE WHEN status_pengajuan = 'Ditolak' THEN 1 ELSE 0 END) as total_ditolak,
                SUM(CASE WHEN status_pengajuan = 'Selesai' THEN 1 ELSE 0 END) as total_selesai,
                SUM(total_nilai) as total_nilai
            ")
            // We must join to filter by the user's branch
            ->join('tb_users', 'tb_pengajuan.unique_id', '=', 'tb_users.unique_id');

        $this->applyBaseFilters($query, $user, $filters, 'tb_pengajuan.created_at');

        $summary = $query->first()->toArray();

        // Ensure all keys exist and are numeric, even if the result is null (no records found)
        return array_map(fn($value) => $value ?? 0, [
            'total_pengajuan' => $summary['total_pengajuan'],
            'total_disetujui' => $summary['total_disetujui'],
            'total_menunggu' => $summary['total_menunggu'],
            'total_ditolak' => $summary['total_ditolak'],
            'total_selesai' => $summary['total_selesai'],
            'total_nilai' => $summary['total_nilai'],
        ]);
    }

    /**
     * Generates a report on items, their stock, and procurement history.
     */
    public function getBarangReport(User $user, array $filters): array
    {
        $query = Barang::with('jenisBarang')
            ->withSum(['gudang as stok_saat_ini' => function (Builder $query) use ($user, $filters) {
                $this->applyBranchAndRoleFilters($query, $user, $filters);
            }], 'jumlah_barang')
            ->withSum(['details as total_pengadaan' => function (Builder $query) use ($user, $filters) {
                $query->whereHas('pengajuan', function (Builder $subQuery) use ($user, $filters) {
                    $this->applyBaseFilters($subQuery, $user, $filters, 'tb_pengajuan.created_at');
                });
            }], 'jumlah');

        $barangData = $query->get();

        return $barangData->map(function ($barang) {
            $stokSaatIni = $barang->stok_saat_ini ?? 0;
            $totalPengadaan = $barang->total_pengadaan ?? 0;

            return [
                'id_barang' => $barang->id_barang,
                'nama_barang' => $barang->nama_barang,
                'harga_barang' => $barang->harga_barang,
                'jenis_barang' => $barang->jenisBarang,
                'total_pengadaan' => $totalPengadaan,
                'nilai_pengadaan' => $totalPengadaan * $barang->harga_barang,
                'stok_saat_ini' => $stokSaatIni,
                'nilai_stok' => $stokSaatIni * $barang->harga_barang,
            ];
        })->toArray();
    }

    /**
     * Generates a detailed list of all pengajuan.
     */
    public function getPengajuanReport(User $user, array $filters): array
    {
        $query = Pengajuan::with(['user', 'details.barang']);
        $this->applyBaseFilters($query, $user, $filters, 'tb_pengajuan.created_at');

        $pengajuanData = $query->get();

        return $pengajuanData->map(function ($pengajuan) {
            return [
                'id_pengajuan' => $pengajuan->id_pengajuan,
                'user' => $pengajuan->user,
                'status_pengajuan' => $pengajuan->status_pengajuan,
                'created_at' => $pengajuan->created_at,
                'updated_at' => $pengajuan->updated_at,
                'total_items' => $pengajuan->details->sum('jumlah'),
                'total_nilai' => $pengajuan->details->sum(function ($detail) {
                    return ($detail->barang->harga_barang ?? 0) * $detail->jumlah;
                }),
            ];
        })->toArray();
    }
    
    /**
     * Generates a report summarizing pengajuan data grouped by branch.
     */
    public function getCabangReport(User $user, array $filters): array
    {
        $query = Pengajuan::query()
            ->join('tb_users', 'tb_pengajuan.unique_id', '=', 'tb_users.unique_id')
            ->select(
                'tb_users.branch_name',
                DB::raw("COUNT(tb_pengajuan.id_pengajuan) as total_pengajuan"),
                DB::raw("SUM(CASE WHEN status_pengajuan = 'Disetujui' THEN 1 ELSE 0 END) as total_disetujui"),
                DB::raw("SUM(CASE WHEN status_pengajuan = 'Menunggu Persetujuan' THEN 1 ELSE 0 END) as total_menunggu"),
                DB::raw("SUM(CASE WHEN status_pengajuan = 'Ditolak' THEN 1 ELSE 0 END) as total_ditolak"),
                DB::raw("SUM(CASE WHEN status_pengajuan = 'Selesai' THEN 1 ELSE 0 END) as total_selesai"),
                DB::raw("SUM(tb_pengajuan.total_nilai) as total_nilai")
            )
            ->groupBy('tb_users.branch_name');

        $this->applyBaseFilters($query, $user, $filters, 'tb_pengajuan.created_at');

        return $query->get()->toArray();
    }

    /**
     * Generates a detailed report on item usage.
     */
    public function getPenggunaanReport(User $user, array $filters): array
    {
        $query = PenggunaanBarang::with(['user', 'barang.jenisBarang', 'approver']);
        $this->applyBaseFilters($query, $user, $filters, 'tanggal_penggunaan');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['keperluan'])) {
            $query->where('keperluan', 'like', '%' . $filters['keperluan'] . '%');
        }

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

    /**
     * Generates a detailed stock report.
     */
    public function getStokReport(User $user, array $filters): array
    {
        $query = Gudang::with(['barang.jenisBarang', 'user']);
        $this->applyBranchAndRoleFilters($query, $user, $filters);

        if (!empty($filters['stock_level'])) {
            $query->where(function ($q) use ($filters) {
                match ($filters['stock_level']) {
                    'empty' => $q->where('jumlah_barang', 0),
                    'low'   => $q->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5),
                    'normal' => $q->where('jumlah_barang', '>', 5),
                    default => null,
                };
            });
        }

        $stokData = $query->get();

        $summary = [
             'total_items' => $stokData->unique('id_barang')->count(),
             'total_stock' => $stokData->sum('jumlah_barang'),
             'total_value' => $stokData->sum(fn($s) => $s->jumlah_barang * ($s->barang->harga_barang ?? 0)),
             'empty_stock' => $stokData->where('jumlah_barang', 0)->count(),
             'low_stock' => $stokData->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5)->count(),
        ];

        return [
            'summary' => $summary,
            'details' => $stokData,
        ];
    }

    private function applyBaseFilters(Builder $query, User $user, array $filters, string $dateColumn = 'created_at'): void
    {
        $this->applyBranchAndRoleFilters($query, $user, $filters);
        $this->applyDateFilters($query, $filters, $dateColumn);
    }

    private function applyBranchAndRoleFilters(Builder $query, User $user, array $filters): void
    {
        if ($user->hasRole('manager')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $user->branch_name));
        }

        if (!empty($filters['branch']) && $user->hasRole('admin')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $filters['branch']));
        }
    }
    
    private function applyDateFilters(Builder $query, array $filters, string $dateColumn): void
    {
        if (!empty($filters['period']) && $filters['period'] !== 'custom') {
            $dates = $this->getDateRangeFromPeriod($filters['period']);
            if ($dates) {
                $query->whereBetween($dateColumn, $dates);
            }
        } elseif (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween($dateColumn, [
                Carbon::parse($filters['start_date'])->startOfDay(), 
                Carbon::parse($filters['end_date'])->endOfDay()
            ]);
        }
    }
    
    private function getDateRangeFromPeriod(string $period): ?array
    {
        return match ($period) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'week'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'year'  => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            default => null,
        };
    }
}

