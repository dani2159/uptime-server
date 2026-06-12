<?php

namespace App\Services;

class SatuSehatChecker extends AbstractApiChecker
{
    public function __construct()
    {
        parent::__construct();
        $this->timeout = (int) config('satusehat.timeout', 15);
    }

    public function getServiceKey(): string
    {
        return 'satusehat';
    }

    public function getServiceLabel(): string
    {
        return 'Satu Sehat';
    }

    protected function getBaseUrl(): string
    {
        return config('satusehat.base_url', '');
    }

    protected function getEndpoints(): array
    {
        return config('satusehat.endpoints', []);
    }
}
