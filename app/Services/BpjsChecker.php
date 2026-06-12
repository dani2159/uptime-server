<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class BpjsChecker extends AbstractApiChecker
{
    private string $serviceKey;
    private array  $serviceConfig;

    public function __construct(string $serviceKey = 'vclaim')
    {
        parent::__construct();
        $this->serviceKey    = $serviceKey;
        $this->serviceConfig = config("bpjs.services.{$serviceKey}", []);
        $this->timeout       = (int) config('bpjs.timeout', 15);
    }

    public function getServiceKey(): string
    {
        return "bpjs_{$this->serviceKey}";
    }

    public function getServiceLabel(): string
    {
        return $this->serviceConfig['label'] ?? ucfirst($this->serviceKey);
    }

    protected function getBaseUrl(): string
    {
        // PCare dan service dengan base_url eksplisit tidak mengikuti CDN switch
        if (isset($this->serviceConfig['base_url'])) {
            return $this->serviceConfig['base_url'];
        }

        $mode = self::activeMode();
        $host = config("bpjs.hosts.{$mode}", config('bpjs.hosts.non_cdn'));
        $path = $this->serviceConfig['path'] ?? '';

        return rtrim($host, '/') . $path;
    }

    protected function getEndpoints(): array
    {
        return $this->serviceConfig['endpoints'] ?? [];
    }

    public static function allServices(): array
    {
        return array_keys(config('bpjs.services', []));
    }

    public static function activeMode(): string
    {
        return Cache::get('bpjs_cdn_mode', config('bpjs.default_mode', 'non_cdn'));
    }

    public static function setMode(string $mode): void
    {
        if (!in_array($mode, ['cdn', 'non_cdn'])) {
            return;
        }

        Cache::forever('bpjs_cdn_mode', $mode);

        // Hapus cache hasil cek agar langsung re-check dengan URL baru
        foreach (self::allServices() as $serviceKey) {
            Cache::forget("api_health_bpjs_{$serviceKey}");
        }
    }
}
