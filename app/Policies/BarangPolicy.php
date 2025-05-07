<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Barang;

class BarangPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function view(User $user, Barang $barang)
    {
        return $user->hasRole(['admin', 'manager']);
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Barang $barang)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Barang $barang)
    {
        return $user->hasRole('admin');
    }
}