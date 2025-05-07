<?php

namespace App\Policies;

use App\Models\User;
use App\Models\JenisBarang;

class JenisBarangPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, JenisBarang $jenisBarang)
    {
        return $user->hasRole('admin');
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, JenisBarang $jenisBarang)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, JenisBarang $jenisBarang)
    {
        return $user->hasRole('admin');
    }
}