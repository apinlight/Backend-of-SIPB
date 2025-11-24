<?php

namespace App\Policies;

use App\Models\Cabang;
use App\Models\User;

class CabangPolicy
{
    /**
     * Determine if the user can view any cabang.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view cabang list
        return true;
    }

    /**
     * Determine if the user can view the cabang.
     */
    public function view(User $user, Cabang $cabang): bool
    {
        // All authenticated users can view cabang details
        return true;
    }

    /**
     * Determine if the user can create cabang.
     */
    public function create(User $user): bool
    {
        // Only admin can create cabang
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can update the cabang.
     */
    public function update(User $user, Cabang $cabang): bool
    {
        // Only admin can update cabang
        return $user->hasRole('admin');
    }

    /**
     * Determine if the user can delete the cabang.
     */
    public function delete(User $user, Cabang $cabang): bool
    {
        // Only admin can delete cabang
        return $user->hasRole('admin');
    }
}
