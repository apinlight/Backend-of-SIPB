<?php

namespace App\Http\Controllers\Api;

use App\Exports\BarangReportExport;
use App\Exports\AllReportsExport;
use App\Exports\PengajuanReportExport;
use App\Exports\PenggunaanReportExport;
use App\Exports\StokReportExport;
use App\Exports\SummaryReportExport;
use App\Exports\Word\BarangReportWord;
use App\Exports\Word\AllReportsWord;
use App\Exports\Word\PengajuanReportWord;
use App\Exports\Word\PenggunaanReportWord;
use App\Exports\Word\StokReportWord;
use App\Exports\Word\SummaryReportWord;
use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;

class LaporanController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected LaporanService $laporanService) {}

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

    public function cabang(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $data = $this->laporanService->getCabangReport($request->user(), $this->getFilters($request));

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function stockSummary(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Gudang::class);
        $data = $this->laporanService->getStockSummaryReport($request->user(), $this->getFilters($request));

        return response()->json(['status' => true, 'data' => $data]);
    }

    // --- METODE EKSPOR ---

    public function exportSummary(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $filters = $this->getFilters($request);
        $data = $this->laporanService->getSummaryReport($request->user(), $filters);
        $fileName = 'Summary_Report_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new SummaryReportExport($data, $filters, $request->user()), $fileName);
    }

    public function exportBarang(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Barang::class);
        $filters = $this->getFilters($request);
        // ✅ PERUBAHAN: Ambil data lengkap (detail dan summary) dari service.
        $reportData = $this->laporanService->getBarangReport($request->user(), $filters);
        $fileName = 'Barang_Report_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        // Kirim seluruh paket data yang sudah matang ke kelas ekspor.
        return Excel::download(new BarangReportExport($reportData, $filters, $request->user()), $fileName);
    }

    public function exportPengajuan(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getPengajuanReport($request->user(), $filters);
        $fileName = 'Pengajuan_Report_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new PengajuanReportExport($reportData, $filters, $request->user()), $fileName);
    }

    public function exportPenggunaan(Request $request)
    {
        $this->authorize('viewAny', \App\Models\PenggunaanBarang::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getPenggunaanReport($request->user(), $filters);
        $fileName = 'Penggunaan_Report_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new PenggunaanReportExport($reportData, $filters, $request->user()), $fileName);
    }

    public function exportStok(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Gudang::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getStokReport($request->user(), $filters);
        $fileName = 'Stok_Report_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new StokReportExport($reportData, $filters, $request->user()), $fileName);
    }

    public function exportAll(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $filters = $this->getFilters($request);

        // Collect all report data to compose a single workbook
        $payload = [
            'summary' => $this->laporanService->getSummaryReport($request->user(), $filters),
            'barang' => $this->laporanService->getBarangReport($request->user(), $filters),
            'pengajuan' => $this->laporanService->getPengajuanReport($request->user(), $filters),
            'penggunaan' => $this->laporanService->getPenggunaanReport($request->user(), $filters),
            'stok' => $this->laporanService->getStokReport($request->user(), $filters),
        ];

        $fileName = 'All_Reports_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new AllReportsExport($payload, $filters, $request->user()), $fileName);
    }

    // --- DOCX (Word) EXPORTS ---

    public function exportSummaryDocx(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $filters = $this->getFilters($request);
        $data = $this->laporanService->getSummaryReport($request->user(), $filters);

        $exporter = new SummaryReportWord($data, $filters, $request->user());
        $filePath = $exporter->generate();
        $fileName = $exporter->getFileName();

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function exportBarangDocx(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Barang::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getBarangReport($request->user(), $filters);

        $exporter = new BarangReportWord($reportData, $filters, $request->user());
        $filePath = $exporter->generate();
        $fileName = $exporter->getFileName();

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function exportPengajuanDocx(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getPengajuanReport($request->user(), $filters);

        $exporter = new PengajuanReportWord($reportData, $filters, $request->user());
        $filePath = $exporter->generate();
        $fileName = $exporter->getFileName();

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function exportPenggunaanDocx(Request $request)
    {
        $this->authorize('viewAny', \App\Models\PenggunaanBarang::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getPenggunaanReport($request->user(), $filters);

        $exporter = new PenggunaanReportWord($reportData, $filters, $request->user());
        $filePath = $exporter->generate();
        $fileName = $exporter->getFileName();

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function exportStokDocx(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Gudang::class);
        $filters = $this->getFilters($request);
        $reportData = $this->laporanService->getStokReport($request->user(), $filters);

        $exporter = new StokReportWord($reportData, $filters, $request->user());
        $filePath = $exporter->generate();
        $fileName = $exporter->getFileName();

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function exportAllDocx(Request $request)
    {
        $this->authorize('viewAny', \App\Models\Pengajuan::class);
        $filters = $this->getFilters($request);

        $payload = [
            'summary' => $this->laporanService->getSummaryReport($request->user(), $filters),
            'barang' => $this->laporanService->getBarangReport($request->user(), $filters),
            'pengajuan' => $this->laporanService->getPengajuanReport($request->user(), $filters),
            'penggunaan' => $this->laporanService->getPenggunaanReport($request->user(), $filters),
            'stok' => $this->laporanService->getStokReport($request->user(), $filters),
        ];

        $exporter = new AllReportsWord($payload, $filters, $request->user());
        $filePath = $exporter->generate();
        $fileName = $exporter->getFileName();

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }
}
