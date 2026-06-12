<?php

namespace App\Contracts;

interface ApiHealthChecker
{
    public function getServiceKey(): string;

    public function getServiceLabel(): string;

    public function checkAll(): array;

    public function checkEndpoint(array $endpoint): array;

    public function getCachedResults(): array;
}
