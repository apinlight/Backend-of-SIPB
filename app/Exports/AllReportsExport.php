<?php

namespace App\Exports;

use App\Exports\Sheets\BarangDetailSheet;
use App\Exports\Sheets\BarangSummarySheet;
use App\Exports\Sheets\FiltersSheet;
use App\Exports\Sheets\PengajuanByStatusSheet;
use App\Exports\Sheets\PengajuanDetailSheet;
use App\Exports\Sheets\PengajuanSummarySheet;
use App\Exports\Sheets\PenggunaanByBarangSheet;
use App\Exports\Sheets\PenggunaanByCabangSheet;
use App\Exports\Sheets\PenggunaanDetailSheet;
use App\Exports\Sheets\PenggunaanSummarySheet;
use App\Exports\Sheets\StokByBranchSheet;
use App\Exports\Sheets\StokDetailSheet;
use App\Exports\Sheets\StokLowAlertSheet;
use App\Exports\Sheets\StokSummarySheet;
use App\Exports\Sheets\SummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * AllReportsExport
 *
 * Produces a single workbook containing multiple logical report sheets:
 * - Summary
 * - Barang (summary + details)
 * - Pengajuan (summary + by status + details)
 * - Penggunaan (summary + by barang + by cabang + details)
 * - Stok (summary + low alert + by branch + details)
 */
class AllReportsExport implements WithMultipleSheets
{
    public function __construct(
        protected array $payload,
        protected array $filters,
        protected $user,
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];

        // Summary
        if (!empty($this->payload['summary'])) {
            $sheets[] = new SummarySheet($this->payload['summary']);
        }

        // Barang
        if (!empty($this->payload['barang'])) {
            $barang = $this->payload['barang'];
            $sheets[] = new BarangSummarySheet($barang['summary'] ?? []);
            $sheets[] = new BarangDetailSheet($barang['details'] ?? []);
        }

        // Pengajuan
        if (!empty($this->payload['pengajuan'])) {
            $pengajuan = $this->payload['pengajuan'];
            $sheets[] = new PengajuanSummarySheet($pengajuan['summary'] ?? []);
            $sheets[] = new PengajuanByStatusSheet($pengajuan['by_status'] ?? []);
            $sheets[] = new PengajuanDetailSheet($pengajuan['details'] ?? []);
        }

        // Penggunaan
        if (!empty($this->payload['penggunaan'])) {
            $penggunaan = $this->payload['penggunaan'];
            $sheets[] = new PenggunaanSummarySheet($penggunaan['summary'] ?? []);
            $sheets[] = new PenggunaanByBarangSheet($penggunaan['by_barang'] ?? []);
            $sheets[] = new PenggunaanByCabangSheet($penggunaan['by_cabang'] ?? []);
            $sheets[] = new PenggunaanDetailSheet($penggunaan['details'] ?? []);
        }

        // Stok
        if (!empty($this->payload['stok'])) {
            $stok = $this->payload['stok'];
            $sheets[] = new StokSummarySheet($stok['summary'] ?? []);
            $sheets[] = new StokLowAlertSheet($stok['stocks'] ?? []);
            $sheets[] = new StokByBranchSheet($stok['by_branch'] ?? []);
            $sheets[] = new StokDetailSheet($stok['stocks'] ?? []);
        }

        // Filters (always append for traceability)
        $sheets[] = new FiltersSheet($this->filters, $this->user);

        return $sheets;
    }
}
