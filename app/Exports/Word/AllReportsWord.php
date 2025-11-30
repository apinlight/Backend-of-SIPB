<?php

namespace App\Exports\Word;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Element\Section;

class AllReportsWord
{
    public function __construct(
        protected array $payload, // ['summary'=>..., 'barang'=>..., 'pengajuan'=>..., 'penggunaan'=>..., 'stok'=>...]
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

    private function getNumber($row, string $key, $default = 0)
    {
        $row = $this->normalize($row);
        $val = $row[$key] ?? $default;
        return is_numeric($val) ? $val + 0 : $default;
    }

    private function computeNilaiStok($row)
    {
        $row = $this->normalize($row);
        $nilai = $row['nilai_stok'] ?? null;
        if (is_numeric($nilai)) return $nilai + 0;
        // Try nested barang harga
        $barang = $row['barang'] ?? null;
        if ($barang !== null) {
            $barang = $this->normalize($barang);
        }
        $harga = $row['harga_barang'] ?? $row['harga'] ?? ($barang['harga_barang'] ?? $barang['harga'] ?? null);
        $qty = $row['jumlah_barang'] ?? $row['stok'] ?? null;
        if (is_numeric($harga) && is_numeric($qty)) return ($harga + 0) * ($qty + 0);
        return 0;
    }

    private function computeStatusStok($row): string
    {
        $row = $this->normalize($row);
        $status = $row['status_stok'] ?? null;
        if (is_string($status) && $status !== '') return $status;
        $qty = $row['jumlah_barang'] ?? $row['stok'] ?? 0;
        $barang = $row['barang'] ?? [];
        $barang = $this->normalize($barang);
        $batas = $barang['batas_minimum'] ?? $row['batas_minimum'] ?? null;
        if ($qty <= 0) return 'HABIS';
        if (is_numeric($batas) && $qty <= ($batas + 0)) return 'RENDAH';
        return 'AMAN';
    }

    private function renderKvTable(Section $section, array $rows): void
    {
        $table = $section->addTable('reportTable');
        $table->addRow();
        $table->addCell(5000, ['bgColor' => '70AD47'])->addText('Metric', ['bold' => true, 'color' => 'FFFFFF']);
        $table->addCell(3000, ['bgColor' => '70AD47'])->addText('Value', ['bold' => true, 'color' => 'FFFFFF']);
        foreach (array_values($rows) as $i => $r) {
            $k = $r[0] ?? '';
            $v = $r[1] ?? '';
            $bg = $i % 2 === 0 ? 'F2F2F2' : 'FFFFFF';
            $table->addRow();
            $table->addCell(5000, ['bgColor' => $bg])->addText((string)$k, ['size' => 10]);
            $table->addCell(3000, ['bgColor' => $bg])->addText((string)$v, ['size' => 10]);
        }
    }

    public function generate(): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        // Styles
        $phpWord->addFontStyle('title', ['bold' => true, 'size' => 18, 'color' => '2E74B5']);
        $phpWord->addFontStyle('heading', ['bold' => true, 'size' => 14]);
        $phpWord->addFontStyle('subheading', ['bold' => true, 'size' => 12]);
    $phpWord->addFontStyle('normal', ['size' => 10]);
        $phpWord->addTableStyle('reportTable', [
            'borderSize' => 6,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 80,
        ]);

        // Cover/Title
        $cover = $phpWord->addSection(['marginTop' => 1200]);
        $cover->addText('LAPORAN KOMPREHENSIF', 'title');
        $cover->addText('Sistem Informasi & Pencatatan Barang (SIPB)', 'subheading');
        $cover->addText('Generated: '.now()->format('d F Y, H:i'), 'normal');
        $periodStr = trim(($this->filters['start_date'] ?? '').' - '.($this->filters['end_date'] ?? ''));
        $branchStr = $this->filters['branch'] ?? '';
        if ($periodStr || $branchStr) {
            $cover->addText('Periode: '.($periodStr ?: '-').'; Cabang: '.($branchStr ?: '-'), ['size' => 9, 'color' => '777777']);
        }
        $cover->addTextBreak(2);

        // Summary Section
        $section = $phpWord->addSection();
        $section->addText('Ringkasan', 'heading');
        $summary = $this->payload['summary'] ?? [];
        $this->renderKvTable($section, [
            ['Total Pengajuan', $summary['total_pengajuan'] ?? 0],
            ['Total Disetujui', $summary['total_disetujui'] ?? 0],
            ['Total Menunggu', $summary['total_menunggu'] ?? 0],
            ['Total Nilai (Rp)', 'Rp '.number_format($summary['total_nilai'] ?? 0, 0, ',', '.')],
        ]);

        // Barang Section
        $section = $phpWord->addSection();
        $section->addText('Analisis Barang', 'heading');
        $barang = $this->payload['barang'] ?? [];
        $table = $section->addTable('reportTable');
        $headers = ['Barang', 'Kategori', 'Total Pengajuan', 'Total Disetujui', 'Total Nilai'];
        $table->addRow();
        foreach ($headers as $h) {
            $table->addCell(1600, ['bgColor' => '4472C4'])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }
        foreach (($barang['details'] ?? []) as $i => $row) {
            $bg = $i % 2 === 0 ? 'F9F9F9' : 'FFFFFF';
            $table->addRow();
            $table->addCell(1600, ['bgColor' => $bg])->addText($row['nama_barang'] ?? '-', ['size' => 9]);
            $table->addCell(1600, ['bgColor' => $bg])->addText($row['kategori'] ?? '-', ['size' => 9]);
            $table->addCell(1600, ['bgColor' => $bg])->addText((string)($row['total_pengajuan'] ?? 0), ['size' => 9]);
            $table->addCell(1600, ['bgColor' => $bg])->addText((string)($row['total_disetujui'] ?? 0), ['size' => 9]);
            $table->addCell(1600, ['bgColor' => $bg])->addText('Rp '.number_format($row['total_nilai'] ?? 0, 0, ',', '.'), ['size' => 9]);
        }

        // Pengajuan Section
        $section = $phpWord->addSection();
        $section->addText('Analisis Pengajuan', 'heading');
        $pengajuan = $this->payload['pengajuan'] ?? [];
        $table = $section->addTable('reportTable');
        $headers = ['Tanggal', 'User', 'Status', 'Total Nilai'];
        $table->addRow();
        foreach ($headers as $h) {
            $table->addCell(2000, ['bgColor' => '4472C4'])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }
        foreach (($pengajuan['details'] ?? []) as $i => $row) {
            $row = $this->normalize($row);
            $bg = $i % 2 === 0 ? 'F9F9F9' : 'FFFFFF';
            $table->addRow();
            $table->addCell(2000, ['bgColor' => $bg])->addText(($row['tanggal'] ?? $row['created_at'] ?? '-'), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText($this->formatUser(is_array($row) ? $row : []), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText(($row['status'] ?? '-'), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText('Rp '.number_format($row['total_nilai'] ?? 0, 0, ',', '.'), ['size' => 9]);
        }

        // Penggunaan Section
        $section = $phpWord->addSection();
        $section->addText('Analisis Penggunaan Barang', 'heading');
        $penggunaan = $this->payload['penggunaan'] ?? [];
        $table = $section->addTable('reportTable');
        $headers = ['Tanggal', 'User/Cabang', 'Barang', 'Jumlah', 'Keperluan', 'Status'];
        $table->addRow();
        foreach ($headers as $h) {
            $table->addCell(1600, ['bgColor' => '4472C4'])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }
        foreach (($penggunaan['details'] ?? []) as $i => $row) {
            $row = $this->normalize($row);
            $bg = $i % 2 === 0 ? 'F9F9F9' : 'FFFFFF';
            $table->addRow();
            $table->addCell(1600, ['bgColor' => $bg])->addText(($row['tanggal'] ?? $row['created_at'] ?? '-'), ['size' => 9]);
            $table->addCell(1600, ['bgColor' => $bg])->addText($this->formatUser(is_array($row) ? $row : []), ['size' => 9]);
            $table->addCell(1600, ['bgColor' => $bg])->addText($this->formatBarang(is_array($row) ? $row : []), ['size' => 9]);
            $table->addCell(1600, ['bgColor' => $bg])->addText((string)($row['jumlah'] ?? $row['qty'] ?? 0), ['size' => 9]);
            $table->addCell(1600, ['bgColor' => $bg])->addText(($row['keperluan'] ?? '-'), ['size' => 9]);
            $table->addCell(1600, ['bgColor' => $bg])->addText(($row['status'] ?? '-'), ['size' => 9]);
        }

        // Stok Section
        $section = $phpWord->addSection();
        $section->addText('Status Stok', 'heading');
        $stok = $this->payload['stok'] ?? [];
        // Summary table for stok
        $this->renderKvTable($section, [
            ['Total Barang', ($stok['summary']['total_items'] ?? 0)],
            ['Total Stok', ($stok['summary']['total_stok_saat_ini'] ?? 0)],
            ['Nilai Stok (Rp)', 'Rp '.number_format($stok['summary']['total_nilai_stok'] ?? 0, 0, ',', '.')],
            ['Items Stok Habis', ($stok['summary']['items_stok_habis'] ?? 0)],
            ['Items Stok Rendah', ($stok['summary']['items_stok_rendah'] ?? 0)],
        ]);
        $section->addTextBreak(1);
        $table = $section->addTable('reportTable');
        $headers = ['Cabang/User', 'Barang', 'Stok', 'Nilai Stok', 'Status'];
        $table->addRow();
        foreach ($headers as $h) {
            $table->addCell(2000, ['bgColor' => '4472C4'])->addText($h, ['bold' => true, 'color' => 'FFFFFF', 'size' => 9]);
        }
        foreach (($stok['stocks'] ?? []) as $i => $row) {
            $row = $this->normalize($row);
            $bg = $i % 2 === 0 ? 'F9F9F9' : 'FFFFFF';
            $table->addRow();
            $table->addCell(2000, ['bgColor' => $bg])->addText($this->formatUser(is_array($row) ? $row : []), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText($this->formatBarang(is_array($row) ? $row : []), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText((string)($row['jumlah_barang'] ?? $row['stok'] ?? 0), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText(number_format($this->computeNilaiStok($row), 0, ',', '.'), ['size' => 9]);
            $table->addCell(2000, ['bgColor' => $bg])->addText($this->computeStatusStok($row), ['size' => 9]);
        }

        // Footer
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
        return 'All_Reports_'.now()->format('Y-m-d_H-i-s').'.docx';
    }
}
