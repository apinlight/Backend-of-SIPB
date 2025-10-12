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

class PenggunaanDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
                'ID Penggunaan' => $item->id_penggunaan,
                'Tanggal' => $item->tanggal_penggunaan->format('Y-m-d'),
                'Username' => $item->user->username ?? '-',
                'Branch' => $item->user->branch_name ?? '-',
                'Nama Barang' => $item->barang->nama_barang ?? '-',
                'Jenis Barang' => $item->barang->jenisBarang->nama_jenis_barang ?? '-',
                'Harga Satuan' => $item->barang->harga_barang ?? 0,
                'Jumlah Digunakan' => $item->jumlah_digunakan,
                'Total Nilai' => ($item->barang->harga_barang ?? 0) * $item->jumlah_digunakan,
                'Keperluan' => $item->keperluan,
                'Status' => ucfirst($item->status),
                'Approver' => $item->approver->username ?? '-',
                'Approved At' => $item->approved_at ? $item->approved_at->format('Y-m-d H:i') : '-',
            ];
        });
    }

    public function headings(): array
    {
        return ['ID Penggunaan', 'Tanggal Penggunaan', 'Username', 'Branch', 'Nama Barang', 'Jenis Barang', 'Harga Satuan (Rp)', 'Jumlah Digunakan', 'Total Nilai (Rp)', 'Keperluan', 'Status', 'Approver', 'Approved At'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'G' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'I' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'M' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function title(): string
    {
        return 'Penggunaan Detail';
    }
}
