<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Support\Collection;

class BarangReportExport implements WithMultipleSheets
{
    protected $data;
    protected $filters;
    protected $user;

    public function __construct($data, $filters = [], $user = null)
    {
        $this->data = $data;
        $this->filters = $filters;
        $this->user = $user;
    }

    public function sheets(): array
    {
        return [
            new BarangDetailSheet($this->data, $this->filters, $this->user),
            new BarangSummarySheet($this->data, $this->filters, $this->user),
            new FiltersSheet($this->filters, $this->user),
        ];
    }
}

class BarangDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $filters;
    protected $user;

    public function __construct($data, $filters = [], $user = null)
    {
        $this->data = $data;
        $this->filters = $filters;
        $this->user = $user;
    }

    public function collection()
    {
        return collect($this->data)->map(function($item) {
            return [
                'ID Barang' => $item['id_barang'],
                'Nama Barang' => $item['nama_barang'],
                'Jenis Barang' => $item['jenis_barang']['nama_jenis_barang'] ?? '-',
                'Harga Satuan' => $item['harga_barang'],
                'Total Pengadaan' => $item['total_pengadaan'],
                'Nilai Pengadaan' => $item['nilai_pengadaan'],
                'Stok Saat Ini' => $item['stok_saat_ini'],
                'Nilai Stok' => $item['nilai_stok'],
                'Status Stok' => $this->getStockStatus($item['stok_saat_ini'], $item['batas_minimum']),
                'Batas Minimum' => $item['batas_minimum'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID Barang',
            'Nama Barang', 
            'Jenis Barang',
            'Harga Satuan (Rp)',
            'Total Pengadaan',
            'Nilai Pengadaan (Rp)',
            'Stok Saat Ini',
            'Nilai Stok (Rp)',
            'Status Stok',
            'Batas Minimum'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'D:D' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'F:F' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'H:H' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
        ];
    }

    public function title(): string
    {
        return 'Barang Detail';
    }

    private function getStockStatus($current, $minimum)
    {
        if ($current == 0) return 'Habis';
        if ($current <= $minimum) return 'Rendah';
        return 'Normal';
    }
}

class BarangSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $filters;
    protected $user;

    public function __construct($data, $filters = [], $user = null)
    {
        $this->data = $data;
        $this->filters = $filters;
        $this->user = $user;
    }

    public function collection()
    {
        $collection = collect($this->data);
        
        return collect([
            [
                'Metric' => 'Total Items',
                'Value' => $collection->count(),
                'Description' => 'Total number of items'
            ],
            [
                'Metric' => 'Total Pengadaan',
                'Value' => $collection->sum('total_pengadaan'),
                'Description' => 'Total items procured'
            ],
            [
                'Metric' => 'Total Nilai Pengadaan',
                'Value' => number_format($collection->sum('nilai_pengadaan'), 0, ',', '.'),
                'Description' => 'Total procurement value (Rp)'
            ],
            [
                'Metric' => 'Total Stok Saat Ini',
                'Value' => $collection->sum('stok_saat_ini'),
                'Description' => 'Current total stock'
            ],
            [
                'Metric' => 'Total Nilai Stok',
                'Value' => number_format($collection->sum('nilai_stok'), 0, ',', '.'),
                'Description' => 'Current total stock value (Rp)'
            ],
            [
                'Metric' => 'Items Stok Habis',
                'Value' => $collection->where('stok_saat_ini', 0)->count(),
                'Description' => 'Items with zero stock'
            ],
            [
                'Metric' => 'Items Stok Rendah',
                'Value' => $collection->filter(function($item) {
                    return $item['stok_saat_ini'] > 0 && $item['stok_saat_ini'] <= $item['batas_minimum'];
                })->count(),
                'Description' => 'Items with low stock'
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Value',
            'Description'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'B:B' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
