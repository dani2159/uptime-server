<?php

namespace App\Services;

class GenericApiChecker extends AbstractApiChecker
{
    private string $serviceKey;
    private array  $config;

    public function __construct(string $serviceKey)
    {
        parent::__construct();
        $this->serviceKey = $serviceKey;
        $this->config     = config("services_custom.{$serviceKey}", []);
        $this->timeout    = $this->config['timeout'] ?? 15;
    }

    public function getServiceKey(): string
    {
        return "custom_{$this->serviceKey}";
    }

    public function getServiceLabel(): string
    {
        return $this->config['label'] ?? ucfirst($this->serviceKey);
    }

    protected function getBaseUrl(): string
    {
        return $this->config['base_url'] ?? '';
    }

    protected function getEndpoints(): array
    {
        return $this->config['endpoints'] ?? [];
    }

    public static function allServices(): array
    {
        return array_keys(config('services_custom', []));
    }
}
