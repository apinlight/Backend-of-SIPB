<?php
// app/Policies/JenisBarangPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\JenisBarang;

class JenisBarangPolicy
{
    public function viewAny(User $user)
    {
        // ✅ FIX: All users can view jenis barang (for pengajuan creation)
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function view(User $user, JenisBarang $jenisBarang)
    {
        // ✅ FIX: All users can view individual jenis barang
        return $user->hasAnyRole(['admin', 'manager', 'user']);
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

    // ✅ ADD: Manage policy for admin operations
    public function manage(User $user)
    {
        return $user->hasRole('admin');
    }
}
