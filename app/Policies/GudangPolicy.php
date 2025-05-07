<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Barang;
use App\Models\Gudang;

class GudangPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function view(User $user, User $targetUser)
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('manager')) {
            return $user->branch_name === $targetUser->branch_name;
        }

        return $user->unique_id === $targetUser->unique_id;
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Gudang $gudang)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Gudang $gudang)
    {
        return $user->hasRole('admin');
    }
}