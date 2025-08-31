<?php
// app/Http/Resources/PengajuanResource.php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengajuanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id_pengajuan' => $this->id_pengajuan,
            'unique_id' => $this->unique_id,
            'status_pengajuan' => $this->status_pengajuan,
            'tipe_pengajuan' => $this->tipe_pengajuan,
            
            // ✅ ADD: Missing approval/rejection fields
            'keterangan' => $this->keterangan,
            'bukti_file' => $this->bukti_file,
            'bukti_file_url' => $this->when(
                $this->bukti_file, 
                $this->getBuktiFileUrl()
            ),
            
            // ✅ ADD: Approval information
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
            'approval_notes' => $this->approval_notes,
            
            // ✅ ADD: Rejection information  
            'rejected_by' => $this->rejected_by,
            'rejected_at' => $this->rejected_at,
            'rejection_reason' => $this->rejection_reason,
            
            // ✅ ADD: Calculated fields
            'total_items' => $this->whenLoaded('details', function() {
                return $this->details->count();
            }),
            'total_nilai' => $this->whenLoaded('details', function() {
                return $this->getTotalValue();
            }),
            
            // ✅ Relationships
            'user' => new UserResource($this->whenLoaded('user')),
            'details' => DetailPengajuanResource::collection($this->whenLoaded('details')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'rejector' => new UserResource($this->whenLoaded('rejector')),
            
            // ✅ ADD: Permissions for current user
            'can_be_approved' => $this->when(
                method_exists($this, 'canBeApproved'),
                $this->canBeApproved()
            ),
            'can_be_deleted' => $this->when(
                method_exists($this, 'canBeDeleted'),
                $this->canBeDeleted()
            ),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
