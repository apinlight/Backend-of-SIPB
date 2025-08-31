<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use App\Exports\SummaryReportExport;
use App\Exports\BarangReportExport;
use App\Exports\PengajuanReportExport;
use App\Exports\PenggunaanReportExport;
use App\Exports\StokReportExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    // By using "protected" in the constructor with a type-hint, Laravel
    // automatically creates and injects the LaporanService for us.
    public function __construct(protected LaporanService $laporanService)
    {
    }

    // This helper method gathers the filters from the request.
    private function getFilters(Request $request): array
    {
        return $request->only(['period', 'start_date', 'end_date', 'branch', 'status', 'keperluan', 'stock_level']);
    }

    public function summary(Request $request)
    {
        // ✅ FIX: We now pass the user and filters to the service method.
        $data = $this->laporanService->getSummaryReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function barang(Request $request)
    {
        // ✅ FIX: We now pass the user and filters to the service method.
        $data = $this->laporanService->getBarangReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function pengajuan(Request $request)
    {
        // ✅ FIX: We now pass the user and filters to the service method.
        $data = $this->laporanService->getPengajuanReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function cabang(Request $request)
    {
        // ✅ FIX: We now pass the user and filters to the service method.
        $data = $this->laporanService->getCabangReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function penggunaan(Request $request)
    {
        // ✅ FIX: We now pass the user and filters to the service method.
        $data = $this->laporanService->getPenggunaanReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function stok(Request $request)
    {
        // ✅ FIX: We now pass the user and filters to the service method.
        $data = $this->laporanService->getStokReport($request->user(), $this->getFilters($request));
        return response()->json(['status' => true, 'data' => $data]);
    }
    
    // --- EXPORTS ---

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
        
        return Excel::download(new PengajuanReportExport($data,
         $filters, $request->user()), $fileName);
    }

    public function exportPenggunaan(Request $request)
    {
        $filters = $this->getFilters($request);
        // Export only the details part of the report data
        $data = $this->laporanService->getPenggunaanReport($request->user(), $filters)['details']; 
        $fileName = 'Penggunaan_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new PenggunaanReportExport($data, $filters, $request->user()), $fileName);
    }

    public function exportStok(Request $request)
    {
        $filters = $this->getFilters($request);
        // Export only the details part of the report data
        $data = $this->laporanService->getStokReport($request->user(), $filters)['details']; 
        $fileName = 'Stok_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new StokReportExport($data, $filters, $request->user()), $fileName);
    }
}

