<?php

namespace App\Providers;

use App\Services\ApiHealthRegistry;
use App\Services\BpjsChecker;
use App\Services\GenericApiChecker;
use App\Services\SatuSehatChecker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
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

    public function boot(): void
    {
        View::composer(['layouts.app', 'layouts.kuma'], function ($view) {
            $ipInfo = Cache::remember('server_ip_info', 300, function () {
                try {
                    $res = Http::timeout(5)->get('http://ip-api.com/json?fields=status,query,isp,country,city');
                    if ($res->ok() && $res->json('status') === 'success') {
                        return ['ip' => $res->json('query'), 'isp' => $res->json('isp'), 'city' => $res->json('city'), 'country' => $res->json('country'), 'error' => null];
                    }
                } catch (\Throwable) {}
                return ['ip' => null, 'isp' => null, 'city' => null, 'country' => null, 'error' => 'unavailable'];
            });
            $view->with('serverIpInfo', $ipInfo);
        });
    }
}
