<?php

$providers = [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
];

// Register Telescope only when the package is installed and we're in a safe environment
if (
    class_exists('Laravel\\Telescope\\TelescopeApplicationServiceProvider') &&
    (env('APP_ENV') === 'local' || env('APP_DEBUG', false))
) {
    $providers[] = App\Providers\TelescopeServiceProvider::class;
}

return $providers;
