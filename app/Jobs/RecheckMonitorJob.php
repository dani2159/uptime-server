<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class RecheckMonitorJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(public readonly int $monitorId) {}

    public function handle(): void
    {
        Artisan::call('monitor:check', ['--id' => $this->monitorId]);
    }
}
