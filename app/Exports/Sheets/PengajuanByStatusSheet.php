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

class PengajuanByStatusSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)->map(function($item) {
            return [
                'Status' => $item['status'],
                'Jumlah Pengajuan' => $item['count'],
                'Total Items' => $item['total_items'],
                'Total Nilai' => $item['total_nilai'],
                'Rata-rata Nilai' => $item['avg_nilai'],
                'Persentase' => $item['percentage'] / 100, // Excel handles percentage formatting
            ];
        });
    }

    public function headings(): array
    {
        return ['Status', 'Jumlah Pengajuan', 'Total Items', 'Total Nilai (Rp)', 'Rata-rata Nilai (Rp)', 'Persentase (%)'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6E6']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'D:E' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'F' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_PERCENTAGE_00]],
        ];
    }

    public function title(): string
    {
        return 'By Status';
    }
}
