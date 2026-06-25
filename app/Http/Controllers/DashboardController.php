<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\Monitor;
use App\Models\MonitorLog;
use App\Models\NotificationChannel;
use App\Services\NotificationService;
use App\Services\UptimeChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $search     = $request->get('q', '');
        $selectedId = $request->get('selected');

        $sidebarQuery = Monitor::with('heartbeatLogs')
            ->orderByRaw("FIELD(last_status, 'down', 'pending', 'up')")
            ->orderBy('name');

        if ($search) {
            $sidebarQuery->where(fn($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('url', 'like', "%{$search}%"));
        }

        $monitors = $sidebarQuery->get();

        $all   = Monitor::all();
        $stats = [
            'total'   => $all->count(),
            'up'      => $all->where('last_status', 'up')->count(),
            'down'    => $all->where('last_status', 'down')->count(),
            'pending' => $all->where('last_status', 'pending')->count(),
        ];

        $selected        = null;
        $heartbeats      = collect();
        $responseHistory = collect();
        $avgResponse24h  = null;

        if ($selectedId) {
            $selected = Monitor::find($selectedId);
        } elseif ($monitors->isNotEmpty()) {
            $selected = $monitors->first();
        }

        if ($selected) {
            $heartbeats = $selected->logs()
                ->latest('checked_at')
                ->limit(90)
                ->get()
                ->reverse()
                ->values();

            $responseHistory = $selected->logs()
                ->latest('checked_at')
                ->limit(48)
                ->get(['status', 'response_time', 'checked_at'])
                ->reverse()
                ->values();

            $avgResponse24h = MonitorLog::where('monitor_id', $selected->id)
                ->where('checked_at', '>=', now()->subDay())
                ->whereNotNull('response_time')
                ->avg('response_time');
            $avgResponse24h = $avgResponse24h ? (int) $avgResponse24h : null;
        }

        $channels = NotificationChannel::where('is_active', true)->get();

        return view('dashboard.index', compact(
            'monitors', 'stats', 'search',
            'selected', 'heartbeats', 'responseHistory', 'avgResponse24h',
            'channels'
        ));
    }

    public function checkAll(Request $request, UptimeChecker $checker, NotificationService $notifier): JsonResponse
    {
        set_time_limit(300);

        $withNotify = filter_var($request->query('notify', false), FILTER_VALIDATE_BOOLEAN);
        $monitors   = Monitor::where('is_active', true)->get();
        $results    = ['up' => 0, 'down' => 0, 'notified' => 0];
        Log::info("checkAll started", ['notify' => $withNotify, 'monitors' => $monitors->count()]);

        foreach ($monitors as $monitor) {
            $previousStatus = $monitor->last_status;

            $result = $checker->check($monitor);
            $checker->saveResult($monitor, $result);

            $currentStatus = $result['status'];
            $results[$currentStatus === 'up' ? 'up' : 'down']++;

            if ($currentStatus === 'down') {
                $hasOpenIncident = Incident::open()->where('monitor_id', $monitor->id)->exists();
                if (!$hasOpenIncident) {
                    $monitor->refresh();
                    Incident::create([
                        'monitor_id' => $monitor->id,
                        'category'   => 'monitor_downtime',
                        'started_at' => $monitor->last_down_at ?? now(),
                        'status'     => 'open',
                    ]);
                }
                // Explicit notify: selalu kirim. Auto (cron): hanya kirim jika baru down
                if ($withNotify || ($previousStatus !== 'down' && !$hasOpenIncident)) {
                    $notifier->notifyDown($monitor);
                    $results['notified']++;
                }
            } elseif ($currentStatus === 'up' && $previousStatus === 'down') {
                $incident = Incident::open()->where('monitor_id', $monitor->id)->latest('started_at')->first();
                if ($incident) {
                    $resolvedAt = now();
                    $incident->update([
                        'resolved_at'      => $resolvedAt,
                        'status'           => 'closed',
                        'duration_seconds' => $incident->started_at->diffInSeconds($resolvedAt),
                    ]);
                }
                if ($withNotify) {
                    $notifier->notifyRecovered($monitor);
                }
            }
        }

        return response()->json([
            'checked' => $monitors->count(),
            'up'      => $results['up'],
            'down'    => $results['down'],
        ]);
    }
}
