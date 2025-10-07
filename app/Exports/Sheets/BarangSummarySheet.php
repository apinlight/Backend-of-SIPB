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

class BarangSummarySheet implements FromCollection, WithTitle, WithHeadings, WithStyles {
    protected $summary;
    public function __construct(array $summary) { $this->summary = $summary; }
    public function collection() {
        return collect([
            ['Metric' => 'Total Items', 'Value' => $this->summary['total_items']],
            ['Metric' => 'Total Pengadaan', 'Value' => $this->summary['total_pengadaan']],
            ['Metric' => 'Total Nilai Pengadaan (Rp)', 'Value' => $this->summary['total_nilai_pengadaan']],
            ['Metric' => 'Total Stok Saat Ini', 'Value' => $this->summary['total_stok_saat_ini']],
            ['Metric' => 'Total Nilai Stok (Rp)', 'Value' => $this->summary['total_nilai_stok']],
            ['Metric' => 'Items Stok Habis', 'Value' => $this->summary['items_stok_habis']],
            ['Metric' => 'Items Stok Rendah', 'Value' => $this->summary['items_stok_rendah']],
        ]);
    }
    public function title(): string { return 'Summary'; }
    public function headings(): array { return ['Metric', 'Value']; }
    public function styles(Worksheet $sheet) 
    {
        return [
           // Style baris header (baris pertama)
           1 => [
               'font' => [
                   'bold' => true, 
                   'size' => 12,
                   'color' => ['rgb' => 'FFFFFF']
               ],
               'fill' => [
                   'fillType' => Fill::FILL_SOLID,
                   'startColor' => ['rgb' => '70AD47'] // Warna hijau konsisten dengan FiltersSheet
               ],
               'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
           ],

           // Style kolom 'Value' (kolom B) agar rata kanan
           'B' => [
               'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
           ],

           // Terapkan format angka pada kolom B, mulai dari baris kedua
           'B2:B8' => [
                'numberFormat' => ['formatCode' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1]
           ],
       ];
    }
}