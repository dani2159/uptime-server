<?php

namespace App\Services;

use App\Contracts\ApiHealthChecker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractApiChecker implements ApiHealthChecker
{
    protected int $timeout;

    public function __construct()
    {
        $this->timeout = 15;
    }

    abstract protected function getEndpoints(): array;

    abstract protected function getBaseUrl(): string;

    /**
     * Override jika endpoint butuh header khusus (misal Content-Type tertentu).
     * Tidak ada auth — project ini hanya cek konektivitas, bukan integrasi.
     */
    protected function buildHeaders(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    public function checkAll(): array
    {
        $results = [];

        foreach ($this->getEndpoints() as $endpoint) {
            $results[$endpoint['key']] = $this->checkEndpoint($endpoint);
        }

        Cache::put("api_health_{$this->getServiceKey()}", $results, now()->addMinutes(10));

        return $results;
    }

    public function getCachedResults(): array
    {
        return Cache::get("api_health_{$this->getServiceKey()}", []);
    }

    public function checkEndpoint(array $endpoint): array
    {
        $start = microtime(true);

        try {
            $response = Http::withHeaders($this->buildHeaders())
                ->timeout($this->timeout)
                ->withoutVerifying()
                ->send($endpoint['method'], $this->getBaseUrl() . $endpoint['path']);

            $ms = (int) ((microtime(true) - $start) * 1000);

            // 401/403 = server UP tapi butuh auth → masih "terhubung"
            // 5xx = server error → "gagal"
            $connected = $response->status() < 500;

            return $this->buildResult($endpoint, $connected, $ms, $response->status());
        } catch (\Throwable $e) {
            $ms = (int) ((microtime(true) - $start) * 1000);
            Log::debug("API health [{$this->getServiceKey()}.{$endpoint['key']}]: {$e->getMessage()}");

            return $this->buildResult($endpoint, false, $ms, null, $e->getMessage());
        }
    }

    protected function buildResult(array $endpoint, bool $connected, int $ms, ?int $httpStatus, ?string $error = null): array
    {
        return [
            'service'     => $this->getServiceKey(),
            'key'         => $endpoint['key'],
            'label'       => $endpoint['label'],
            'connected'   => $connected,
            'ms'          => $ms,
            'http_status' => $httpStatus,
            'error'       => $error,
            'checked_at'  => now()->format('d-m-Y H:i:s'),
        ];
    }
}
