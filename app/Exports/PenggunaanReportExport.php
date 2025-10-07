<?php

namespace App\Exports;

use App\Exports\Sheets\FiltersSheet;
use App\Exports\Sheets\PenggunaanByBarangSheet;
use App\Exports\Sheets\PenggunaanByCabangSheet;
use App\Exports\Sheets\PenggunaanDetailSheet;
use App\Exports\Sheets\PenggunaanSummarySheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PenggunaanReportExport implements WithMultipleSheets
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
            new PenggunaanSummarySheet($this->data['summary'] ?? []),
            new PenggunaanByBarangSheet($this->data['by_barang'] ?? []),
            new PenggunaanByCabangSheet($this->data['by_cabang'] ?? []),
            new PenggunaanDetailSheet($this->data['details'] ?? []),
            new FiltersSheet($this->filters, $this->user),
        ];
    }
}

