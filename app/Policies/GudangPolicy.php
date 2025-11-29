<?php

// app/Policies/GudangPolicy.php

namespace App\Policies;

use App\Models\Gudang;
use App\Models\User;

class GudangPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasAnyRole([\App\Enums\Role::ADMIN, \App\Enums\Role::MANAGER, \App\Enums\Role::USER]);
    }

    // ✅ FIX: Correct parameter type and logic
    public function view(User $user, Gudang $gudang)
    {
        if ($user->hasRole(\App\Enums\Role::ADMIN)) {
            return true; // Admin can view all gudang
        }
        if ($user->hasRole(\App\Enums\Role::MANAGER)) {
            // Manager can view gudang from same cabang
            return $user->id_cabang === $gudang->user->id_cabang;
        }

        // User can only view their own gudang
        return $user->unique_id === $gudang->unique_id;
    }

    // ✅ ADD: Scope-based viewing policies
    public function viewOwn(User $user)
    {
        return $user->hasAnyRole([\App\Enums\Role::ADMIN, \App\Enums\Role::MANAGER, \App\Enums\Role::USER]);
    }

    public function viewBranch(User $user)
    {
        return $user->hasAnyRole([\App\Enums\Role::ADMIN, \App\Enums\Role::MANAGER]);
    }

    public function viewAll(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function create(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function update(User $user, Gudang $gudang)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function delete(User $user, Gudang $gudang)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    // ✅ ADD: Manual entry policy
    public function createManual(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }
}
