<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\MonitorLog;
use App\Models\NotificationChannel;
use App\Services\UptimeChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

    public function checkAll(UptimeChecker $checker): JsonResponse
    {
        set_time_limit(300);

        $monitors = Monitor::where('is_active', true)->get();
        $results  = ['up' => 0, 'down' => 0];

        foreach ($monitors as $monitor) {
            $result = $checker->check($monitor);
            $checker->saveResult($monitor, $result);
            $results[$result['status'] === 'up' ? 'up' : 'down']++;
        }

        return response()->json([
            'checked' => $monitors->count(),
            'up'      => $results['up'],
            'down'    => $results['down'],
        ]);
    }
}
