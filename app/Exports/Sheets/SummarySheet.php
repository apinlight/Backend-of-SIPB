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

class SummarySheet implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected $summary;

    public function __construct(array $summary)
    {
        $this->summary = $summary;
    }

    public function collection()
    {
        // Data sudah dihitung oleh LaporanService, kita hanya menampilkannya.
        return collect([
            ['Metric' => 'Total Pengajuan', 'Value' => $this->summary['total_pengajuan'] ?? 0, 'Description' => 'Total number of requests'],
            ['Metric' => 'Total Disetujui', 'Value' => $this->summary['total_disetujui'] ?? 0, 'Description' => 'Approved requests'],
            ['Metric' => 'Total Menunggu', 'Value' => $this->summary['total_menunggu'] ?? 0, 'Description' => 'Pending requests'],
            ['Metric' => 'Total Ditolak', 'Value' => $this->summary['total_ditolak'] ?? 0, 'Description' => 'Rejected requests'],
            ['Metric' => 'Total Selesai', 'Value' => $this->summary['total_selesai'] ?? 0, 'Description' => 'Completed requests'],
            ['Metric' => 'Total Nilai (Rp)', 'Value' => $this->summary['total_nilai'] ?? 0, 'Description' => 'Total value in Rupiah'],
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value', 'Description'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '70AD47']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],
            'B' => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                'numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1],
            ],
        ];
    }

    public function title(): string
    {
        return 'Summary Report';
    }
}
