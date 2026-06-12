<?php

namespace App\Providers;

use App\Services\ApiHealthRegistry;
use App\Services\BpjsChecker;
use App\Services\GenericApiChecker;
use App\Services\SatuSehatChecker;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApiHealthRegistry::class, function () {
            $registry = new ApiHealthRegistry();

            // BPJS — satu checker per service (vclaim, antrean_rs, dst)
            foreach (array_keys(config('bpjs.services', [])) as $serviceKey) {
                $registry->register(new BpjsChecker($serviceKey));
            }

            // Satu Sehat — OAuth2 Bearer
            $registry->register(new SatuSehatChecker());

            // Custom services (SIMRS, Sisrute, dll) — config-driven
            // Tambah service baru: cukup tambah entry di config/services_custom.php
            foreach (GenericApiChecker::allServices() as $serviceKey) {
                $registry->register(new GenericApiChecker($serviceKey));
            }

            return $registry;
        });
    }

    public function boot(): void {}
}
