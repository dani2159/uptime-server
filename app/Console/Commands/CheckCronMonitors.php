<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckCronMonitors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'monitor:check-cron';
    protected $description = 'Check cron/push monitors for missed heartbeats';

    public function handle(): int
    {
        $monitors = \App\Models\Monitor::where('is_active', true)->whereIn('type', ['cron', 'push'])->get();
        foreach ($monitors as $monitor) {
            $checker = app(\App\Services\UptimeChecker::class);
            $result  = $checker->check($monitor);
            $prev    = $monitor->last_status;
            $checker->saveResult($monitor, $result);
            $current = $result['status'];
            if ($current === 'down' && $prev !== 'down') {
                app(\App\Services\NotificationService::class)->notifyDown($monitor);
                if (!\App\Models\Incident::open()->where('monitor_id', $monitor->id)->exists()) {
                    \App\Models\Incident::create(['monitor_id' => $monitor->id, 'category' => 'monitor_downtime', 'started_at' => now(), 'status' => 'open']);
                }
            } elseif ($current === 'up' && $prev === 'down') {
                app(\App\Services\NotificationService::class)->notifyRecovered($monitor);
            }
            $this->line("  [{$current}] {$monitor->name}");
        }
        return self::SUCCESS;
    }
}
