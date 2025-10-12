<?php

// app/Policies/BarangPolicy.php

namespace App\Policies;

use App\Models\Barang;
use App\Models\User;

class BarangPolicy
{
    public function viewAny(User $user)
    {
        // ✅ FIX: All users can view barang (for pengajuan creation)
        return $user->hasAnyRole([\App\Enums\Role::ADMIN, \App\Enums\Role::MANAGER, \App\Enums\Role::USER]);
    }

    public function view(User $user, Barang $barang)
    {
        // ✅ FIX: All users can view individual barang
        return $user->hasAnyRole([\App\Enums\Role::ADMIN, \App\Enums\Role::MANAGER, \App\Enums\Role::USER]);
    }

    public function create(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function update(User $user, Barang $barang)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function delete(User $user, Barang $barang)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    // ✅ ADD: Manage policy for admin operations
    public function manage(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }
}
