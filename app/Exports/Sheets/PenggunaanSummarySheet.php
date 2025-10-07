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

class PenggunaanSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $summary;

    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    public function collection()
    {
        return collect([
            ['Metric' => 'Total Penggunaan', 'Value' => $this->summary['total_penggunaan'] ?? 0],
            ['Metric' => 'Total Approved', 'Value' => $this->summary['total_approved'] ?? 0],
            ['Metric' => 'Total Pending', 'Value' => $this->summary['total_pending'] ?? 0],
            ['Metric' => 'Total Rejected', 'Value' => $this->summary['total_rejected'] ?? 0],
            ['Metric' => 'Total Barang Digunakan', 'Value' => $this->summary['total_barang_digunakan'] ?? 0],
            ['Metric' => 'Total Nilai (Rp)', 'Value' => $this->summary['total_nilai'] ?? 0],
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
            ]
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
