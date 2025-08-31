<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // The service layer should pre-calculate permissions for the current user
        $currentUser = $request->user();

        return [
            'id_pengajuan'      => $this->id_pengajuan,
            'unique_id'         => $this->unique_id,
            'status_pengajuan'  => $this->status_pengajuan,
            'tipe_pengajuan'    => $this->tipe_pengajuan,
            'keterangan'        => $this->keterangan,
            'bukti_file_url'    => $this->bukti_file_url, // Uses the model's accessor
            
            'approval_notes'    => $this->approval_notes,
            'rejection_reason'  => $this->rejection_reason,
            
            'created_at'        => $this->created_at?->toISOString(),
            'approved_at'       => $this->approved_at?->toISOString(),
            'rejected_at'       => $this->rejected_at?->toISOString(),
            
            // Relationships
            'user'              => UserResource::make($this->whenLoaded('user')),
            'approver'          => UserResource::make($this->whenLoaded('approver')),
            'rejector'          => UserResource::make($this->whenLoaded('rejector')),
            'details'           => DetailPengajuanResource::collection($this->whenLoaded('details')),

            // Explicit permissions object for the UI
            'permissions' => [
                'can_update' => $currentUser ? $currentUser->can('update', $this->resource) : false,
                'can_delete' => $currentUser ? $currentUser->can('delete', $this->resource) : false,
            ]
        ];
    }
}