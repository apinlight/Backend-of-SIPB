<?php
// app/Services/PengajuanValidationService.php

namespace App\Services;

use App\Models\GlobalSetting;
use App\Models\Pengajuan;
use App\Models\Gudang;
use App\Models\BatasBarang;
use Carbon\Carbon;

class PengajuanValidationService
{
    /**
     * Validate pengajuan limits (monthly and stock)
     */
    public static function validatePengajuanLimits(string $unique_id, array $items): array
    {
        $errors = [];
        
        // ✅ 1. Check monthly pengajuan limit
        $monthlyLimit = GlobalSetting::getMonthlyPengajuanLimit();
        $currentMonthCount = Pengajuan::where('unique_id', $unique_id)
            ->whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereIn('status_pengajuan', ['Menunggu Persetujuan', 'Disetujui'])
            ->count();
            
        if ($currentMonthCount >= $monthlyLimit) {
            $errors['monthly_limit'] = "Anda sudah mencapai batas pengajuan bulanan ({$monthlyLimit} pengajuan)";
        }
        
        // ✅ 2. Check stock limits for each item
        foreach ($items as $item) {
            $batasBarang = BatasBarang::where('id_barang', $item['id_barang'])->first();
            $currentStock = Gudang::where('unique_id', 'ADMIN001') // Admin stock
                ->where('id_barang', $item['id_barang'])
                ->value('jumlah_barang') ?? 0;
                
            if ($batasBarang && $item['jumlah'] > $batasBarang->batas_barang) {
                $errors["items.{$item['id_barang']}.jumlah"] = "Jumlah melebihi batas maksimal ({$batasBarang->batas_barang})";
            }
            
            if ($item['jumlah'] > $currentStock) {
                $errors["items.{$item['id_barang']}.stock"] = "Stok tidak mencukupi (tersedia: {$currentStock})";
            }
        }
        
        return $errors;
    }
}
