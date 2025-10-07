<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StokLowAlertSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)
            ->filter(fn($item) => $this->isLowOrEmpty($item))
            ->map(fn($item) => [
                'Priority' => $item->jumlah_barang == 0 ? 'URGENT' : 'Warning',
                'Username' => $item->user->username,
                'Branch' => $item->user->branch_name,
                'Nama Barang' => $item->barang->nama_barang,
                'Stok Saat Ini' => $item->jumlah_barang,
                'Batas Minimum' => $item->barang->batas_minimum ?? 5,
                'Action Required' => $item->jumlah_barang == 0 ? 'Segera Restock' : 'Monitoring',
            ])
            ->sortBy(fn($item) => $item['Priority'] === 'URGENT' ? 0 : 1)
            ->values();
    }
    
    public function headings(): array
    {
        return ['Priority', 'Username', 'Branch', 'Nama Barang', 'Stok Saat Ini', 'Batas Minimum', 'Action Required'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
        ];
    }

    public function title(): string
    {
        return 'Low Stock Alert';
    }

    private function isLowOrEmpty($item): bool
    {
        $batasMinimum = $item->barang->batas_minimum ?? 5;
        return $item->jumlah_barang <= $batasMinimum;
    }
}
