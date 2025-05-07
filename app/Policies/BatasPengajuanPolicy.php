<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BatasPengajuan;

class BatasPengajuanPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, BatasPengajuan $batasPengajuan)
    {
        return $user->hasRole('admin');
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, BatasPengajuan $batasPengajuan)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatasPengajuan $batasPengajuan)
    {
        return $user->hasRole('admin');
    }
}
