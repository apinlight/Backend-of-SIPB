<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PengajuanBarangInfoResource;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\BatasBarang;
use App\Models\Pengajuan;
use App\Models\DetailPengajuan;
use App\Models\GlobalSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengajuanBarangInfoController extends Controller
{
    /**
     * Get combined barang info for pengajuan page
     * Includes: barang data, stock info, limits, and monthly status
     */
    public function getBarangInfo(Request $request)
    {
        $user = Auth::user();
        
        // Get all barang with their basic info
        $barangQuery = Barang::with(['jenisBarang'])
            ->where('is_active', true)
            ->orderBy('nama_barang');
            
        // Apply search filter if provided
        if ($request->filled('search')) {
            $barangQuery->where(function($q) use ($request) {
                $q->where('nama_barang', 'like', '%' . $request->search . '%')
                  ->orWhere('id_barang', 'like', '%' . $request->search . '%')
                  ->orWhereHas('jenisBarang', function($jq) use ($request) {
                      $jq->where('nama_jenis', 'like', '%' . $request->search . '%');
                  });
            });
        }
        
        $barangList = $barangQuery->get();
        
        // Get user's current stock
        $userStock = Gudang::where('unique_id', $user->unique_id)
            ->pluck('jumlah_barang', 'id_barang')
            ->toArray();
        
        // Get per-barang limits (BatasBarang)
        $barangLimits = BatasBarang::pluck('batas_barang', 'id_barang')->toArray();
        
        // Get global monthly limit
        $globalLimit = GlobalSetting::where('key', 'monthly_pengajuan_limit')->first();
        $monthlyLimit = $globalLimit ? (int)$globalLimit->value : 0;
        
        // Get user's current month pengajuan summary
        $currentMonth = Carbon::now()->startOfMonth();
        $nextMonth = Carbon::now()->addMonth()->startOfMonth();
        
        $monthlyPengajuanData = $this->getUserMonthlyPengajuanData($user->unique_id, $currentMonth, $nextMonth);
        
        // Get admin stock for reference (if user has permission)
        $adminStock = $this->getAdminStockData($user);
        
        // Prepare data for the resource
        $resourceData = [
            'barang' => $barangList,
            'userStock' => $userStock,
            'adminStock' => $adminStock,
            'barangLimits' => $barangLimits,
            'monthlyLimit' => $monthlyLimit,
            'monthlyUsed' => $monthlyPengajuanData['total_used'],
            'currentMonth' => $currentMonth,
            'pengajuanCount' => $monthlyPengajuanData['pengajuan_count'],
            'pendingCount' => $monthlyPengajuanData['pending_count'],
            'user' => $user,
            'canSeeAdminStock' => $this->canSeeAdminStock($user),
        ];
        
        return response()->json([
            'status' => true,
            'data' => new PengajuanBarangInfoResource($resourceData)
        ]);
    }
    
    /**
     * Get user's monthly pengajuan data
     */
    private function getUserMonthlyPengajuanData($uniqueId, $startDate, $endDate)
    {
        $pengajuanQuery = Pengajuan::where('unique_id', $uniqueId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('tipe_pengajuan', '!=', 'mandiri'); // Exclude mandiri from limits
            
        $pengajuanList = $pengajuanQuery->with('details')->get();
        
        $totalUsed = 0;
        $pengajuanCount = $pengajuanList->count();
        $pendingCount = $pengajuanList->where('status_pengajuan', 'Menunggu Persetujuan')->count();
        
        foreach ($pengajuanList as $pengajuan) {
            foreach ($pengajuan->details as $detail) {
                $totalUsed += $detail->jumlah;
            }
        }
        
        return [
            'total_used' => $totalUsed,
            'pengajuan_count' => $pengajuanCount,
            'pending_count' => $pendingCount,
        ];
    }
    
    /**
     * Get admin stock data (if user has permission)
     */
    private function getAdminStockData($user)
    {
        if (!$this->canSeeAdminStock($user)) {
            return [];
        }
        
        // Find admin user(s) - no branch filtering as admin is global
        $adminUsers = \App\Models\User::whereHas('roles', function($q) {
                $q->where('name', 'admin');
            })
            ->pluck('unique_id');
            
        if ($adminUsers->isEmpty()) {
            return [];
        }
        
        // Get combined admin stock (all admin stock regardless of branch)
        $adminStock = Gudang::whereIn('unique_id', $adminUsers)
            ->select('id_barang', DB::raw('SUM(jumlah_barang) as total_stock'))
            ->groupBy('id_barang')
            ->pluck('total_stock', 'id_barang')
            ->toArray();
            
        return $adminStock;
    }
    
    /**
     * Check if user can see admin stock
     */
    private function canSeeAdminStock($user)
    {
        // Only managers and admins can see admin stock
        return $user->hasRole('manager') || $user->hasRole('admin');
    }
    
    /**
     * Get user's pengajuan history for a specific barang
     */
    public function getBarangPengajuanHistory(Request $request, $idBarang)
    {
        $user = Auth::user();
        
        $request->validate([
            'months' => 'sometimes|integer|min:1|max:12',
        ]);
        
        $months = $request->input('months', 6); // Default 6 months
        $startDate = Carbon::now()->subMonths($months)->startOfMonth();
        
        $pengajuanHistory = DetailPengajuan::whereHas('pengajuan', function($q) use ($user, $startDate) {
                $q->where('unique_id', $user->unique_id)
                  ->where('created_at', '>=', $startDate);
            })
            ->where('id_barang', $idBarang)
            ->with(['pengajuan' => function($q) {
                $q->select('id_pengajuan', 'status_pengajuan', 'tipe_pengajuan', 'created_at');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $summary = [
            'total_requested' => $pengajuanHistory->sum('jumlah'),
            'approved_count' => $pengajuanHistory->filter(function($detail) {
                return $detail->pengajuan->status_pengajuan === 'Disetujui';
            })->count(),
            'pending_count' => $pengajuanHistory->filter(function($detail) {
                return $detail->pengajuan->status_pengajuan === 'Menunggu Persetujuan';
            })->count(),
            'rejected_count' => $pengajuanHistory->filter(function($detail) {
                return $detail->pengajuan->status_pengajuan === 'Ditolak';
            })->count(),
        ];
        
        return response()->json([
            'status' => true,
            'data' => [
                'history' => $pengajuanHistory,
                'summary' => $summary,
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => Carbon::now()->format('Y-m-d'),
                    'months' => $months,
                ],
            ]
        ]);
    }
}
