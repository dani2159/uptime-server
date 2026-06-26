<?php

namespace App\Console\Commands;

use App\Jobs\EscalateIncidentJob;
use App\Jobs\RecheckMonitorJob;
use App\Models\BusinessHour;
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

    private array $batchDown = [];
    private array $batchUp   = [];

    public function __construct(
        private UptimeChecker $checker,
        private DnsResolver $resolver,
        private NotificationService $notifier
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = Monitor::where('is_active', true)->whereNotIn('type', ['push', 'cron']);

        if ($id = $this->option('id')) {
            $query = Monitor::where('id', $id);
        } else {
            $query->where(function ($q) {
                $q->whereNull('last_checked_at')
                  ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_checked_at, NOW()) >= check_interval');
            });
        }

        $monitors = $query->get();
        if ($monitors->isEmpty()) { $this->info('No monitors due.'); return self::SUCCESS; }
        $this->info("Checking {$monitors->count()} monitor(s)...");

        foreach ($monitors as $monitor) {
            $previousStatus = $monitor->last_status;
            $result = $this->checker->check($monitor);
            $this->checker->saveResult($monitor, $result);
            $this->resolver->resolve($monitor);
            $monitor->refresh();
            $this->handleSlowAlert($monitor, $result);
            $this->handleLatencyTrend($monitor, $result);
            $this->handleRetryAndNotify($monitor, $previousStatus, $result['status']);
            $this->line("  [" . ($result['status'] === 'up' ? 'OK' : 'DN') . "] {$monitor->name} — {$result['response_time']}ms");
        }

        $this->notifier->sendBatchDown($this->batchDown);
        $this->notifier->sendBatchUp($this->batchUp);
        $this->checkCorrelatedIncident();
        return self::SUCCESS;
    }

    private function handleSlowAlert(Monitor $monitor, array $result): void
    {
        $isSlow  = $result['is_slow'] ?? false;
        $wasSlow = $monitor->last_is_slow;
        $inMaint = MaintenanceWindow::isMonitorInMaintenance($monitor);
        $monitor->update(['last_is_slow' => $isSlow]);
        if ($isSlow && !$wasSlow && !$inMaint) $this->notifier->notifySlow($monitor);
    }

    private function handleLatencyTrend(Monitor $monitor, array $result): void
    {
        if ($result['latency_trending'] ?? false) {
            AuditService::log('monitor.latency_trend',
                "Monitor \"{$monitor->name}\" latency terus naik ({$result['response_time']}ms)", $monitor);
        }
    }

    private function handleRetryAndNotify(Monitor $monitor, string $previousStatus, string $currentStatus): void
    {
        $inMaintenance = MaintenanceWindow::isMonitorInMaintenance($monitor);

        if ($currentStatus === 'up') {
            $wasConfirmedDown = $previousStatus === 'down' && $monitor->current_retries >= $monitor->retry_count;
            $monitor->update(['current_retries' => 0, 'flap_occurrences' => 0]);
            if ($wasConfirmedDown && !$inMaintenance) {
                $monitor->update(['flap_first_at' => null]);
                $this->closeIncident($monitor);
                $this->batchUp[] = $monitor;
            }
            return;
        }

        $newRetries = $monitor->current_retries + 1;
        $monitor->update(['current_retries' => $newRetries]);

        if ($newRetries >= $monitor->retry_count && !$inMaintenance) {
            if ($monitor->isDependencyDown()) {
                $this->line("  >> [{$monitor->name}] dependency down — skip");
                return;
            }
            if ($monitor->flap_detection && $this->isFlapDetected($monitor)) {
                $this->line("  >> [{$monitor->name}] flap detected — suppress");
                AuditService::log('monitor.flap', "Monitor \"{$monitor->name}\" flap terdeteksi", $monitor);
                return;
            }
            $bhOnly = \App\Models\AppSetting::get('notif_business_hours_only', '0');
            if ($bhOnly === '1' && !BusinessHour::isBusinessHours()) {
                $this->line("  >> [{$monitor->name}] di luar jam kerja — skip");
            } else {
                $this->openIncident($monitor);
                $this->batchDown[] = $monitor;
            }
        } elseif ($newRetries < $monitor->retry_count) {
            RecheckMonitorJob::dispatch($monitor->id)->delay(now()->addSeconds(20));
        }
    }

    private function isFlapDetected(Monitor $monitor): bool
    {
        $windowMin = $monitor->flap_window_minutes ?: 5;
        $threshold = $monitor->flap_count_threshold ?: 3;
        if (!$monitor->flap_first_at) {
            $monitor->update(['flap_first_at' => now(), 'flap_occurrences' => 1]);
            return false;
        }
        if ($monitor->flap_first_at->diffInMinutes(now()) > $windowMin) {
            $monitor->update(['flap_first_at' => now(), 'flap_occurrences' => 1]);
            return false;
        }
        $occ = $monitor->flap_occurrences + 1;
        $monitor->update(['flap_occurrences' => $occ]);
        return $occ >= $threshold;
    }

    private function checkCorrelatedIncident(): void
    {
        $downCount = count($this->batchDown);
        $threshold = (int) \App\Models\AppSetting::get('correlated_incident_threshold', '5');
        if ($downCount < $threshold) return;
        $names = collect($this->batchDown)->pluck('name')->implode(', ');
        AuditService::log('incident.major', "MAJOR INCIDENT: {$downCount} monitor DOWN serentak — {$names}");
        $this->notifier->notifyMajorIncident($this->batchDown);
    }

    private function openIncident(Monitor $monitor): void
    {
        if (Incident::open()->where('monitor_id', $monitor->id)->exists()) return;
        Incident::create([
            'monitor_id' => $monitor->id,
            'category'   => 'monitor_downtime',
            'started_at' => $monitor->last_down_at ?? now(),
            'status'     => 'open',
        ]);
        AuditService::log('monitor.down', "Monitor \"{$monitor->name}\" DOWN — insiden dibuka", $monitor, null);
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
        if (!$incident) return;
        $resolvedAt = now();
        $incident->update([
            'resolved_at'      => $resolvedAt,
            'status'           => 'closed',
            'duration_seconds' => $incident->started_at->diffInSeconds($resolvedAt),
        ]);
        AuditService::log('monitor.recovered', "Monitor \"{$monitor->name}\" UP kembali — insiden ditutup", $monitor, null);
    }
}
