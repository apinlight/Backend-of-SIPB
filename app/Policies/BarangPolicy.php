<?php
// app/Policies/BarangPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\Barang;

class BarangPolicy
{
    public function viewAny(User $user)
    {
        // ✅ FIX: All users can view barang (for pengajuan creation)
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function view(User $user, Barang $barang)
    {
        // ✅ FIX: All users can view individual barang
        return $user->hasAnyRole(['admin', 'manager', 'user']);
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

    // ✅ ADD: Manage policy for admin operations
    public function manage(User $user)
    {
        return $user->hasRole('admin');
    }
}
