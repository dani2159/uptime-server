<?php

namespace App\Console\Commands;

use App\Models\Monitor;
use App\Services\NotificationService;
use App\Services\SslChecker;
use Illuminate\Console\Command;

class CheckSslCertificates extends Command
{
    protected $signature   = 'monitor:ssl-check {--id= : Check specific monitor by ID}';
    protected $description = 'Check SSL certificate expiry for all HTTPS monitors';

    public function __construct(
        private SslChecker $checker,
        private NotificationService $notifier
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = Monitor::where('is_active', true)
            ->whereIn('type', ['http', 'keyword'])
            ->where('url', 'like', 'https://%');

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $monitors = $query->get();

        if ($monitors->isEmpty()) {
            $this->info('No HTTPS monitors to check.');
            return self::SUCCESS;
        }

        $this->info("Checking SSL for {$monitors->count()} monitor(s)...");

        foreach ($monitors as $monitor) {
            $result = $this->checker->check($monitor);

            if ($result === null) {
                continue;
            }

            $this->checker->saveResult($monitor, $result);
            $monitor->refresh();

            $days = $result['ssl_days_remaining'];
            $valid = $result['ssl_valid'] ? 'valid' : 'EXPIRED/INVALID';

            $this->line("  [{$monitor->name}] SSL {$valid} — {$days} hari tersisa");

            // Notif jika cert akan expire dalam 30 hari
            if ($days !== null && $days <= 30 && $days > 0) {
                $this->notifier->notifySslExpiry($monitor);
            }
        }

        return self::SUCCESS;
    }
}
