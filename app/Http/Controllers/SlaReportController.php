<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Http\Request;

class SlaReportController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->input('days', 30);
        $days = in_array($days, [7, 30, 90]) ? $days : 30;

        $periodStart   = now()->subDays($days);
        $periodSeconds = $days * 86400;

        // --- Panel 1: SLA per monitor (hanya monitor_downtime) ---
        $monitors = Monitor::orderBy('name')->get();

        $rows = $monitors->map(function (Monitor $monitor) use ($periodStart, $periodSeconds) {
            $incidents = Incident::where('monitor_id', $monitor->id)
                ->where('category', 'monitor_downtime')
                ->where('started_at', '>=', $periodStart)
                ->get();

            $closed = $incidents->where('status', 'closed');

            $downtimeSeconds = $closed->sum('duration_seconds')
                + $incidents->where('status', 'open')->sum(fn ($i) => $i->started_at->diffInSeconds(now()));

            $mttrSeconds = $closed->count() > 0 ? (int) $closed->avg('duration_seconds') : 0;

            $availability = $periodSeconds > 0
                ? max(0, round((($periodSeconds - $downtimeSeconds) / $periodSeconds) * 100, 2))
                : 100;

            return [
                'monitor'          => $monitor,
                'incident_count'   => $incidents->count(),
                'downtime_seconds' => $downtimeSeconds,
                'mttr_seconds'     => $mttrSeconds,
                'availability'     => $availability,
            ];
        });

        // --- Panel 2: Ringkasan insiden operasional (semua kategori) ---
        $allIncidents = Incident::where('started_at', '>=', $periodStart)->get();

        // Bar chart: insiden per interval waktu, stacked by category
        $chartLabels  = [];
        $chartMonitor = [];
        $chartGeneral = [];
        $chartClient  = [];

        if ($days === 7) {
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = now()->subDays($i)->toDateString();
                $chartLabels[]  = now()->subDays($i)->format('d M');
                $bucket = $allIncidents->filter(fn ($inc) => $inc->started_at->toDateString() === $date);
                $chartMonitor[] = $bucket->where('category', 'monitor_downtime')->count();
                $chartGeneral[] = $bucket->where('category', 'general')->count();
                $chartClient[]  = $bucket->where('category', 'client_report')->count();
            }
        } else {
            $weeks = $days === 30 ? 5 : 13;
            for ($i = $weeks - 1; $i >= 0; $i--) {
                $wStart = now()->copy()->startOfWeek()->subWeeks($i);
                $wEnd   = $wStart->copy()->endOfWeek();
                $chartLabels[]  = $wStart->format('d M');
                $bucket = $allIncidents->filter(fn ($inc) => $inc->started_at->between($wStart, $wEnd));
                $chartMonitor[] = $bucket->where('category', 'monitor_downtime')->count();
                $chartGeneral[] = $bucket->where('category', 'general')->count();
                $chartClient[]  = $bucket->where('category', 'client_report')->count();
            }
        }

        // Donut chart: breakdown severity (semua insiden)
        $severityCounts = [
            'critical' => $allIncidents->where('severity', 'critical')->count(),
            'high'     => $allIncidents->where('severity', 'high')->count(),
            'medium'   => $allIncidents->where('severity', 'medium')->count(),
            'low'      => $allIncidents->where('severity', 'low')->count(),
        ];

        // Summary card: insiden operasional (general + client_report)
        $opIncidents   = $allIncidents->whereIn('category', ['general', 'client_report']);
        $opClosed      = $opIncidents->where('status', 'closed');
        $opMttr        = $opClosed->count() > 0 ? (int) $opClosed->avg('duration_seconds') : 0;

        $opSummary = [
            'total'         => $opIncidents->count(),
            'open'          => $opIncidents->where('status', 'open')->count(),
            'closed'        => $opClosed->count(),
            'mttr_seconds'  => $opMttr,
            'general'       => $opIncidents->where('category', 'general')->count(),
            'client_report' => $opIncidents->where('category', 'client_report')->count(),
        ];

        $chartData = [
            'labels'  => $chartLabels,
            'monitor' => $chartMonitor,
            'general' => $chartGeneral,
            'client'  => $chartClient,
        ];

        return view('sla-report.index', compact('rows', 'days', 'chartData', 'severityCounts', 'opSummary'));
    }
}
