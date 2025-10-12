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

class PenggunaanByCabangSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
                'Total Penggunaan' => $item['total_penggunaan'],
                'Total Approved' => $item['total_approved'],
                'Total Pending' => $item['total_pending'],
                'Total Rejected' => $item['total_rejected'],
                'Total Barang Digunakan' => $item['total_barang_digunakan'],
                'Total Nilai' => $item['total_nilai'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Branch Name',
            'Total Penggunaan',
            'Total Approved',
            'Total Pending',
            'Total Rejected',
            'Total Barang Digunakan',
            'Total Nilai (Rp)',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2C6C6'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'G' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
        ];
    }

    public function title(): string
    {
        return 'By Branch';
    }
}
