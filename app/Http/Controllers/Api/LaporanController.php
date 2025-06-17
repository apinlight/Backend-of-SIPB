<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Models\Gudang;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function summary(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json(['status' => false, 'message' => 'Access denied'], 403);
        }

        $pengajuanQuery = Pengajuan::query();
        
        if ($user->hasRole('manager')) {
            $pengajuanQuery->whereHas('user', fn($q) => $q->where('branch_name', $user->branch_name));
        }

        $this->applyDateFilters($pengajuanQuery, $request);

        $summary = [
            'total_pengajuan' => $pengajuanQuery->count(),
            'total_disetujui' => (clone $pengajuanQuery)->where('status_pengajuan', 'Disetujui')->count(),
            'total_menunggu' => (clone $pengajuanQuery)->where('status_pengajuan', 'Menunggu Persetujuan')->count(),
            'total_ditolak' => (clone $pengajuanQuery)->where('status_pengajuan', 'Ditolak')->count(),
            'total_nilai' => $this->calculateTotalValue($pengajuanQuery),
        ];

        return response()->json(['status' => true, 'data' => $summary]);
    }

    public function barang(Request $request)
    {
        $user = Auth::user();
        
        $barangData = Barang::with(['jenisBarang'])
            ->leftJoin('detail_pengajuan', 'barang.id_barang', '=', 'detail_pengajuan.id_barang')
            ->leftJoin('pengajuan', 'detail_pengajuan.id_pengajuan', '=', 'pengajuan.id_pengajuan')
            ->leftJoin('gudang', 'barang.id_barang', '=', 'gudang.id_barang')
            ->select([
                'barang.*',
                DB::raw('COALESCE(SUM(detail_pengajuan.jumlah), 0) as total_pengadaan'),
                DB::raw('COALESCE(SUM(gudang.jumlah_barang), 0) as stok_saat_ini'),
                DB::raw('COALESCE(SUM(detail_pengajuan.jumlah * barang.harga_barang), 0) as nilai_pengadaan')
            ])
            ->groupBy('barang.id_barang')
            ->get();

        return response()->json(['status' => true, 'data' => $barangData]);
    }

    public function pengajuan(Request $request)
    {
        $user = Auth::user();
        $query = Pengajuan::with(['user', 'details.barang'])
            ->select([
                'pengajuan.*',
                DB::raw('(SELECT COUNT(*) FROM detail_pengajuan WHERE detail_pengajuan.id_pengajuan = pengajuan.id_pengajuan) as total_items')
            ]);

        if ($user->hasRole('manager')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $user->branch_name));
        }

        $this->applyDateFilters($query, $request);

        $pengajuan = $query->get()->map(function($item) {
            $item->total_nilai = $item->details->sum(function($detail) {
                return ($detail->barang->harga_barang ?? 0) * $detail->jumlah;
            });
            return $item;
        });

        return response()->json(['status' => true, 'data' => $pengajuan]);
    }

    public function cabang(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json(['status' => false, 'message' => 'Only admin can access branch reports'], 403);
        }

        $cabangData = DB::table('users')
            ->leftJoin('pengajuan', 'users.unique_id', '=', 'pengajuan.unique_id')
            ->select([
                'users.branch_name',
                DB::raw('COUNT(pengajuan.id_pengajuan) as total_pengajuan'),
                DB::raw('SUM(CASE WHEN pengajuan.status_pengajuan = "Disetujui" THEN 1 ELSE 0 END) as total_disetujui'),
                DB::raw('SUM(CASE WHEN pengajuan.status_pengajuan = "Menunggu Persetujuan" THEN 1 ELSE 0 END) as total_menunggu'),
                DB::raw('SUM(CASE WHEN pengajuan.status_pengajuan = "Ditolak" THEN 1 ELSE 0 END) as total_ditolak'),
            ])
            ->groupBy('users.branch_name')
            ->get();

        return response()->json(['status' => true, 'data' => $cabangData]);
    }

    private function applyDateFilters($query, Request $request): void
    {
        if ($request->filled('period')) {
            match($request->period) {
                'today' => $query->whereDate('created_at', today()),
                'week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'month' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
                'year' => $query->whereYear('created_at', now()->year),
                default => null
            };
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
    }

    private function calculateTotalValue($query): float
    {
        return $query->with(['details.barang'])
            ->get()
            ->sum(function($pengajuan) {
                return $pengajuan->details->sum(function($detail) {
                    return ($detail->barang->harga_barang ?? 0) * $detail->jumlah;
                });
            });
    }
}
