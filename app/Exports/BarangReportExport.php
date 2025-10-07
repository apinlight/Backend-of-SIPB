<?php
namespace App\Exports;

use App\Exports\Sheets\BarangDetailSheet;
use App\Exports\Sheets\BarangSummarySheet;
use App\Exports\Sheets\FiltersSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BarangReportExport implements WithMultipleSheets
{
    protected $data; protected $filters; protected $user;
    public function __construct(array $data, array $filters, $user) {
        $this->data = $data; $this->filters = $filters; $this->user = $user;
    }
    public function sheets(): array {
        return [
            new BarangSummarySheet($this->data['summary']),
            new BarangDetailSheet($this->data['details']),
            new FiltersSheet($this->filters, $this->user),
        ];
    }
}