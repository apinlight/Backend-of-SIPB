<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Pengajuan;
use App\Models\Gudang;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\BatasBarang;
use App\Models\BatasPengajuan;

use App\Policies\UserPolicy;
use App\Policies\PengajuanPolicy;
use App\Policies\GudangPolicy;
use App\Policies\BarangPolicy;
use App\Policies\JenisBarangPolicy;
use App\Policies\BatasBarangPolicy;
use App\Policies\BatasPengajuanPolicy;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Pengajuan::class => PengajuanPolicy::class,
        Gudang::class => GudangPolicy::class,
        Barang::class => BarangPolicy::class,
        JenisBarang::class => JenisBarangPolicy::class,
        BatasBarang::class => BatasBarangPolicy::class,
        BatasPengajuan::class => BatasPengajuanPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}