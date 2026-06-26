<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunAlertTestSimulator extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'monitor:simulate {monitor_id} {event=down : down|up|slow}';
    protected $description = 'Simulate a DOWN/UP/SLOW alert for testing notifications without affecting a real server';

    public function handle(): int
    {
        $monitor = \App\Models\Monitor::findOrFail($this->argument('monitor_id'));
        $event   = $this->argument('event');
        $notifier = app(\App\Services\NotificationService::class);

        match ($event) {
            'down' => (function() use ($monitor, $notifier) {
                $this->info("Simulating DOWN for {$monitor->name}...");
                $notifier->notifyDown($monitor);
            })(),
            'up' => (function() use ($monitor, $notifier) {
                $this->info("Simulating UP/recovered for {$monitor->name}...");
                $notifier->notifyRecovered($monitor);
            })(),
            'slow' => (function() use ($monitor, $notifier) {
                $this->info("Simulating SLOW for {$monitor->name}...");
                $notifier->notifySlow($monitor);
            })(),
            default => $this->error("Unknown event: {$event}. Use: down|up|slow"),
        };

        \App\Services\AuditService::log('monitor.simulate',
            "Alert simulator: event={$event} pada \"{$monitor->name}\"", $monitor);
        $this->info('Done. Check your notification channels.');
        return self::SUCCESS;
    }
}
