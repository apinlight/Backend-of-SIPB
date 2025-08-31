<?php
// app/Policies/UserPolicy.php
namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $user, User $targetUser)
    {
        if ($user->hasRole('admin')) {
            return true; // Admin can view anyone
        }
        
        if ($user->hasRole('manager')) {
            // Manager can view users in same branch
            return $user->branch_name === $targetUser->branch_name;
        }
        
        // Users can view themselves
        return $user->unique_id === $targetUser->unique_id;
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, User $targetUser)
    {
        if ($user->hasRole('admin')) {
            return true; // Admin can update anyone
        }
        
        // Users can update themselves (profile info)
        return $user->unique_id === $targetUser->unique_id;
    }

    public function delete(User $user, User $targetUser)
    {
        // Admin can delete anyone except themselves
        return $user->hasRole('admin') && 
               $user->unique_id !== $targetUser->unique_id;
    }

    // ✅ ADD: Role management policies
    public function assignRole(User $user)
    {
        return $user->hasRole('admin');
    }

    public function removeRole(User $user)
    {
        return $user->hasRole('admin');
    }

    // ✅ ADD: Scope-based viewing policies
    public function viewBranchUsers(User $user)
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function viewAllUsers(User $user)
    {
        return $user->hasRole('admin');
    }
}
