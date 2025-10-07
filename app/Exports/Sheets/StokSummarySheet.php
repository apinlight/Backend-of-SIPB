<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class StokSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect([
            ['Metric' => 'Total Items', 'Value' => $this->data['total_items'] ?? 0],
            ['Metric' => 'Total Stock', 'Value' => $this->data['total_stock'] ?? 0],
            ['Metric' => 'Total Value (Rp)', 'Value' => $this->data['total_value'] ?? 0],
            ['Metric' => 'Empty Stock Items', 'Value' => $this->data['empty_stock'] ?? 0],
            ['Metric' => 'Low Stock Items', 'Value' => $this->data['low_stock'] ?? 0],
            ['Metric' => 'Normal Stock Items', 'Value' => $this->data['normal_stock'] ?? 0],
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'B' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]
            ],
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
