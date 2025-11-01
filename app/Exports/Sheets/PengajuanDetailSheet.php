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

class PengajuanDetailSheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        return collect($this->data)->map(function ($item) {
            $createdAt = $item['created_at'] ?? null;
            $updatedAt = $item['updated_at'] ?? null;
            $formatDate = function ($val) {
                if (! $val) return '-';
                // Support Carbon instances or ISO/date strings
                if (is_object($val) && method_exists($val, 'format')) {
                    return $val->format('Y-m-d H:i');
                }
                try {
                    return \Carbon\Carbon::parse($val)->format('Y-m-d H:i');
                } catch (\Throwable $e) {
                    return (string) $val;
                }
            };
            return [
                'ID Pengajuan' => $item['id_pengajuan'],
                'Username' => $item['user']['username'] ?? '-',
                'Branch' => $item['user']['branch_name'] ?? '-',
                'Status' => $item['status_pengajuan'],
                'Total Items' => $item['total_items'],
                'Total Nilai' => $item['total_nilai'],
                'Tanggal Dibuat' => $formatDate($createdAt),
                'Tanggal Update' => $formatDate($updatedAt),
            ];
        });
    }

    public function headings(): array
    {
        return ['ID Pengajuan', 'Username', 'Branch', 'Status', 'Total Items', 'Total Nilai (Rp)', 'Tanggal Dibuat', 'Tanggal Update'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'F' => ['numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]],
            'G:H' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function title(): string
    {
        return 'Pengajuan Detail';
    }
}
