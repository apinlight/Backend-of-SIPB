<?php

namespace App\Policies;

use App\Models\BatasBarang;
use App\Models\User;

class BatasBarangPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function view(User $user, BatasBarang $batasBarang)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function create(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function update(User $user, BatasBarang $batasBarang)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function delete(User $user, BatasBarang $batasBarang)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }
}
