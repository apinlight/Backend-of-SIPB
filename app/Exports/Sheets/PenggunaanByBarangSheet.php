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

class PenggunaanByBarangSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        // Data sudah matang dari service, kita hanya memetakannya ke kolom.
        return collect($this->data)->map(function ($item) {
            return [
                'ID Barang' => $item['id_barang'],
                'Nama Barang' => $item['nama_barang'],
                'Jenis Barang' => $item['jenis_barang'] ?? '-',
                'Total Digunakan' => $item['total_digunakan'],
                'Total Nilai' => $item['total_nilai'],
                'Frekuensi Penggunaan' => $item['frekuensi_penggunaan'],
                'Approved' => $item['penggunaan_approved'],
                'Pending' => $item['penggunaan_pending'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID Barang',
            'Nama Barang',
            'Jenis Barang',
            'Total Digunakan',
            'Total Nilai (Rp)',
            'Frekuensi Penggunaan',
            'Approved',
            'Pending',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'E' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
        ];
    }

    public function title(): string
    {
        return 'By Barang';
    }
}
