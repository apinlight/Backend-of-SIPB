<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Pengajuan;

class PengajuanPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function view(User $user, Pengajuan $pengajuan)
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return $user->branch_name === $pengajuan->user->branch_name;
        }

        return $user->unique_id === $pengajuan->user_id;
    }

    public function create(User $user)
    {
        return $user->hasRole('user');
    }

    public function update(User $user, Pengajuan $pengajuan)
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('user')) {
            return $user->unique_id === $pengajuan->user_id && $pengajuan->status === 'pending';
        }

        return false;
    }

    public function delete(User $user, Pengajuan $pengajuan)
    {
        return $this->update($user, $pengajuan);
    }

    public function approve(User $user, Pengajuan $pengajuan)
    {
        return $user->hasRole('admin') && $pengajuan->status === 'pending';
    }

    public function decline(User $user, Pengajuan $pengajuan)
    {
        return $user->hasRole('admin') && $pengajuan->status === 'pending';
    }
}
