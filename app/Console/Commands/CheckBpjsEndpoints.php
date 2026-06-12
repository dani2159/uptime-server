<?php

namespace App\Console\Commands;

use App\Services\ApiHealthRegistry;
use Illuminate\Console\Command;

class CheckBpjsEndpoints extends Command
{
    protected $signature = 'api:health-check {--service= : Check specific service key}';
    protected $description = 'Run connectivity checks for all registered API health services';

    public function __construct(private ApiHealthRegistry $registry)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($key = $this->option('service')) {
            $checker = $this->registry->get($key);

            if (!$checker) {
                $this->error("Service '{$key}' not found.");
                return self::FAILURE;
            }

            $this->info("Checking service: {$checker->getServiceLabel()}");
            foreach ($checker->checkAll() as $result) {
                $this->printResult($result);
            }
            return self::SUCCESS;
        }

        $this->info('Checking all API health services...');

        foreach ($this->registry->all() as $checker) {
            $this->line("\n  <fg=cyan>[{$checker->getServiceLabel()}]</>");
            foreach ($checker->checkAll() as $result) {
                $this->printResult($result);
            }
        }

        return self::SUCCESS;
    }

    private function printResult(array $result): void
    {
        $icon   = $result['connected'] ? '<fg=green>✓</>' : '<fg=red>✗</>';
        $status = $result['connected'] ? 'Terhubung' : 'Gagal';
        $this->line("    {$icon} {$result['label']} — {$status} ({$result['ms']}ms)");
    }
}
