<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Monitor;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DowntimeReportController extends Controller
{
    public function index(Request $request)
    {
        $from      = $request->date('from', 'Y-m-d') ?? now()->startOfMonth();
        $to        = $request->date('to', 'Y-m-d')   ?? now()->endOfDay();
        $monitorId = $request->integer('monitor_id') ?: null;

        $query = Incident::with('monitor')
            ->where('category', 'monitor_downtime')
            ->where('started_at', '>=', $from)
            ->where('started_at', '<=', $to)
            ->orderBy('started_at', 'desc');

        if ($monitorId) {
            $query->where('monitor_id', $monitorId);
        }

        $incidents = $query->get();

        // Summary per monitor
        $summary = $incidents->groupBy('monitor_id')->map(function ($group) {
            $totalSeconds  = $group->sum(fn($i) => $i->duration_seconds ?? $i->started_at->diffInSeconds($i->resolved_at ?? now()));
            $count         = $group->count();
            $longestSeconds = $group->max(fn($i) => $i->duration_seconds ?? $i->started_at->diffInSeconds($i->resolved_at ?? now()));
            return [
                'monitor'        => $group->first()->monitor,
                'count'          => $count,
                'total_seconds'  => $totalSeconds,
                'longest_seconds'=> $longestSeconds,
            ];
        })->sortByDesc('total_seconds');

        // Total downtime all monitors in range
        $totalDownSeconds = $incidents->sum(fn($i) => $i->duration_seconds ?? $i->started_at->diffInSeconds($i->resolved_at ?? now()));

        $monitors = Monitor::orderBy('name')->get();

        return view('reports.downtime', compact(
            'incidents', 'summary', 'monitors', 'from', 'to', 'monitorId', 'totalDownSeconds'
        ));
    }

    public function export(Request $request)
    {
        $from      = $request->date('from', 'Y-m-d') ?? now()->startOfMonth();
        $to        = $request->date('to', 'Y-m-d')   ?? now()->endOfDay();
        $monitorId = $request->integer('monitor_id') ?: null;

        $query = Incident::with('monitor')
            ->where('category', 'monitor_downtime')
            ->where('started_at', '>=', $from)
            ->where('started_at', '<=', $to)
            ->orderBy('started_at', 'desc');

        if ($monitorId) {
            $query->where('monitor_id', $monitorId);
        }

        $incidents = $query->get();

        $rows   = [];
        $rows[] = ['Monitor', 'Mulai DOWN', 'Pulih', 'Durasi', 'Status', 'Catatan'];
        foreach ($incidents as $i) {
            $dur     = $i->duration_seconds ?? $i->started_at->diffInSeconds($i->resolved_at ?? now());
            $rows[]  = [
                $i->monitor->name ?? '-',
                $i->started_at->format('d/m/Y H:i:s'),
                $i->resolved_at?->format('d/m/Y H:i:s') ?? 'Belum pulih',
                $this->formatDuration($dur),
                $i->status === 'resolved' ? 'Resolved' : 'Open',
                $i->note ?? '',
            ];
        }

        $filename = 'downtime_' . $from->format('Ymd') . '_' . $to->format('Ymd') . '.csv';
        $headers  = ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename={$filename}"];

        $callback = function () use ($rows) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
            foreach ($rows as $row) fputcsv($f, $row);
            fclose($f);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds < 60)   return "{$seconds}d";
        if ($seconds < 3600) return round($seconds / 60) . 'm';
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        return $m > 0 ? "{$h}j {$m}m" : "{$h}j";
    }
}
