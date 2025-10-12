<?php

// app/Policies/GlobalSettingPolicy.php

namespace App\Policies;

use App\Models\GlobalSetting;
use App\Models\User;

class GlobalSettingPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function view(User $user, GlobalSetting $globalSetting)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function create(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function update(User $user, GlobalSetting $globalSetting)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function delete(User $user, GlobalSetting $globalSetting)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    // ✅ ADD: Specific setting policies
    public function updateSystemSettings(User $user)
    {
        return $user->hasRole(\App\Enums\Role::ADMIN);
    }

    public function viewPublicSettings(User $user)
    {
        // Some settings might be viewable by all users (like limits)
        return $user->hasAnyRole([\App\Enums\Role::ADMIN, \App\Enums\Role::MANAGER, \App\Enums\Role::USER]);
    }
}
