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

class PengajuanReportExport implements WithMultipleSheets
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
            new PengajuanDetailSheet($this->data, $this->filters, $this->user),
            new PengajuanSummarySheet($this->data, $this->filters, $this->user),
            new PengajuanByStatusSheet($this->data, $this->filters, $this->user),
            new FiltersSheet($this->filters, $this->user),
        ];
    }
}

class PengajuanDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
                'ID Pengajuan' => $item['id_pengajuan'],
                'Username' => $item['user']['username'] ?? '-',
                'Branch' => $item['user']['branch_name'] ?? '-',
                'Status' => $item['status_pengajuan'],
                'Total Items' => $item['total_items'],
                'Total Nilai' => $item['total_nilai'],
                'Tanggal Dibuat' => $item['created_at'] ? date('Y-m-d H:i', strtotime($item['created_at'])) : '-',
                'Tanggal Update' => $item['updated_at'] ? date('Y-m-d H:i', strtotime($item['updated_at'])) : '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID Pengajuan',
            'Username',
            'Branch',
            'Status',
            'Total Items',
            'Total Nilai (Rp)',
            'Tanggal Dibuat',
            'Tanggal Update'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF']
            ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'F:F' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'G:H' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function title(): string
    {
        return 'Pengajuan Detail';
    }
}

class PengajuanSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
                'Metric' => 'Total Pengajuan',
                'Value' => $collection->count(),
                'Description' => 'Total number of requests'
            ],
            [
                'Metric' => 'Disetujui',
                'Value' => $collection->where('status_pengajuan', 'Disetujui')->count(),
                'Description' => 'Approved requests'
            ],
            [
                'Metric' => 'Menunggu Persetujuan',
                'Value' => $collection->where('status_pengajuan', 'Menunggu Persetujuan')->count(),
                'Description' => 'Pending requests'
            ],
            [
                'Metric' => 'Ditolak',
                'Value' => $collection->where('status_pengajuan', 'Ditolak')->count(),
                'Description' => 'Rejected requests'
            ],
            [
                'Metric' => 'Selesai',
                'Value' => $collection->where('status_pengajuan', 'Selesai')->count(),
                'Description' => 'Completed requests'
            ],
            [
                'Metric' => 'Total Items Diminta',
                'Value' => $collection->sum('total_items'),
                'Description' => 'Total items requested'
            ],
            [
                'Metric' => 'Total Nilai',
                'Value' => number_format($collection->sum('total_nilai'), 0, ',', '.'),
                'Description' => 'Total value (Rp)'
            ],
            [
                'Metric' => 'Rata-rata Nilai per Pengajuan',
                'Value' => $collection->count() > 0 ? number_format($collection->avg('total_nilai'), 0, ',', '.') : 0,
                'Description' => 'Average value per request (Rp)'
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
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
        ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47']
                ],
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

class PengajuanByStatusSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
        
        return $collection->groupBy('status_pengajuan')->map(function($items, $status) {
            return [
                'Status' => $status,
                'Jumlah Pengajuan' => $items->count(),
                'Total Items' => $items->sum('total_items'),
                'Total Nilai' => $items->sum('total_nilai'),
                'Rata-rata Nilai' => $items->count() > 0 ? $items->avg('total_nilai') : 0,
                'Persentase' => collect($this->data)->count() > 0 ? 
                    round(($items->count() / collect($this->data)->count()) * 100, 1) : 0,
            ];
        })->values();
    }

    public function headings(): array
    {
        return [
            'Status',
            'Jumlah Pengajuan',
            'Total Items',
            'Total Nilai (Rp)',
            'Rata-rata Nilai (Rp)',
            'Persentase (%)'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E6E6']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'D:E' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'F:F' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_PERCENTAGE_00]],
        ];
    }

    public function title(): string
    {
        return 'By Status';
    }
}
