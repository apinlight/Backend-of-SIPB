<?php

namespace App\Exports\Word;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class BarangReportWord
{
    protected $reportData;
    protected $filters;
    protected $user;

    public function __construct(array $reportData, array $filters, $user)
    {
        $this->reportData = $reportData;
        $this->filters = $filters;
        $this->user = $user;
    }

    public function generate(): string
    {
        $phpWord = new PhpWord();
        
        // Define styles
        $phpWord->addFontStyle('title', ['bold' => true, 'size' => 16, 'color' => '2E74B5']);
        $phpWord->addFontStyle('heading', ['bold' => true, 'size' => 12]);
        $phpWord->addFontStyle('normal', ['size' => 10]);
        $phpWord->addTableStyle('reportTable', [
            'borderSize' => 6,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 80,
        ]);

        $section = $phpWord->addSection([
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'orientation' => 'landscape',
        ]);
        
        // Header
        $section->addText('📦 LAPORAN BARANG & STOK', 'title');
        $section->addText('Generated: '.now()->format('d F Y, H:i'), 'normal');
        $section->addTextBreak(1);

        // Summary Section
        $summary = $this->reportData['summary'] ?? [];
        $section->addText('Ringkasan', 'heading');
        $sumTable = $section->addTable('reportTable');
        
        $sumTable->addRow();
        $sumTable->addCell(5000, ['bgColor' => '70AD47'])->addText('Metric', ['bold' => true, 'color' => 'FFFFFF']);
        $sumTable->addCell(3000, ['bgColor' => '70AD47'])->addText('Value', ['bold' => true, 'color' => 'FFFFFF']);
        
        $summaryEntries = [
            ['Total Items', $summary['total_items'] ?? 0],
            ['Total Pengadaan', $summary['total_pengadaan'] ?? 0],
            ['Total Nilai Pengadaan (Rp)', 'Rp '.number_format($summary['total_nilai_pengadaan'] ?? 0, 0, ',', '.')],
            ['Total Stok Saat Ini', $summary['total_stok_saat_ini'] ?? 0],
            ['Total Nilai Stok (Rp)', 'Rp '.number_format($summary['total_nilai_stok'] ?? 0, 0, ',', '.')],
            ['Items Stok Habis', $summary['items_stok_habis'] ?? 0],
            ['Items Stok Rendah', $summary['items_stok_rendah'] ?? 0],
        ];
        
        foreach ($summaryEntries as $i => [$label, $val]) {
            $sumTable->addRow();
            $bgColor = $i % 2 === 0 ? 'F2F2F2' : 'FFFFFF';
            $sumTable->addCell(5000, ['bgColor' => $bgColor])->addText($label, 'normal');
            $sumTable->addCell(3000, ['bgColor' => $bgColor])->addText((string) $val, 'normal');
        }

        $section->addTextBreak(1);
        
        // Detail Section
        $section->addText('Detail Barang', 'heading');
        $table = $section->addTable('reportTable');
        
        // Header row
        $table->addRow(500);
        $headers = ['Nama Barang', 'Jenis', 'Harga (Rp)', 'Pengadaan', 'Nilai Pengadaan', 'Stok', 'Nilai Stok', 'Status', 'Batas Min'];
        foreach ($headers as $h) {
            $table->addCell(2000, ['bgColor' => '4472C4'])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }
        
        // Data rows
        foreach (($this->reportData['details'] ?? []) as $i => $item) {
            $jenis = $this->extractJenisBarang($item['jenis_barang'] ?? null);
            
            $bgColor = $i % 2 === 0 ? 'F9F9F9' : 'FFFFFF';
            $table->addRow();
            $table->addCell(2000, ['bgColor' => $bgColor])->addText($item['nama_barang'] ?? '-', ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bgColor])->addText($jenis, ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bgColor])->addText(number_format($item['harga_barang'] ?? 0, 0, ',', '.'), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bgColor])->addText((string) ($item['total_pengadaan'] ?? 0), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bgColor])->addText(number_format($item['nilai_pengadaan'] ?? 0, 0, ',', '.'), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bgColor])->addText((string) ($item['stok_saat_ini'] ?? 0), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bgColor])->addText(number_format($item['nilai_stok'] ?? 0, 0, ',', '.'), ['size' => 9]);
            
            // Status with color coding
            $status = $item['status_stok'] ?? '-';
            $statusColor = match ($status) {
                'Habis' => 'D32F2F',
                'Rendah' => 'F57C00',
                default => '388E3C'
            };
            $table->addCell(2000, ['bgColor' => $bgColor])->addText($status, ['size' => 9, 'color' => $statusColor, 'bold' => true]);
            $table->addCell(2000, ['bgColor' => $bgColor])->addText((string) ($item['batas_minimum'] ?? 0), ['size' => 9]);
        }

        $section->addTextBreak(1);
        $section->addText('Generated by SIPB System | '.now()->format('Y'), ['size' => 9, 'color' => '999999']);

        // Save to temp file
        $fileName = $this->getFileName();
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) { @mkdir($tempPath, 0777, true); }
        $filePath = $tempPath.'/'.$fileName;
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);

        return $filePath;
    }

    public function getFileName(): string
    {
        return 'Barang_Report_'.now()->format('Y-m-d_H-i-s').'.docx';
    }

    protected function extractJenisBarang($jenisBarang): string
    {
        if (empty($jenisBarang)) {
            return '-';
        }

        if (is_array($jenisBarang)) {
            return $jenisBarang['nama_jenis_barang'] ?? ($jenisBarang['nama'] ?? '-');
        }

        return $jenisBarang->nama_jenis_barang ?? ($jenisBarang->nama ?? '-');
    }
}
