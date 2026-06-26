<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AutoCloseIncidents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature   = 'monitor:auto-close-incidents';
    protected $description = 'Auto-close incidents for monitors that have been UP for the configured duration';

    public function handle(): int
    {
        $minutes = (int) \App\Models\AppSetting::get('incident_auto_close_minutes', '0');
        if ($minutes <= 0) return self::SUCCESS;

        $incidents = \App\Models\Incident::open()
            ->with('monitor')
            ->get();

        $closed = 0;
        foreach ($incidents as $incident) {
            $monitor = $incident->monitor;
            if (!$monitor || $monitor->last_status !== 'up') continue;

            // Check if monitor has been UP for $minutes consecutive
            $upSince = \App\Models\MonitorLog::where('monitor_id', $monitor->id)
                ->where('checked_at', '>=', now()->subMinutes($minutes))
                ->min('checked_at');

            $allUp = \App\Models\MonitorLog::where('monitor_id', $monitor->id)
                ->where('checked_at', '>=', now()->subMinutes($minutes))
                ->where('status', '!=', 'up')
                ->doesntExist();

            if ($allUp) {
                $resolvedAt = now();
                $incident->update([
                    'resolved_at'      => $resolvedAt,
                    'status'           => 'closed',
                    'duration_seconds' => $incident->started_at->diffInSeconds($resolvedAt),
                ]);
                \App\Services\AuditService::log('incident.auto_closed',
                    "Insiden #{$incident->id} auto-closed setelah {$minutes} menit UP", $monitor);
                $closed++;
            }
        }

        $this->info("Auto-closed {$closed} incident(s).");
        return self::SUCCESS;
    }
}
