<?php
// app/Providers/AuthServiceProvider.php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // ✅ UPDATE: Register all policies
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        \App\Models\Barang::class => \App\Policies\BarangPolicy::class,
        \App\Models\JenisBarang::class => \App\Policies\JenisBarangPolicy::class,
        \App\Models\BatasBarang::class => \App\Policies\BatasBarangPolicy::class,
        \App\Models\Pengajuan::class => \App\Policies\PengajuanPolicy::class,
        \App\Models\Gudang::class => \App\Policies\GudangPolicy::class,
        \App\Models\GlobalSetting::class => \App\Policies\GlobalSettingPolicy::class, // ✅ NEW
        // ❌ REMOVE: \App\Models\BatasPengajuan::class => \App\Policies\BatasPengajuanPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        
        // ✅ ADD: Additional gate definitions for complex permissions
        Gate::define('manage-system', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('view-reports', function ($user) {
            return $user->hasAnyRole(['admin', 'manager']);
        });

        Gate::define('export-data', function ($user) {
            return $user->hasAnyRole(['admin', 'manager']);
        });

        Gate::define('approve-pengajuan', function ($user) {
            return $user->hasRole('admin');
        });

        // ✅ ADD: Branch-specific gates
        Gate::define('view-branch-data', function ($user, $branchName = null) {
            if ($user->hasRole('admin')) {
                return true;
            }
            
            if ($user->hasRole('manager')) {
                return $branchName ? $user->branch_name === $branchName : true;
            }
            
            return false;
        });

        Gate::define('view-own-data', function ($user, $uniqueId = null) {
            if ($user->hasRole('admin')) {
                return true;
            }
            
            return $uniqueId ? $user->unique_id === $uniqueId : true;
        });
    }
}
