<?php

namespace App\Policies;

use App\Models\DetailPengajuan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DetailPengajuanPolicy
{
    /**
     * The core of this policy: a user can manage details if they can update the parent pengajuan.
     */
    private function canUpdateParent(User $user, DetailPengajuan $detailPengajuan): bool
    {
        // We use the existing PengajuanPolicy to check this.
        return $user->can('update', $detailPengajuan->pengajuan);
    }

    public function viewAny(User $user): bool
    {
        return true; // The controller query scope will handle filtering
    }

    public function view(User $user, DetailPengajuan $detailPengajuan): bool
    {
        return $user->can('view', $detailPengajuan->pengajuan);
    }

    public function create(User $user): bool
    {
        // This is checked more specifically in the Form Request based on the Pengajuan ID.
        return true;
    }

    public function update(User $user, DetailPengajuan $detailPengajuan): bool
    {
        return $this->canUpdateParent($user, $detailPengajuan);
    }

    public function delete(User $user, DetailPengajuan $detailPengajuan): bool
    {
        return $this->canUpdateParent($user, $detailPengajuan);
    }
}