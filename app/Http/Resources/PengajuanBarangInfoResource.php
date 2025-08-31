<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanBarangInfoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'barang' => $this->resource['barang']->map(function($barang) {
                return [
                    'id_barang' => $barang->id_barang,
                    'nama_barang' => $barang->nama_barang,
                    'satuan' => $barang->satuan,
                    'harga_satuan' => $barang->harga_satuan,
                    'is_active' => $barang->is_active,
                    'jenis_barang' => $barang->jenisBarang ? [
                        'id_jenis_barang' => $barang->jenisBarang->id_jenis_barang,
                        'nama_jenis' => $barang->jenisBarang->nama_jenis,
                    ] : null,
                    'stock_info' => [
                        'user_stock' => $this->resource['userStock'][$barang->id_barang] ?? 0,
                        'admin_stock' => $this->resource['adminStock'][$barang->id_barang] ?? null,
                        'per_barang_limit' => $this->resource['barangLimits'][$barang->id_barang] ?? null,
                    ],
                ];
            }),
            'monthly_info' => [
                'limit' => $this->resource['monthlyLimit'],
                'used' => $this->resource['monthlyUsed'],
                'remaining' => max(0, $this->resource['monthlyLimit'] - $this->resource['monthlyUsed']),
                'current_month' => $this->resource['currentMonth']->format('Y-m'),
                'pengajuan_count' => $this->resource['pengajuanCount'],
                'pending_count' => $this->resource['pendingCount'],
                'percentage_used' => $this->resource['monthlyLimit'] > 0 
                    ? round(($this->resource['monthlyUsed'] / $this->resource['monthlyLimit']) * 100, 1)
                    : 0,
            ],
            'user_info' => [
                'unique_id' => $this->resource['user']->unique_id,
                'username' => $this->resource['user']->username,
                'branch_name' => $this->resource['user']->branch_name,
                'can_see_admin_stock' => $this->resource['canSeeAdminStock'],
                'role_info' => [
                    'is_admin' => $this->resource['user']->hasRole('admin'),
                    'is_manager' => $this->resource['user']->hasRole('manager'),
                    'is_user' => $this->resource['user']->hasRole('user'),
                ],
            ],
            'summary' => [
                'total_barang_available' => $this->resource['barang']->count(),
                'total_barang_with_limits' => collect($this->resource['barangLimits'])->filter()->count(),
                'total_user_stock_value' => $this->calculateTotalStockValue(),
                'monthly_limit_status' => $this->getMonthlyLimitStatus(),
            ],
        ];
    }

    /**
     * Calculate total value of user's current stock
     */
    private function calculateTotalStockValue(): float
    {
        $total = 0;
        foreach ($this->resource['barang'] as $barang) {
            $userStock = $this->resource['userStock'][$barang->id_barang] ?? 0;
            $total += $userStock * $barang->harga_satuan;
        }
        return $total;
    }

    /**
     * Get monthly limit status
     */
    private function getMonthlyLimitStatus(): array
    {
        $used = $this->resource['monthlyUsed'];
        $limit = $this->resource['monthlyLimit'];
        
        if ($limit <= 0) {
            return [
                'status' => 'unlimited',
                'message' => 'Tidak ada batasan bulanan',
                'color' => 'gray',
            ];
        }
        
        $percentage = ($used / $limit) * 100;
        
        if ($percentage >= 100) {
            return [
                'status' => 'exceeded',
                'message' => 'Batas bulanan tercapai',
                'color' => 'red',
            ];
        } elseif ($percentage >= 80) {
            return [
                'status' => 'warning',
                'message' => 'Mendekati batas bulanan',
                'color' => 'yellow',
            ];
        } elseif ($percentage >= 50) {
            return [
                'status' => 'moderate',
                'message' => 'Penggunaan normal',
                'color' => 'blue',
            ];
        } else {
            return [
                'status' => 'low',
                'message' => 'Masih banyak sisa kuota',
                'color' => 'green',
            ];
        }
    }
}
