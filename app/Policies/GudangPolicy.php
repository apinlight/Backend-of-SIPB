<?php
// app/Policies/GudangPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\Gudang;

class GudangPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    // ✅ FIX: Correct parameter type and logic
    public function view(User $user, Gudang $gudang)
    {
        if ($user->hasRole('admin')) {
            return true; // Admin can view all gudang
        }
        
        if ($user->hasRole('manager')) {
            // Manager can view gudang from same branch
            return $user->branch_name === $gudang->user->branch_name;
        }
        
        // User can only view their own gudang
        return $user->unique_id === $gudang->unique_id;
    }

    // ✅ ADD: Scope-based viewing policies
    public function viewOwn(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }

    public function viewBranch(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function viewAll(User $user)
    {
        return $user->hasRole('admin');
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

    // ✅ ADD: Manual entry policy
    public function createManual(User $user)
    {
        return $user->hasRole('admin');
    }
}
