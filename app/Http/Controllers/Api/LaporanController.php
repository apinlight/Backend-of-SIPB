<?php
// app/Http/Controllers/Api/LaporanController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use App\Models\DetailPengajuan;
use App\Models\Gudang;
use App\Models\Barang;
use App\Models\User;
use App\Models\PenggunaanBarang;
use App\Exports\SummaryReportExport;
use App\Exports\BarangReportExport;
use App\Exports\PengajuanReportExport;
use App\Exports\PenggunaanReportExport;
use App\Exports\StokReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class LaporanController extends Controller
{
    public function summary(Request $request)
    {
        $user = Auth::user();
        
        // Role-based access control
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $query = Pengajuan::with(['user', 'details.barang']);

        // Apply role-based filtering
        if ($user->hasRole('manager')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $user->branch_name));
        }

        // Apply date filters
        $this->applyDateFilters($query, $request);

        // Apply branch filter (for admin)
        if ($request->filled('branch') && $user->hasRole('admin')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $request->branch));
        }

        $pengajuanData = $query->get();

        // ✅ FIX: Use collect() and proper sum calculation
        $totalNilai = $pengajuanData->reduce(function($carry, $pengajuan) {
            $pengajuanTotal = $pengajuan->details->reduce(function($detailCarry, $detail) {
                return $detailCarry + (($detail->barang->harga_barang ?? 0) * $detail->jumlah);
            }, 0);
            return $carry + $pengajuanTotal;
        }, 0);

        $summary = [
            'total_pengajuan' => $pengajuanData->count(),
            'total_disetujui' => $pengajuanData->where('status_pengajuan', 'Disetujui')->count(),
            'total_menunggu' => $pengajuanData->where('status_pengajuan', 'Menunggu Persetujuan')->count(),
            'total_ditolak' => $pengajuanData->where('status_pengajuan', 'Ditolak')->count(),
            'total_selesai' => $pengajuanData->where('status_pengajuan', 'Selesai')->count(),
            'total_nilai' => $totalNilai
        ];

        return response()->json([
            'status' => true,
            'data' => $summary
        ]);
    }

    public function barang(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $query = Barang::with(['jenisBarang']);

        // Get procurement data
        $procurementQuery = DetailPengajuan::with(['pengajuan.user', 'barang'])
            ->whereHas('pengajuan', function($q) use ($user, $request) {
                if ($user->hasRole('manager')) {
                    $q->whereHas('user', fn($subQ) => $subQ->where('branch_name', $user->branch_name));
                }
                
                if ($request->filled('branch') && $user->hasRole('admin')) {
                    $q->whereHas('user', fn($subQ) => $subQ->where('branch_name', $request->branch));
                }
                
                $this->applyDateFilters($q, $request);
            });

        $procurementData = $procurementQuery->get();

        // Get current stock data
        $stockQuery = Gudang::with(['barang', 'user']);
        
        if ($user->hasRole('manager')) {
            $stockQuery->whereHas('user', fn($q) => $q->where('branch_name', $user->branch_name));
        }
        
        if ($request->filled('branch') && $user->hasRole('admin')) {
            $stockQuery->whereHas('user', fn($q) => $q->where('branch_name', $request->branch));
        }

        $stockData = $stockQuery->get();

        // Combine data
        $barangData = $query->get()->map(function($barang) use ($procurementData, $stockData) {
            $procurement = $procurementData->where('id_barang', $barang->id_barang);
            $stock = $stockData->where('id_barang', $barang->id_barang);
            
            // ✅ FIX: Use reduce instead of sum with closure
            $totalPengadaan = $procurement->reduce(function($carry, $item) {
                return $carry + $item->jumlah;
            }, 0);
            
            $nilaiPengadaan = $procurement->reduce(function($carry, $item) use ($barang) {
                return $carry + ($item->jumlah * $barang->harga_barang);
            }, 0);
            
            $stokSaatIni = $stock->reduce(function($carry, $item) {
                return $carry + $item->jumlah_barang;
            }, 0);
            
            return [
                'id_barang' => $barang->id_barang,
                'nama_barang' => $barang->nama_barang,
                'harga_barang' => $barang->harga_barang,
                'jenis_barang' => $barang->jenisBarang,
                'total_pengadaan' => $totalPengadaan,
                'nilai_pengadaan' => $nilaiPengadaan,
                'stok_saat_ini' => $stokSaatIni,
                'nilai_stok' => $stokSaatIni * $barang->harga_barang,
                'batas_minimum' => 5, // Default minimum stock
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $barangData
        ]);
    }

    public function pengajuan(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $query = Pengajuan::with(['user', 'details.barang']);

        // Apply role-based filtering
        if ($user->hasRole('manager')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $user->branch_name));
        }

        // Apply date filters
        $this->applyDateFilters($query, $request);

        // Apply branch filter (for admin)
        if ($request->filled('branch') && $user->hasRole('admin')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $request->branch));
        }

        $pengajuanData = $query->get()->map(function($pengajuan) {
            // ✅ FIX: Use reduce instead of sum with closure
            $totalItems = $pengajuan->details->reduce(function($carry, $detail) {
                return $carry + $detail->jumlah;
            }, 0);
            
            $totalNilai = $pengajuan->details->reduce(function($carry, $detail) {
                return $carry + (($detail->barang->harga_barang ?? 0) * $detail->jumlah);
            }, 0);
            
            return [
                'id_pengajuan' => $pengajuan->id_pengajuan,
                'user' => $pengajuan->user,
                'status_pengajuan' => $pengajuan->status_pengajuan,
                'created_at' => $pengajuan->created_at,
                'updated_at' => $pengajuan->updated_at,
                'total_items' => $totalItems,
                'total_nilai' => $totalNilai
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $pengajuanData
        ]);
    }

    public function cabang(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $query = Pengajuan::with(['user', 'details.barang']);

        // Apply role-based filtering
        if ($user->hasRole('manager')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $user->branch_name));
        }

        // Apply date filters
        $this->applyDateFilters($query, $request);

        $pengajuanData = $query->get();

        // Group by branch
        $cabangData = $pengajuanData->groupBy('user.branch_name')->map(function($pengajuanPerCabang, $branchName) {
            // ✅ FIX: Use reduce instead of sum with closure
            $totalNilai = $pengajuanPerCabang->reduce(function($carry, $pengajuan) {
                $pengajuanTotal = $pengajuan->details->reduce(function($detailCarry, $detail) {
                    return $detailCarry + (($detail->barang->harga_barang ?? 0) * $detail->jumlah);
                }, 0);
                return $carry + $pengajuanTotal;
            }, 0);
            
            return [
                'branch_name' => $branchName,
                'total_pengajuan' => $pengajuanPerCabang->count(),
                'total_disetujui' => $pengajuanPerCabang->where('status_pengajuan', 'Disetujui')->count(),
                'total_menunggu' => $pengajuanPerCabang->where('status_pengajuan', 'Menunggu Persetujuan')->count(),
                'total_ditolak' => $pengajuanPerCabang->where('status_pengajuan', 'Ditolak')->count(),
                'total_selesai' => $pengajuanPerCabang->where('status_pengajuan', 'Selesai')->count(),
                'total_nilai' => $totalNilai
            ];
        })->values();

        return response()->json([
            'status' => true,
            'data' => $cabangData
        ]);
    }

    // ✅ FIXED: Laporan Penggunaan Barang
    public function penggunaan(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $query = PenggunaanBarang::with(['user', 'barang.jenisBarang', 'approver']);

        // Apply role-based filtering
        if ($user->hasRole('manager')) {
            $query->whereHas('user', fn($q) => 
                $q->where('branch_name', $user->branch_name)
            );
        }

        // Apply date filters
        $this->applyDateFilters($query, $request, 'tanggal_penggunaan');

        // Apply branch filter (for admin)
        if ($request->filled('branch') && $user->hasRole('admin')) {
            $query->whereHas('user', fn($q) => 
                $q->where('branch_name', $request->branch)
            );
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply keperluan filter
        if ($request->filled('keperluan')) {
            $query->where('keperluan', 'like', '%' . $request->keperluan . '%');
        }

        $penggunaanData = $query->orderBy('tanggal_penggunaan', 'desc')->get();

        // ✅ FIX: Use reduce instead of sum with closure
        $totalNilai = $penggunaanData->reduce(function($carry, $penggunaan) {
            return $carry + (($penggunaan->barang->harga_barang ?? 0) * $penggunaan->jumlah_digunakan);
        }, 0);

        $totalBarangDigunakan = $penggunaanData->reduce(function($carry, $penggunaan) {
            return $carry + $penggunaan->jumlah_digunakan;
        }, 0);

        // Create summary
        $summary = [
            'total_penggunaan' => $penggunaanData->count(),
            'total_approved' => $penggunaanData->where('status', 'approved')->count(),
            'total_pending' => $penggunaanData->where('status', 'pending')->count(),
            'total_rejected' => $penggunaanData->where('status', 'rejected')->count(),
            'total_nilai' => $totalNilai,
            'total_barang_digunakan' => $totalBarangDigunakan
        ];

                // Group by barang
        $byBarang = $penggunaanData->groupBy('id_barang')->map(function($items) {
            $first = $items->first();
            
            $totalDigunakan = $items->reduce(function($carry, $item) {
                return $carry + $item->jumlah_digunakan;
            }, 0);
            
            $totalNilai = $items->reduce(function($carry, $item) {
                return $carry + (($item->barang->harga_barang ?? 0) * $item->jumlah_digunakan);
            }, 0);
            
            $penggunaanApproved = $items->where('status', 'approved')->reduce(function($carry, $item) {
                return $carry + $item->jumlah_digunakan;
            }, 0);
            
            $penggunaanPending = $items->where('status', 'pending')->reduce(function($carry, $item) {
                return $carry + $item->jumlah_digunakan;
            }, 0);
            
            return [
                'id_barang' => $first->id_barang,
                'nama_barang' => $first->barang->nama_barang,
                'jenis_barang' => $first->barang->jenisBarang->nama_jenis_barang ?? null,
                'total_digunakan' => $totalDigunakan,
                'total_nilai' => $totalNilai,
                'frekuensi_penggunaan' => $items->count(),
                'penggunaan_approved' => $penggunaanApproved,
                'penggunaan_pending' => $penggunaanPending,
            ];
        })->values();

        // Group by cabang
        $byCabang = $penggunaanData->groupBy('user.branch_name')->map(function($items, $branchName) {
            $totalBarangDigunakan = $items->reduce(function($carry, $item) {
                return $carry + $item->jumlah_digunakan;
            }, 0);
            
            $totalNilai = $items->reduce(function($carry, $item) {
                return $carry + (($item->barang->harga_barang ?? 0) * $item->jumlah_digunakan);
            }, 0);
            
            return [
                'branch_name' => $branchName,
                'total_penggunaan' => $items->count(),
                'total_approved' => $items->where('status', 'approved')->count(),
                'total_pending' => $items->where('status', 'pending')->count(),
                'total_rejected' => $items->where('status', 'rejected')->count(),
                'total_barang_digunakan' => $totalBarangDigunakan,
                'total_nilai' => $totalNilai
            ];
        })->values();

        // Group by keperluan
        $byKeperluan = $penggunaanData->groupBy('keperluan')->map(function($items, $keperluan) {
            $totalBarangDigunakan = $items->reduce(function($carry, $item) {
                return $carry + $item->jumlah_digunakan;
            }, 0);
            
            $totalNilai = $items->reduce(function($carry, $item) {
                return $carry + (($item->barang->harga_barang ?? 0) * $item->jumlah_digunakan);
            }, 0);
            
            return [
                'keperluan' => $keperluan,
                'total_penggunaan' => $items->count(),
                'total_barang_digunakan' => $totalBarangDigunakan,
                'total_nilai' => $totalNilai,
                'unique_barang' => $items->unique('id_barang')->count()
            ];
        })->values();

        return response()->json([
            'status' => true,
            'data' => [
                'summary' => $summary,
                'by_barang' => $byBarang,
                'by_cabang' => $byCabang,
                'by_keperluan' => $byKeperluan,
                'detail' => $penggunaanData->map(function($penggunaan) {
                    return [
                        'id_penggunaan' => $penggunaan->id_penggunaan,
                        'tanggal_penggunaan' => $penggunaan->tanggal_penggunaan->format('Y-m-d'),
                        'user' => [
                            'username' => $penggunaan->user->username,
                            'branch_name' => $penggunaan->user->branch_name
                        ],
                        'barang' => [
                            'nama_barang' => $penggunaan->barang->nama_barang,
                            'jenis_barang' => $penggunaan->barang->jenisBarang->nama_jenis_barang ?? null,
                            'harga_barang' => $penggunaan->barang->harga_barang
                        ],
                        'jumlah_digunakan' => $penggunaan->jumlah_digunakan,
                        'keperluan' => $penggunaan->keperluan,
                        'status' => $penggunaan->status,
                        'total_nilai' => ($penggunaan->barang->harga_barang ?? 0) * $penggunaan->jumlah_digunakan,
                        'approver' => $penggunaan->approver ? [
                            'username' => $penggunaan->approver->username,
                            'branch_name' => $penggunaan->approver->branch_name
                        ] : null,
                        'approved_at' => $penggunaan->approved_at?->format('Y-m-d H:i:s')
                    ];
                })
            ]
        ]);
    }

    // ✅ FIXED: Laporan Stok
    public function stok(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        $query = Gudang::with(['barang.jenisBarang', 'user']);

        // Apply role-based filtering
        if ($user->hasRole('manager')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $user->branch_name));
        }

        if ($request->filled('branch') && $user->hasRole('admin')) {
            $query->whereHas('user', fn($q) => $q->where('branch_name', $request->branch));
        }

        // Apply stock level filters
        if ($request->filled('stock_level')) {
            switch ($request->stock_level) {
                case 'empty':
                    $query->where('jumlah_barang', 0);
                    break;
                case 'low':
                    $query->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5);
                    break;
                case 'normal':
                    $query->where('jumlah_barang', '>', 5);
                    break;
            }
        }

        $stocks = $query->get();

        // ✅ FIX: Use reduce instead of sum with closure
        $totalStock = $stocks->reduce(function($carry, $stock) {
            return $carry + $stock->jumlah_barang;
        }, 0);
        
        $totalValue = $stocks->reduce(function($carry, $stock) {
            return $carry + ($stock->jumlah_barang * ($stock->barang->harga_barang ?? 0));
        }, 0);

        // Create summary
        $summary = [
            'total_items' => $stocks->count(),
            'total_stock' => $totalStock,
            'total_value' => $totalValue,
            'empty_stock' => $stocks->where('jumlah_barang', 0)->count(),
            'low_stock' => $stocks->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5)->count(),
            'normal_stock' => $stocks->where('jumlah_barang', '>', 5)->count(),
        ];

        // Group by branch
        $byBranch = $stocks->groupBy('user.branch_name')->map(function($items, $branchName) {
            $totalStock = $items->reduce(function($carry, $item) {
                return $carry + $item->jumlah_barang;
            }, 0);
            
            $totalValue = $items->reduce(function($carry, $item) {
                return $carry + ($item->jumlah_barang * ($item->barang->harga_barang ?? 0));
            }, 0);
            
            return [
                'branch_name' => $branchName,
                'total_items' => $items->count(),
                'total_stock' => $totalStock,
                'total_value' => $totalValue,
                'empty_stock' => $items->where('jumlah_barang', 0)->count(),
                'low_stock' => $items->where('jumlah_barang', '>', 0)->where('jumlah_barang', '<=', 5)->count(),
            ];
        })->values();

        return response()->json([
            'status' => true,
            'data' => [
                'summary' => $summary,
                'by_branch' => $byBranch,
                'stocks' => $stocks->map(function($stock) {
                    return [
                        'unique_id' => $stock->unique_id,
                        'user' => [
                            'username' => $stock->user->username,
                            'branch_name' => $stock->user->branch_name
                        ],
                        'barang' => [
                            'id_barang' => $stock->barang->id_barang,
                            'nama_barang' => $stock->barang->nama_barang,
                            'jenis_barang' => $stock->barang->jenisBarang->nama_jenis_barang ?? null,
                            'harga_barang' => $stock->barang->harga_barang
                        ],
                        'jumlah_barang' => $stock->jumlah_barang,
                        'total_nilai' => $stock->jumlah_barang * ($stock->barang->harga_barang ?? 0),
                        'stock_status' => $this->getStockStatus($stock->jumlah_barang),
                        'created_at' => $stock->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $stock->updated_at->format('Y-m-d H:i:s')
                    ];
                })
            ]
        ]);
    }

    // ✅ IMPLEMENTED: Excel Export Methods
    public function exportSummary(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        try {
            // Get summary data using existing method logic
            $query = Pengajuan::with(['user', 'details.barang']);

            if ($user->hasRole('manager')) {
                $query->whereHas('user', fn($q) => $q->where('branch_name', $user->branch_name));
            }

            $this->applyDateFilters($query, $request);

            if ($request->filled('branch') && $user->hasRole('admin')) {
                $query->whereHas('user', fn($q) => $q->where('branch_name', $request->branch));
            }

            $pengajuanData = $query->get();

            $totalNilai = $pengajuanData->reduce(function($carry, $pengajuan) {
                $pengajuanTotal = $pengajuan->details->reduce(function($detailCarry, $detail) {
                    return $detailCarry + (($detail->barang->harga_barang ?? 0) * $detail->jumlah);
                }, 0);
                return $carry + $pengajuanTotal;
            }, 0);

            $summaryData = [
                'total_pengajuan' => $pengajuanData->count(),
                'total_disetujui' => $pengajuanData->where('status_pengajuan', 'Disetujui')->count(),
                'total_menunggu' => $pengajuanData->where('status_pengajuan', 'Menunggu Persetujuan')->count(),
                'total_ditolak' => $pengajuanData->where('status_pengajuan', 'Ditolak')->count(),
                'total_selesai' => $pengajuanData->where('status_pengajuan', 'Selesai')->count(),
                'total_nilai' => $totalNilai
            ];

            $filters = $request->only(['period', 'start_date', 'end_date', 'branch']);
            
            $fileName = 'Summary_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(
                new SummaryReportExport($summaryData, $filters, $user),
                $fileName
            );

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to export summary: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportBarang(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        try {
            // Get barang data using existing method logic
            $response = $this->barang($request);
            $responseData = json_decode($response->getContent(), true);
            
            if (!$responseData['status']) {
                return $response;
            }

            $barangData = $responseData['data'];
            $filters = $request->only(['period', 'start_date', 'end_date', 'branch']);
            
            $fileName = 'Barang_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(
                new BarangReportExport($barangData, $filters, $user),
                $fileName
            );

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to export barang report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportPengajuan(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        try {
            // Get pengajuan data using existing method logic
            $response = $this->pengajuan($request);
            $responseData = json_decode($response->getContent(), true);
            
            if (!$responseData['status']) {
                return $response;
            }

            $pengajuanData = $responseData['data'];
            $filters = $request->only(['period', 'start_date', 'end_date', 'branch']);
            
            $fileName = 'Pengajuan_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(
                new PengajuanReportExport($pengajuanData, $filters, $user),
                $fileName
            );

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to export pengajuan report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportPenggunaan(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        try {
            // Get penggunaan data using existing method logic
            $response = $this->penggunaan($request);
            $responseData = json_decode($response->getContent(), true);
            
            if (!$responseData['status']) {
                return $response;
            }

            $penggunaanData = $responseData['data'];
            $filters = $request->only(['period', 'start_date', 'end_date', 'branch', 'status', 'keperluan']);
            
            $fileName = 'Penggunaan_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(
                new PenggunaanReportExport($penggunaanData, $filters, $user),
                $fileName
            );

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to export penggunaan report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportStok(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        try {
            // Get stok data using existing method logic
            $response = $this->stok($request);
            $responseData = json_decode($response->getContent(), true);
            
            if (!$responseData['status']) {
                return $response;
            }

            $stokData = $responseData['data'];
            $filters = $request->only(['branch', 'stock_level']);
            
            $fileName = 'Stok_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(
                new StokReportExport($stokData, $filters, $user),
                $fileName
            );

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to export stok report: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportAll(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasRole(['admin', 'manager'])) {
            return response()->json([
                'status' => false,
                'message' => 'Access denied - insufficient permissions'
            ], 403);
        }

        try {
            // This would generate a comprehensive report with all data
            // For now, redirect to summary export
            return $this->exportSummary($request);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to export all reports: ' . $e->getMessage()
            ], 500);
        }
    }

    // ✅ LEGACY: Keep for backward compatibility
    public function export(Request $request)
    {
        // Redirect to appropriate export based on type parameter
        $type = $request->input('type', 'summary');
        
        switch ($type) {
            case 'summary':
                return $this->exportSummary($request);
            case 'barang':
                return $this->exportBarang($request);
            case 'pengajuan':
                return $this->exportPengajuan($request);
            case 'penggunaan':
                return $this->exportPenggunaan($request);
            case 'stok':
                return $this->exportStok($request);
            case 'all':
                return $this->exportAll($request);
            default:
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid export type. Available types: summary, barang, pengajuan, penggunaan, stok, all'
                ], 400);
        }
    }

    // ✅ HELPER METHODS
    private function applyDateFilters($query, Request $request, string $dateColumn = 'created_at')
    {
        if ($request->filled('period')) {
            switch ($request->period) {
                case 'today':
                    $query->whereDate($dateColumn, today());
                    break;
                case 'week':
                    $query->whereBetween($dateColumn, [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth($dateColumn, now()->month)
                          ->whereYear($dateColumn, now()->year);
                    break;
                case 'year':
                    $query->whereYear($dateColumn, now()->year);
                    break;
                case 'custom':
                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $query->whereBetween($dateColumn, [
                            $request->start_date, 
                            $request->end_date
                        ]);
                    }
                    break;
            }
        }
    }

    private function getStockStatus(int $stock): string
    {
        if ($stock === 0) return 'empty';
        if ($stock <= 5) return 'low';
        return 'normal';
    }
}
