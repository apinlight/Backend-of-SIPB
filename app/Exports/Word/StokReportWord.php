<?php

namespace App\Exports\Word;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class StokReportWord
{
    public function __construct(
        protected array $reportData,
        protected array $filters,
        protected $user,
    ) {
    }

    private function normalize($value): array
    {
        if (is_array($value)) return $value;
        if (is_object($value)) return json_decode(json_encode($value), true) ?: [];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return [];
    }

    private function formatUser($row): string
    {
        $row = $this->normalize($row);
        $u = $row['user'] ?? null;
        if ($u !== null) $u = $this->normalize($u);
        if (is_array($u)) {
            $username = $u['username'] ?? $u['name'] ?? $u['unique_id'] ?? null;
            $branch = $u['cabang']['nama_cabang'] ?? $u['branch_name'] ?? null;
            return trim(($username ?? '-') . ($branch ? " ({$branch})" : ''));
        }
        return (string)($row['user_name'] ?? $row['username'] ?? $row['unique_id'] ?? '-');
    }

    private function formatBarang($row): string
    {
        $row = $this->normalize($row);
        $b = $row['barang'] ?? null;
        if ($b !== null) $b = $this->normalize($b);
        if (is_array($b)) {
            $name = $b['nama_barang'] ?? ($b['name'] ?? null);
            $kategori = $b['jenis_barang']['nama_jenis_barang'] ?? $b['kategori'] ?? null;
            return trim(($name ?? '-') . ($kategori ? " - {$kategori}" : ''));
        }
        $name = $row['nama_barang'] ?? $row['barang_name'] ?? null;
        $kategori = $row['kategori'] ?? null;
        return trim(($name ?? '-') . ($kategori ? " - {$kategori}" : ''));
    }

    private function computeNilaiStok($row)
    {
        $row = $this->normalize($row);
        $nilai = $row['nilai_stok'] ?? null;
        if (is_numeric($nilai)) return $nilai + 0;
        $harga = $row['harga_barang'] ?? $row['harga'] ?? null;
        $qty = $row['jumlah_barang'] ?? $row['stok'] ?? null;
        if (is_numeric($harga) && is_numeric($qty)) return ($harga + 0) * ($qty + 0);
        return 0;
    }

    public function generate(): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        // Styles
        $phpWord->addFontStyle('title', ['bold' => true, 'size' => 16, 'color' => '2E74B5']);
        $phpWord->addFontStyle('heading', ['bold' => true, 'size' => 12]);
    $phpWord->addFontStyle('normal', ['size' => 10]);
        $phpWord->addTableStyle('reportTable', [
            'borderSize' => 6,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 80,
        ]);

        $section = $phpWord->addSection(['marginTop' => 1000, 'marginBottom' => 1000]);

    $section->addText('LAPORAN STOK', 'title');
        $section->addText('Generated: '.now()->format('d F Y, H:i'), 'normal');
        $section->addTextBreak(1);

        // Summary
        $summary = $this->reportData['summary'] ?? [];
        $section->addText('Ringkasan', 'heading');
        $sumTable = $section->addTable('reportTable');
        $sumTable->addRow();
        $sumTable->addCell(5000, ['bgColor' => '70AD47'])->addText('Metric', ['bold' => true, 'color' => 'FFFFFF']);
        $sumTable->addCell(3000, ['bgColor' => '70AD47'])->addText('Value', ['bold' => true, 'color' => 'FFFFFF']);

        $entries = [
            ['Total Barang', $summary['total_items'] ?? 0],
            ['Total Stok', $summary['total_stok_saat_ini'] ?? 0],
            ['Nilai Stok (Rp)', 'Rp '.number_format($summary['total_nilai_stok'] ?? 0, 0, ',', '.')],
            ['Items Stok Habis', $summary['items_stok_habis'] ?? 0],
            ['Items Stok Rendah', $summary['items_stok_rendah'] ?? 0],
        ];
        foreach ($entries as $i => [$label, $val]) {
            $sumTable->addRow();
            $bg = $i % 2 === 0 ? 'F2F2F2' : 'FFFFFF';
            $sumTable->addCell(5000, ['bgColor' => $bg])->addText($label, 'normal');
            $sumTable->addCell(3000, ['bgColor' => $bg])->addText((string) $val, 'normal');
        }

        $section->addTextBreak(1);

        // Details
        $section->addText('Detail Stok', 'heading');
        $table = $section->addTable('reportTable');
        $headers = ['Cabang/User', 'Barang', 'Stok', 'Nilai Stok', 'Status'];
        $table->addRow();
        foreach ($headers as $h) {
            $table->addCell(2000, ['bgColor' => '4472C4'])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }

        foreach (($this->reportData['stocks'] ?? []) as $i => $row) {
            $row = $this->normalize($row);
            $bg = $i % 2 === 0 ? 'F9F9F9' : 'FFFFFF';
            $table->addRow();
            $table->addCell(2000, ['bgColor' => $bg])->addText($this->formatUser($row), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText($this->formatBarang($row), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText((string)($row['jumlah_barang'] ?? $row['stok'] ?? 0), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText(number_format($this->computeNilaiStok($row), 0, ',', '.'), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText($row['status_stok'] ?? '-', ['size' => 9]);
        }

        $section->addTextBreak(1);
        $section->addText('Generated by SIPB System | '.now()->format('Y'), ['size' => 9, 'color' => '999999']);

        // Save temp file
        $fileName = $this->getFileName();
        $tempPath = storage_path('app/temp');
        if (!is_dir($tempPath)) { @mkdir($tempPath, 0777, true); }
        $filePath = $tempPath.'/'.$fileName;
        IOFactory::createWriter($phpWord, 'Word2007')->save($filePath);
        return $filePath;
    }

    public function getFileName(): string
    {
        return 'Stok_Report_'.now()->format('Y-m-d_H-i-s').'.docx';
    }
}
