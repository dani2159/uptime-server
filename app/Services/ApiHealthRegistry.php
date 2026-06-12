<?php

namespace App\Services;

use App\Contracts\ApiHealthChecker;

class ApiHealthRegistry
{
    /** @var ApiHealthChecker[] */
    private array $checkers = [];

    public function register(ApiHealthChecker $checker): static
    {
        $this->checkers[$checker->getServiceKey()] = $checker;
        return $this;
    }

    public function get(string $key): ?ApiHealthChecker
    {
        return $this->checkers[$key] ?? null;
    }

    /** @return ApiHealthChecker[] */
    public function all(): array
    {
        return $this->checkers;
    }

    public function checkAll(): array
    {
        $results = [];

        foreach ($this->checkers as $key => $checker) {
            $results[$key] = [
                'label'   => $checker->getServiceLabel(),
                'results' => $checker->checkAll(),
            ];
        }

        return $results;
    }

    public function getCachedAll(): array
    {
        $results = [];

        foreach ($this->checkers as $key => $checker) {
            $results[$key] = [
                'label'   => $checker->getServiceLabel(),
                'results' => $checker->getCachedResults(),
            ];
        }

        return $results;
    }
}
