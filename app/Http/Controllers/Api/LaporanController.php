<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use App\Exports\SummaryReportExport;
use App\Exports\PenggunaanReportExport;
use App\Exports\StokReportExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    protected LaporanService $reportService;

    public function __construct(Request $request)
    {
        // The middleware from routes/api.php already handles authorization.
        // We create a new service instance for each request.
        $this->reportService = new LaporanService($request);
    }

    public function summary()
    {
        $data = $this->reportService->getSummaryReport();
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function penggunaan()
    {
        $data = $this->reportService->getPenggunaanReport();
        // Here you would transform the 'details' collection into a resource if needed
        return response()->json(['status' => true, 'data' => $data]);
    }

    public function stok()
    {
        $data = $this->reportService->getStokReport();
        return response()->json(['status' => true, 'data' => $data]);
    }
    
    // You would add barang(), pengajuan(), cabang() methods here, following the same pattern.

    // --- EXPORTS ---

    public function exportSummary(Request $request)
    {
        $data = $this->reportService->getSummaryReport();
        $filters = $request->only(['period', 'start_date', 'end_date', 'branch']);
        $fileName = 'Summary_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        return Excel::download(new SummaryReportExport($data, $filters, $request->user()), $fileName);
    }

    public function exportPenggunaan(Request $request)
    {
        $data = $this->reportService->getPenggunaanReport();
        $filters = $request->only(['period', 'start_date', 'end_date', 'branch', 'status', 'keperluan']);
        $fileName = 'Penggunaan_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new PenggunaanReportExport($data, $filters, $request->user()), $fileName);
    }

    public function exportStok(Request $request)
    {
        $data = $this->reportService->getStokReport();
        $filters = $request->only(['branch', 'stock_level']);
        $fileName = 'Stok_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new StokReportExport($data['details'], $filters, $request->user()), $fileName);
    }
}

