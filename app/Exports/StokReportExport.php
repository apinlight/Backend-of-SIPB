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
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Support\Collection;

class StokReportExport implements WithMultipleSheets
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
            new StokDetailSheet($this->data['stocks'] ?? [], $this->filters, $this->user),
            new StokSummarySheet($this->data['summary'] ?? [], $this->filters, $this->user),
            new StokByBranchSheet($this->data['by_branch'] ?? [], $this->filters, $this->user),
            new StokLowAlertSheet($this->data['stocks'] ?? [], $this->filters, $this->user),
            new FiltersSheet($this->filters, $this->user),
        ];
    }
}

class StokDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
                'User ID' => $item['unique_id'],
                'Username' => $item['user']['username'] ?? '-',
                'Branch' => $item['user']['branch_name'] ?? '-',
                'ID Barang' => $item['barang']['id_barang'] ?? '-',
                'Nama Barang' => $item['barang']['nama_barang'] ?? '-',
                'Jenis Barang' => $item['barang']['jenis_barang'] ?? '-',
                'Harga Satuan' => $item['barang']['harga_barang'] ?? 0,
                'Jumlah Stok' => $item['jumlah_barang'],
                'Total Nilai' => $item['total_nilai'],
                'Status Stok' => $this->getStockStatusText($item['stock_status']),
                'Created At' => $item['created_at'],
                'Updated At' => $item['updated_at'],
            ];
        });
    }

    public function headings(): array
    {
        return [
            'User ID',
            'Username',
            'Branch',
            'ID Barang',
            'Nama Barang',
            'Jenis Barang',
            'Harga Satuan (Rp)',
            'Jumlah Stok',
            'Total Nilai (Rp)',
            'Status Stok',
            'Created At',
            'Updated At'
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
            'G:G' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'I:I' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'K:L' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function title(): string
    {
        return 'Stok Detail';
    }

    private function getStockStatusText($status)
    {
        switch ($status) {
            case 'empty': return 'Habis';
            case 'low': return 'Rendah';
            case 'normal': return 'Normal';
            default: return 'Unknown';
        }
    }
}

class StokSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        return collect([
            [
                'Metric' => 'Total Items',
                'Value' => $this->data['total_items'] ?? 0,
                'Description' => 'Total number of stock entries'
            ],
            [
                'Metric' => 'Total Stock',
                'Value' => $this->data['total_stock'] ?? 0,
                'Description' => 'Total stock quantity'
            ],
            [
                'Metric' => 'Total Value',
                'Value' => number_format($this->data['total_value'] ?? 0, 0, ',', '.'),
                'Description' => 'Total stock value (Rp)'
            ],
            [
                'Metric' => 'Empty Stock Items',
                'Value' => $this->data['empty_stock'] ?? 0,
                'Description' => 'Items with zero stock'
            ],
            [
                'Metric' => 'Low Stock Items',
                'Value' => $this->data['low_stock'] ?? 0,
                'Description' => 'Items with low stock (1-5)'
            ],
            [
                'Metric' => 'Normal Stock Items',
                'Value' => $this->data['normal_stock'] ?? 0,
                'Description' => 'Items with normal stock (>5)'
            ],
            [
                'Metric' => 'Average Stock per Item',
                'Value' => ($this->data['total_items'] ?? 0) > 0 ? 
                    round(($this->data['total_stock'] ?? 0) / $this->data['total_items'], 2) : 0,
                'Description' => 'Average stock quantity per item'
            ],
            [
                'Metric' => 'Average Value per Item',
                'Value' => ($this->data['total_items'] ?? 0) > 0 ? 
                    number_format(($this->data['total_value'] ?? 0) / $this->data['total_items'], 0, ',', '.') : 0,
                'Description' => 'Average value per item (Rp)'
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

class StokByBranchSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
                'Branch Name' => $item['branch_name'],
                'Total Items' => $item['total_items'],
                'Total Stock' => $item['total_stock'],
                'Total Value' => $item['total_value'],
                'Empty Stock' => $item['empty_stock'],
                'Low Stock' => $item['low_stock'],
                'Stock Health %' => $item['total_items'] > 0 ? 
                    round((($item['total_items'] - $item['empty_stock'] - $item['low_stock']) / $item['total_items']) * 100, 1) : 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Branch Name',
            'Total Items',
            'Total Stock',
            'Total Value (Rp)',
            'Empty Stock',
            'Low Stock',
            'Stock Health (%)'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F2C6C6']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'D:D' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'G:G' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_PERCENTAGE_00]],
        ];
    }

    public function title(): string
    {
        return 'By Branch';
    }
}

class StokLowAlertSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        // Filter for empty and low stock items only
        return collect($this->data)
            ->filter(function($item) {
                return in_array($item['stock_status'], ['empty', 'low']);
            })
            ->map(function($item) {
                return [
                    'Priority' => $item['stock_status'] === 'empty' ? 'URGENT' : 'Warning',
                    'Username' => $item['user']['username'] ?? '-',
                    'Branch' => $item['user']['branch_name'] ?? '-',
                    'Nama Barang' => $item['barang']['nama_barang'] ?? '-',
                    'Jenis Barang' => $item['barang']['jenis_barang'] ?? '-',
                    'Stok Saat Ini' => $item['jumlah_barang'],
                    'Status' => $this->getStockStatusText($item['stock_status']),
                    'Action Required' => $item['stock_status'] === 'empty' ? 'Segera Restock' : 'Monitoring',
                    'Last Updated' => $item['updated_at'],
                ];
            })
            ->sortBy(function($item) {
                return $item['Priority'] === 'URGENT' ? 0 : 1;
            })
            ->values();
    }

    public function headings(): array
    {
        return [
            'Priority',
            'Username',
            'Branch',
            'Nama Barang',
            'Jenis Barang',
            'Stok Saat Ini',
            'Status',
            'Action Required',
            'Last Updated'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FF0000']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ]
        ];
    }

    public function title(): string
    {
        return 'Low Stock Alert';
    }

    private function getStockStatusText($status)
    {
        switch ($status) {
            case 'empty': return 'HABIS';
            case 'low': return 'RENDAH';
            default: return 'Unknown';
        }
    }
}
