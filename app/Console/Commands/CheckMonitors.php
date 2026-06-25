<?php

namespace App\Console\Commands;

use App\Jobs\EscalateIncidentJob;
use App\Jobs\RecheckMonitorJob;
use App\Models\EscalationRule;
use App\Models\Incident;
use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use App\Services\AuditService;
use App\Services\DnsResolver;
use App\Services\NotificationService;
use App\Services\UptimeChecker;
use Illuminate\Console\Command;

class CheckMonitors extends Command
{
    protected $signature = 'monitor:check {--id= : Check specific monitor by ID}';
    protected $description = 'Run uptime checks for all active monitors';

    public function __construct(
        private UptimeChecker $checker,
        private DnsResolver $resolver,
        private NotificationService $notifier
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = Monitor::where('is_active', true);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        } else {
            $query->where(function ($q) {
                $q->whereNull('last_checked_at')
                  ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_checked_at, NOW()) >= check_interval');
            });
        }

        $monitors = $query->get();

        if ($monitors->isEmpty()) {
            $this->info('No monitors due for checking.');
            return self::SUCCESS;
        }

        $this->info("Checking {$monitors->count()} monitor(s)...");

        foreach ($monitors as $monitor) {
            $previousStatus = $monitor->last_status;

            $result = $this->checker->check($monitor);
            $this->checker->saveResult($monitor, $result);
            $this->resolver->resolve($monitor);

            $monitor->refresh();
            $this->handleSlowAlert($monitor, $result);
            $this->handleRetryAndNotify($monitor, $previousStatus, $result['status']);

            $icon = $result['status'] === 'up' ? '✓' : '✗';
            $this->line("  {$icon} [{$monitor->name}] {$result['status']} — {$result['response_time']}ms");
        }

        return self::SUCCESS;
    }

    private function handleSlowAlert(Monitor $monitor, array $result): void
    {
        $isSlow     = $result['is_slow'] ?? false;
        $wasSlow    = $monitor->last_is_slow;
        $inMaint    = MaintenanceWindow::isMonitorInMaintenance($monitor);

        $monitor->update(['last_is_slow' => $isSlow]);

        if ($isSlow && !$wasSlow && !$inMaint) {
            $this->notifier->notifySlow($monitor);
        }
    }

    private function handleRetryAndNotify(Monitor $monitor, string $previousStatus, string $currentStatus): void
    {
        $inMaintenance = MaintenanceWindow::isMonitorInMaintenance($monitor);

        if ($currentStatus === 'up') {
            // Recover: hanya notif jika sebelumnya benar-benar sudah DOWN (retries >= retry_count)
            $wasConfirmedDown = $previousStatus === 'down' && $monitor->current_retries >= $monitor->retry_count;
            $monitor->update(['current_retries' => 0]);

            if ($wasConfirmedDown && !$inMaintenance) {
                $this->notifier->notifyRecovered($monitor);
                $this->closeIncident($monitor);
            }
            return;
        }

        // Down: increment retries
        $newRetries = $monitor->current_retries + 1;
        $monitor->update(['current_retries' => $newRetries]);

        // Notif hanya saat pertama kali retries mencapai threshold (bukan setiap check)
        if ($newRetries >= $monitor->retry_count && !$inMaintenance) {
            $this->notifier->notifyDown($monitor);
            $this->openIncident($monitor);
        } elseif ($newRetries < $monitor->retry_count) {
            // Rapid recheck: cek ulang dalam 20 detik
            RecheckMonitorJob::dispatch($monitor->id)->delay(now()->addSeconds(20));
        }
    }

    private function openIncident(Monitor $monitor): void
    {
        if (Incident::open()->where('monitor_id', $monitor->id)->exists()) {
            return;
        }

        Incident::create([
            'monitor_id' => $monitor->id,
            'category'   => 'monitor_downtime',
            'started_at' => $monitor->last_down_at ?? now(),
            'status'     => 'open',
        ]);
        AuditService::log('monitor.down', "Monitor \"{$monitor->name}\" DOWN — insiden dibuka", $monitor, null);

        // Dispatch eskalasi untuk rules yang berlaku (global + per-monitor)
        $incident = Incident::open()->where('monitor_id', $monitor->id)->latest('started_at')->first();
        if ($incident) {
            $rules = EscalationRule::where('is_active', true)
                ->where(fn($q) => $q->whereNull('monitor_id')->orWhere('monitor_id', $monitor->id))
                ->get();
            foreach ($rules as $rule) {
                EscalateIncidentJob::dispatch($incident->id, $rule->id)
                    ->delay(now()->addMinutes($rule->delay_minutes));
            }
        }
    }

    private function closeIncident(Monitor $monitor): void
    {
        $incident = Incident::open()->where('monitor_id', $monitor->id)->latest('started_at')->first();

        if (!$incident) {
            return;
        }

        $resolvedAt = now();

        $incident->update([
            'resolved_at'       => $resolvedAt,
            'status'            => 'closed',
            'duration_seconds'  => $incident->started_at->diffInSeconds($resolvedAt),
        ]);
        AuditService::log('monitor.recovered', "Monitor \"{$monitor->name}\" UP kembali — insiden ditutup", $monitor, null);
    }
}
