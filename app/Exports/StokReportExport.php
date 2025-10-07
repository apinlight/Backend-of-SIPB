<?php

namespace App\Exports;

use App\Exports\Sheets\FiltersSheet;
use App\Exports\Sheets\StokByBranchSheet;
use App\Exports\Sheets\StokDetailSheet;
use App\Exports\Sheets\StokLowAlertSheet;
use App\Exports\Sheets\StokSummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StokReportExport implements WithMultipleSheets
{
    protected $data;
    protected $filters;
    protected $user;

    public function __construct(array $data, array $filters, $user)
    {
        $this->data = $data;
        $this->filters = $filters;
        $this->user = $user;
    }

    public function sheets(): array
    {
        return [
            new StokSummarySheet($this->data['summary'] ?? []),
            new StokLowAlertSheet($this->data['stocks'] ?? []),
            new StokByBranchSheet($this->data['by_branch'] ?? []),
            new StokDetailSheet($this->data['stocks'] ?? []),
            new FiltersSheet($this->filters, $this->user),
        ];
    }
}

