<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StokByBranchSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)->map(function ($item) {
            return [
                'Branch Name' => $item['branch_name'],
                'Total Items' => $item['total_items'],
                'Total Stock' => $item['total_stock'],
                'Total Value' => $item['total_value'],
                'Empty Stock' => $item['empty_stock'],
                'Low Stock' => $item['low_stock'],
                'Stock Health %' => ($item['total_items'] > 0)
                    ? round((($item['total_items'] - $item['empty_stock'] - $item['low_stock']) / $item['total_items']) * 100, 1) / 100
                    : 0,
            ];
        });
    }

    public function headings(): array
    {
        return ['Branch Name', 'Total Items', 'Total Stock', 'Total Value (Rp)', 'Empty Stock', 'Low Stock', 'Stock Health (%)'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F2C6C6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'D' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'G' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_PERCENTAGE_00]],
        ];
    }

    public function title(): string
    {
        return 'By Branch';
    }
}
