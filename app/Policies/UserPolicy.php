<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, User $targetUser)
    {
        return $user->hasRole('admin') || $user->unique_id === $targetUser->unique_id;
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