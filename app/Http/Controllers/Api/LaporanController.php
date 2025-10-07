<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use App\Exports\BarangReportExport;
use App\Exports\PengajuanReportExport;
use App\Exports\PenggunaanReportExport;
use App\Exports\StokReportExport;
use App\Exports\SummaryReportExport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected LaporanService $laporanService)
    {
    }

    private function getFilters(Request $request): array
    {
        return $request->only(['period', 'start_date', 'end_date', 'branch', 'status', 'keperluan', 'stock_level']);
    }

    public function summary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $data = $this->laporanService->getSummaryReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function barang(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Barang::class);
        // ✅ PERUBAHAN: Service sekarang mengembalikan array, kita hanya butuh 'details' untuk JSON response.
        $reportData = $this->laporanService->getBarangReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $reportData['details']]);
    }

    public function pengajuan(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $reportData = $this->laporanService->getPengajuanReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $reportData['details']]);
    }

    public function penggunaan(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\PenggunaanBarang::class);
        $reportData = $this->laporanService->getPenggunaanReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $reportData['details']]);
    }

    public function stok(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Gudang::class);
        $reportData = $this->laporanService->getStokReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $reportData['stocks']]);
    }

    // --- METODE EKSPOR ---

    public function exportSummary(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $filters = $this->getFilters($request);
        $data = $this->laporanService->getSummaryReport($request->user(), $filters);
        $fileName = 'Summary_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new SummaryReportExport($data, $filters, $request->user()), $fileName);
    }

    public function exportBarang(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Barang::class);
        $filters = $this->getFilters($request);
        // ✅ PERUBAHAN: Ambil data lengkap (detail dan summary) dari service.
        $reportData = $this->laporanService->getBarangReport($request->user(), $filters);
        $fileName = 'Barang_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        // Kirim seluruh paket data yang sudah matang ke kelas ekspor.
        return Excel::download(new BarangReportExport($reportData, $filters, $request->user()), $fileName);
    }

    public function exportPengajuan(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getPengajuanReport($request->user(), $filters);
        $fileName = 'Pengajuan_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new PengajuanReportExport($reportData, $filters, $request->user()), $fileName);
    }

    public function exportPenggunaan(Request $request)
    {
        $this->authorize('viewAny', \App\Models\PenggunaanBarang::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getPenggunaanReport($request->user(), $filters);
        $fileName = 'Penggunaan_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new PenggunaanReportExport($reportData, $filters, $request->user()), $fileName);
    }

    public function exportStok(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Gudang::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getStokReport($request->user(), $filters);
        $fileName = 'Stok_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new StokReportExport($reportData, $filters, $request->user()), $fileName);
    }
}

