<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PenggunaanBarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentUser = $request->user();

        return [
            'id_penggunaan'     => $this->id_penggunaan,
            'jumlah_digunakan'  => (int) $this->jumlah_digunakan,
            'keperluan'         => $this->keperluan,
            'keterangan'        => $this->keterangan,
            'status'            => $this->status,
            'tanggal_penggunaan'=> $this->tanggal_penggunaan?->format('Y-m-d'),

            // Computed field - this is a simple calculation, acceptable in a resource
            'total_nilai'       => $this->whenLoaded('barang', fn() => ($this->barang->harga_barang ?? 0) * $this->jumlah_digunakan),
            
            // Timestamps
            'created_at'        => $this->created_at?->toISOString(),
            'approved_at'       => $this->approved_at?->toISOString(),
            
            // Relationships
            'user'              => UserResource::make($this->whenLoaded('user')),
            'barang'            => BarangResource::make($this->whenLoaded('barang')),
            'approver'          => UserResource::make($this->whenLoaded('approver')),

            // Explicit permissions object for the UI
            'permissions' => [
                'can_update' => $currentUser ? $currentUser->can('update', $this->resource) : false,
                'can_delete' => $currentUser ? $currentUser->can('delete', $this->resource) : false,
            ]
        ];
    }
}