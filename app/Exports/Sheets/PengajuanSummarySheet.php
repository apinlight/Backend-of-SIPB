<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PengajuanSummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $summary;

    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    public function collection()
    {
        return collect([
            ['Metric' => 'Total Pengajuan', 'Value' => $this->summary['total_pengajuan']],
            ['Metric' => 'Disetujui', 'Value' => $this->summary['disetujui']],
            ['Metric' => 'Menunggu Persetujuan', 'Value' => $this->summary['menunggu']],
            ['Metric' => 'Ditolak', 'Value' => $this->summary['ditolak']],
            ['Metric' => 'Selesai', 'Value' => $this->summary['selesai']],
            ['Metric' => 'Total Items Diminta', 'Value' => $this->summary['total_items']],
            ['Metric' => 'Total Nilai (Rp)', 'Value' => $this->summary['total_nilai']],
            ['Metric' => 'Rata-rata Nilai per Pengajuan (Rp)', 'Value' => $this->summary['avg_nilai']],
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            'B' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]
            ]
        ];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
