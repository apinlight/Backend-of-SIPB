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

class BarangDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)->map(fn ($item) => [
            'ID Barang' => $item['id_barang'], 'Nama Barang' => $item['nama_barang'],
            'Jenis Barang' => $item['jenis_barang'] ? $item['jenis_barang']->nama_jenis_barang : '-',
            'Harga Satuan' => $item['harga_barang'], 'Total Pengadaan' => $item['total_pengadaan'],
            'Nilai Pengadaan' => $item['nilai_pengadaan'], 'Stok Saat Ini' => $item['stok_saat_ini'],
            'Nilai Stok' => $item['nilai_stok'], 'Status Stok' => $item['status_stok'],
            'Batas Minimum' => $item['batas_minimum'],
        ]);
    }

    public function title(): string
    {
        return 'Barang Detail';
    }

    public function headings(): array
    {
        return ['ID Barang', 'Nama Barang', 'Jenis Barang', 'Harga Satuan (Rp)', 'Total Pengadaan', 'Nilai Pengadaan (Rp)', 'Stok Saat Ini', 'Nilai Stok (Rp)', 'Status Stok', 'Batas Minimum'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'D:D' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'F:F' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'H:H' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
        ];
    }
}
