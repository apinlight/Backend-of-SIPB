<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user)
    {
        // Allow admin and manager to view users list
        return $user->hasAnyRole(['admin', 'manager']);
    }

    public function view(User $user, User $targetUser)
    {
        // Admin can view anyone, manager can view users in same branch, users can view themselves
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

    public function update(User $user, User $targetUser)
    {
        return $user->hasRole('admin') || $user->unique_id === $targetUser->unique_id;
    }

    public function delete(User $user, User $targetUser)
    {
        return $user->hasRole('admin') && $user->unique_id !== $targetUser->unique_id;
    }
}