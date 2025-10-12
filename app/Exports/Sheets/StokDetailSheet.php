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

class StokDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
                'User ID' => $item->user->unique_id,
                'Username' => $item->user->username,
                'Branch' => $item->user->branch_name,
                'ID Barang' => $item->barang->id_barang,
                'Nama Barang' => $item->barang->nama_barang,
                'Jenis Barang' => $item->barang->jenisBarang->nama_jenis_barang ?? '-',
                'Harga Satuan' => $item->barang->harga_barang ?? 0,
                'Jumlah Stok' => $item->jumlah_barang,
                'Total Nilai' => ($item->barang->harga_barang ?? 0) * $item->jumlah_barang,
                'Status Stok' => $this->getStockStatusText($item),
                'Created At' => $item->created_at->format('Y-m-d H:i:s'),
                'Updated At' => $item->updated_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return ['User ID', 'Username', 'Branch', 'ID Barang', 'Nama Barang', 'Jenis Barang', 'Harga Satuan (Rp)', 'Jumlah Stok', 'Total Nilai (Rp)', 'Status Stok', 'Created At', 'Updated At'];
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
            'K:L' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function title(): string
    {
        return 'Stok Detail';
    }

    private function getStockStatusText($stockItem)
    {
        $batasMinimum = $stockItem->barang->batas_minimum ?? 5;
        if ($stockItem->jumlah_barang == 0) {
            return 'Habis';
        }
        if ($stockItem->jumlah_barang <= $batasMinimum) {
            return 'Rendah';
        }

        return 'Normal';
    }
}
