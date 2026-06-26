<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MonitorApiController extends Controller
{
    public function index(Request $request)
    {
        $monitors = \App\Models\Monitor::with('tags')
            ->when($request->env, fn($q) => $q->where('environment', $request->env))
            ->when($request->status, fn($q) => $q->where('last_status', $request->status))
            ->get()
            ->map(fn($m) => [
                'id'             => $m->id,
                'name'           => $m->name,
                'url'            => $m->url,
                'type'           => $m->type,
                'status'         => $m->last_status,
                'response_time'  => $m->last_response_time,
                'uptime_24h'     => $m->uptime_24h,
                'uptime_30d'     => $m->uptime_30d,
                'health_score'   => $m->health_score,
                'environment'    => $m->environment,
                'tags'           => $m->tags->pluck('name'),
                'last_checked'   => $m->last_checked_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $monitors, 'total' => $monitors->count()]);
    }

    public function show($id)
    {
        $m = \App\Models\Monitor::with(['tags', 'slaContracts'])->findOrFail($id);
        return response()->json([
            'id' => $m->id, 'name' => $m->name, 'url' => $m->url,
            'type' => $m->type, 'status' => $m->last_status,
            'response_time' => $m->last_response_time,
            'uptime' => ['24h' => $m->uptime_24h, '7d' => $m->uptime_7d, '30d' => $m->uptime_30d],
            'health_score' => $m->health_score,
            'environment' => $m->environment,
            'notes' => $m->notes,
            'runbook_url' => $m->runbook_url,
            'tags' => $m->tags->pluck('name'),
            'sla_contracts' => $m->slaContracts->map(fn($s) => [
                'name' => $s->name, 'target_uptime' => $s->target_uptime, 'period' => $s->period,
            ]),
            'last_checked' => $m->last_checked_at?->toIso8601String(),
        ]);
    }

    public function incidents($id)
    {
        $incidents = \App\Models\Incident::where('monitor_id', $id)
            ->orderByDesc('started_at')->limit(20)->get()
            ->map(fn($i) => [
                'id' => $i->id, 'status' => $i->status,
                'started_at' => $i->started_at?->toIso8601String(),
                'resolved_at' => $i->resolved_at?->toIso8601String(),
                'duration_seconds' => $i->duration_seconds,
            ]);
        return response()->json(['data' => $incidents]);
    }

    public function trigger(Request $request, $id)
    {
        // Push heartbeat to cron/push monitor
        $monitor = \App\Models\Monitor::findOrFail($id);
        if (!in_array($monitor->type, ['push', 'cron'])) {
            return response()->json(['error' => 'Only push/cron monitors accept heartbeat'], 422);
        }
        $monitor->update(['last_push_at' => now(), 'last_heartbeat_at' => now()]);
        return response()->json(['ok' => true, 'message' => 'Heartbeat received']);
    }

    public function statusSummary()
    {
        $monitors = \App\Models\Monitor::all();
        return response()->json([
            'total'   => $monitors->count(),
            'up'      => $monitors->where('last_status', 'up')->count(),
            'down'    => $monitors->where('last_status', 'down')->count(),
            'pending' => $monitors->whereIn('last_status', ['pending', null])->count(),
            'avg_health_score' => round($monitors->avg('health_score'), 1),
        ]);
    }
}
