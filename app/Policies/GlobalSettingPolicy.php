<?php
// app/Policies/GlobalSettingPolicy.php
namespace App\Policies;

use App\Models\User;
use App\Models\GlobalSetting;

class GlobalSettingPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole('admin');
    }

    public function view(User $user, GlobalSetting $globalSetting)
    {
        return $user->hasRole('admin');
    }

    public function create(User $user)
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, GlobalSetting $globalSetting)
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, GlobalSetting $globalSetting)
    {
        return $user->hasRole('admin');
    }

    // ✅ ADD: Specific setting policies
    public function updateSystemSettings(User $user)
    {
        return $user->hasRole('admin');
    }

    public function viewPublicSettings(User $user)
    {
        // Some settings might be viewable by all users (like limits)
        return $user->hasAnyRole(['admin', 'manager', 'user']);
    }
}
