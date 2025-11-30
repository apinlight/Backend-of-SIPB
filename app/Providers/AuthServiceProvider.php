<?php

// app/Providers/AuthServiceProvider.php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Barang::class => \App\Policies\BarangPolicy::class,
        \App\Models\JenisBarang::class => \App\Policies\JenisBarangPolicy::class,
        \App\Models\Cabang::class => \App\Policies\CabangPolicy::class,
        \App\Models\BatasBarang::class => \App\Policies\BatasBarangPolicy::class,
        \App\Models\Pengajuan::class => \App\Policies\PengajuanPolicy::class,
        \App\Models\Gudang::class => \App\Policies\GudangPolicy::class,
        \App\Models\GlobalSetting::class => \App\Policies\GlobalSettingPolicy::class,
        \App\Models\PenggunaanBarang::class => \App\Policies\PenggunaanBarangPolicy::class,
        \App\Models\DetailPengajuan::class => \App\Policies\DetailPengajuanPolicy::class,

    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // ✅ ADD: Additional gate definitions for complex permissions
        Gate::define('manage-system', function ($user) {
            return $user->hasRole(\App\Enums\Role::ADMIN);
        });

        Gate::define('view-reports', function ($user) {
            return $user->hasAnyRole([\App\Enums\Role::ADMIN, \App\Enums\Role::MANAGER]);
        });

        Gate::define('export-data', function ($user) {
            return $user->hasAnyRole([\App\Enums\Role::ADMIN, \App\Enums\Role::MANAGER]);
        });

        Gate::define('approve-pengajuan', function ($user) {
            return $user->hasRole(\App\Enums\Role::ADMIN);
        });

        Gate::define('view-branch-data', function ($user, $branchName = null) {
            if ($user->hasRole(\App\Enums\Role::ADMIN)) {
                return true;
            }

            if ($user->hasRole(\App\Enums\Role::MANAGER)) {
                return $branchName ? $user->cabang?->nama_cabang === $branchName : true;
            }

            return false;
        });

        Gate::define('view-own-data', function ($user, $uniqueId = null) {
            if ($user->hasRole(\App\Enums\Role::ADMIN)) {
                return true;
            }

            return $uniqueId ? $user->unique_id === $uniqueId : true;
        });
    }
}
