<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use App\Exports\SummaryReportExport;
use App\Exports\BarangReportExport;
use App\Exports\PengajuanReportExport;
use App\Exports\PenggunaanReportExport;
use App\Exports\StokReportExport;
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
        $data = $this->laporanService->getSummaryReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function barang(Request $request): JsonResponse
    {
        $data = $this->laporanService->getBarangReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function pengajuan(Request $request): JsonResponse
    {
        $data = $this->laporanService->getPengajuanReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function cabang(Request $request): JsonResponse
    {
        $data = $this->laporanService->getCabangReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function penggunaan(Request $request): JsonResponse
    {
        $data = $this->laporanService->getPenggunaanReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function stok(Request $request): JsonResponse
    {
        $data = $this->laporanService->getStokReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }
    
    public function stockSummary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Barang::class); 
        $data = $this->laporanService->getStockSummaryReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }
    
    public function exportSummary(Request $request)
    {
        $filters = $this->getFilters($request);
        $data = $this->laporanService->getSummaryReport($request->user(), $filters);
        $fileName = 'Summary_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new SummaryReportExport($data, $filters, $request->user()), $fileName);
    }

    public function exportBarang(Request $request)
    {
        $filters = $this->getFilters($request);
        $data = $this->laporanService->getBarangReport($request->user(), $filters);
        $fileName = 'Barang_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new BarangReportExport($data, $filters, $request->user()), $fileName);
    }

    public function exportPengajuan(Request $request)
    {
        $filters = $this->getFilters($request);
        $data = $this->laporanService->getPengajuanReport($request->user(), $filters);
        $fileName = 'Pengajuan_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new PengajuanReportExport($data, $filters, $request->user()), $fileName);
    }

    public function exportPenggunaan(Request $request)
    {
        $filters = $this->getFilters($request);
        $data = $this->laporanService->getPenggunaanReport($request->user(), $filters)['details']; 
        $fileName = 'Penggunaan_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new PenggunaanReportExport($data, $filters, $request->user()), $fileName);
    }

    public function exportStok(Request $request)
    {
        $filters = $this->getFilters($request);
        $data = $this->laporanService->getStokReport($request->user(), $filters)['details']; 
        $fileName = 'Stok_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new StokReportExport($data, $filters, $request->user()), $fileName);
    }
    
    public function exportAll(Request $request)
    {
        // This would require a more complex export class that combines multiple reports.
        // For now, it can default to the summary.
        return $this->exportSummary($request);
    }
}