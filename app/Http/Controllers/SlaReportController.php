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

        return view('sla-report.index', compact('rows', 'days'));
    }
}
