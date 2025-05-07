<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BatasBarang;

class BatasBarangPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, BatasBarang $batasBarang)
    {
        return $user->hasRole('admin');
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, BatasBarang $batasBarang)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, BatasBarang $batasBarang)
    {
        return $user->hasRole('admin');
    }
}