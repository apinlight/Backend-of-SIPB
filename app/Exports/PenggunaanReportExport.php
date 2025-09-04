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

class PenggunaanReportExport implements WithMultipleSheets
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
            new PenggunaanDetailSheet($this->data['detail'] ?? [], $this->filters, $this->user),
            new PenggunaanSummarySheet($this->data['summary'] ?? [], $this->filters, $this->user),
            new PenggunaanByBarangSheet($this->data['by_barang'] ?? [], $this->filters, $this->user),
            new PenggunaanByCabangSheet($this->data['by_cabang'] ?? [], $this->filters, $this->user),
            new FiltersSheet($this->filters, $this->user),
        ];
    }
}

class PenggunaanDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
                'ID Penggunaan' => $item['id_penggunaan'],
                'Tanggal' => $item['tanggal_penggunaan'],
                'Username' => $item['user']['username'] ?? '-',
                'Branch' => $item['user']['branch_name'] ?? '-',
                'Nama Barang' => $item['barang']['nama_barang'] ?? '-',
                'Jenis Barang' => $item['barang']['jenis_barang'] ?? '-',
                'Harga Satuan' => $item['barang']['harga_barang'] ?? 0,
                'Jumlah Digunakan' => $item['jumlah_digunakan'],
                'Total Nilai' => $item['total_nilai'],
                'Keperluan' => $item['keperluan'],
                'Status' => ucfirst($item['status']),
                'Approver' => $item['approver']['username'] ?? '-',
                'Approved At' => $item['approved_at'] ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ID Penggunaan',
            'Tanggal Penggunaan',
            'Username',
            'Branch',
            'Nama Barang',
            'Jenis Barang',
            'Harga Satuan (Rp)',
            'Jumlah Digunakan',
            'Total Nilai (Rp)',
            'Keperluan',
            'Status',
            'Approver',
            'Approved At'
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
            'G:G' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'I:I' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'B:B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            'M:M' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function title(): string
    {
        return 'Penggunaan Detail';
    }
}

class PenggunaanSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
                'Metric' => 'Total Penggunaan',
                'Value' => $this->data['total_penggunaan'] ?? 0,
                'Description' => 'Total usage records'
            ],
            [
                'Metric' => 'Total Approved',
                'Value' => $this->data['total_approved'] ?? 0,
                'Description' => 'Approved usage records'
            ],
            [
                'Metric' => 'Total Pending',
                'Value' => $this->data['total_pending'] ?? 0,
                'Description' => 'Pending approval'
            ],
            [
                'Metric' => 'Total Rejected',
                'Value' => $this->data['total_rejected'] ?? 0,
                'Description' => 'Rejected usage records'
            ],
            [
                'Metric' => 'Total Barang Digunakan',
                'Value' => $this->data['total_barang_digunakan'] ?? 0,
                'Description' => 'Total items used'
            ],
            [
                'Metric' => 'Total Nilai',
                'Value' => number_format($this->data['total_nilai'] ?? 0, 0, ',', '.'),
                'Description' => 'Total value (Rp)'
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
                'font' => ['bold' => true,
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

class PenggunaanByBarangSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
            'Pending'
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
            'E:E' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
        ];
    }

    public function title(): string
    {
        return 'By Barang';
    }
}

class PenggunaanByCabangSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
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
            'Total Nilai (Rp)'
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
            'G:G' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
        ];
    }

    public function title(): string
    {
        return 'By Branch';
    }
}
