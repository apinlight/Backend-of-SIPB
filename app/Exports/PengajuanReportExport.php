<?php

namespace App\Exports;

use App\Exports\Sheets\FiltersSheet;
use App\Exports\Sheets\PengajuanByStatusSheet;
use App\Exports\Sheets\PengajuanDetailSheet;
use App\Exports\Sheets\PengajuanSummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PengajuanReportExport implements WithMultipleSheets
{
    protected $reportData;
    protected $filters;
    protected $user;

    public function __construct(array $reportData, array $filters, $user)
    {
        $this->reportData = $reportData;
        $this->filters = $filters;
        $this->user = $user;
    }

    public function sheets(): array
    {
        return [
            new PengajuanSummarySheet($this->reportData['summary']),
            new PengajuanByStatusSheet($this->reportData['by_status']),
            new PengajuanDetailSheet($this->reportData['details']),
            new FiltersSheet($this->filters, $this->user),
        ];
    }
}
