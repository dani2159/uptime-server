<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckDomainExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'monitor:check-domain-expiry';
    protected $description = 'Check WHOIS domain expiry for whois-type monitors and send alerts';

    public function handle(): int
    {
        $monitors = \App\Models\Monitor::where('is_active', true)->where('type', 'whois')->get();
        foreach ($monitors as $monitor) {
            $checker = app(\App\Services\UptimeChecker::class);
            $result  = $checker->check($monitor);
            $checker->saveResult($monitor, $result);
            $this->line("  [{$result['status']}] {$monitor->name} — {$result['message']}");
        }
        return self::SUCCESS;
    }
}
