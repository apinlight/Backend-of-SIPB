<?php
// app/Http/Resources/PenggunaanBarangResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenggunaanBarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_penggunaan' => $this->id_penggunaan,
            'unique_id' => $this->unique_id,
            'id_barang' => $this->id_barang,
            'jumlah_digunakan' => $this->jumlah_digunakan,
            'keperluan' => $this->keperluan,
            'tanggal_penggunaan' => $this->tanggal_penggunaan?->format('Y-m-d'),
            'keterangan' => $this->keterangan,
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            
            // ✅ Relationships
            'user' => $this->whenLoaded('user', [
                'unique_id' => $this->user?->unique_id,
                'username' => $this->user?->username,
                'branch_name' => $this->user?->branch_name,
                'email' => $this->user?->email,
            ]),
            
            'barang' => $this->whenLoaded('barang', [
                'id_barang' => $this->barang?->id_barang,
                'nama_barang' => $this->barang?->nama_barang,
                'harga_barang' => $this->barang?->harga_barang,
                'jenis_barang' => $this->whenLoaded('barang.jenisBarang', [
                    'id_jenis_barang' => $this->barang?->jenisBarang?->id_jenis_barang,
                    'nama_jenis_barang' => $this->barang?->jenisBarang?->nama_jenis_barang,
                ]),
            ]),
            
            'approver' => $this->whenLoaded('approver', [
                'unique_id' => $this->approver?->unique_id,
                'username' => $this->approver?->username,
                'branch_name' => $this->approver?->branch_name,
            ]),
            
            // ✅ Computed fields
            'total_nilai' => $this->barang ? ($this->barang->harga_barang * $this->jumlah_digunakan) : 0,
            'status_label' => $this->getStatusLabel(),
            'formatted_tanggal' => $this->tanggal_penggunaan?->format('d M Y'),
            'can_be_updated' => $this->canBeUpdated(),
            'can_be_deleted' => $this->canBeDeleted(),
        ];
    }

    private function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default => 'Status Tidak Dikenal'
        };
    }

    private function canBeUpdated(): bool
    {
        return $this->status === 'pending';
    }

    private function canBeDeleted(): bool
    {
        return in_array($this->status, ['pending', 'rejected']);
    }
}
