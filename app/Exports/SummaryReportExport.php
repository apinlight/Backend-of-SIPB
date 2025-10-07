<?php

namespace App\Exports;

use App\Exports\Sheets\FiltersSheet;
use App\Exports\Sheets\SummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SummaryReportExport implements WithMultipleSheets
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
            new SummarySheet($this->data),
            new FiltersSheet($this->filters, $this->user),
        ];
    }
}
