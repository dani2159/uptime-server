<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Models\MaintenanceWindow;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Models\Tag;
use App\Services\AuditService;
use App\Services\DnsResolver;
use App\Services\NotificationService;
use App\Services\SslChecker;
use App\Services\UptimeChecker;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function __construct(
        private UptimeChecker $checker,
        private DnsResolver $resolver,
        private SslChecker $sslChecker,
        private NotificationService $notifier
    ) {}

    public function index()
    {
        $monitors = Monitor::with('heartbeatLogs')->orderBy('name')->paginate(20);
        return view('monitors.index', compact('monitors'));
    }

    public function create()
    {
        $channels = NotificationChannel::where('is_active', true)->get();
        $tags     = Tag::orderBy('name')->get();
        return view('monitors.create', compact('channels', 'tags'));
    }

    private function monitorValidationRules(Request $request): array
    {
        $urlRule = in_array($request->type, ['http', 'keyword']) ? ['required', 'string', 'max:500', 'regex:/^https?:\/\/.+/'] : 'nullable|string|max:500';
        return [
            'name'                  => 'required|string|max:100',
            'url'                   => $urlRule,
            'type'                  => 'required|in:http,ping,keyword,tcp,dns,push,cron,database,docker,whois',
            'check_interval'        => 'required|integer|min:1|max:1440',
            'timeout'               => 'required|integer|min:1|max:60',
            'retry_count'           => 'required|integer|min:1|max:10',
            'expected_status'       => 'nullable|string|max:10',
            'keyword'               => 'nullable|string|max:200',
            'tcp_host'              => 'nullable|string|max:255',
            'tcp_port'              => 'nullable|integer|min:1|max:65535',
            'push_token'            => 'nullable|string|max:100',
            'dns_resolve_type'      => 'nullable|in:A,AAAA,CNAME,MX',
            'dns_expected_value'    => 'nullable|string|max:255',
            'notification_channels' => 'nullable|array',
            'tags'                  => 'nullable|array',
            'tags.*'                => 'integer|exists:tags,id',
            'response_time_warning' => 'nullable|integer|min:100|max:60000',
            // v2
            'notes'                 => 'nullable|string|max:1000',
            'runbook_url'           => 'nullable|url|max:500',
            'environment'           => 'nullable|in:production,staging,development,testing',
            'http_method'           => 'nullable|in:GET,POST,PUT,PATCH,DELETE,HEAD,OPTIONS',
            'request_body'          => 'nullable|string|max:5000',
            'auth_type'             => 'nullable|in:basic,bearer',
            'auth_username'         => 'nullable|string|max:255',
            'auth_password'         => 'nullable|string|max:255',
            'custom_headers'        => 'nullable|string|max:2000',
            'custom_user_agent'     => 'nullable|string|max:255',
            'proxy_url'             => 'nullable|string|max:255',
            'accepted_status_codes' => 'nullable|string|max:100',
            'ignore_tls_error'      => 'nullable|boolean',
            'follow_redirects'      => 'nullable|boolean',
            'max_redirects'         => 'nullable|integer|min:0|max:20',
            'min_response_size'     => 'nullable|integer|min:0',
            'max_response_size'     => 'nullable|integer|min:0',
            'body_assertion_path'   => 'nullable|string|max:200',
            'body_assertion_value'  => 'nullable|string|max:500',
            'body_assertion_op'     => 'nullable|in:equals,contains,not_contains',
            'suppress_pattern'      => 'nullable|string|max:500',
            'flap_detection'        => 'nullable|boolean',
            'flap_window_minutes'   => 'nullable|integer|min:1|max:60',
            'flap_count_threshold'  => 'nullable|integer|min:2|max:20',
            'latency_trend_alert'   => 'nullable|boolean',
            'heartbeat_interval'    => 'nullable|integer|min:1',
            'domain_expiry_alert_days' => 'nullable|integer|min:1',
        ];
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->monitorValidationRules($request));

        $monitor = Monitor::create($data);
        $monitor->tags()->sync($request->input('tags', []));
        AuditService::log('monitor.created', "Monitor \"{$monitor->name}\" dibuat", $monitor);
        $this->resolver->resolve($monitor);

        if ($ssl = $this->sslChecker->check($monitor)) {
            $this->sslChecker->saveResult($monitor, $ssl);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok'       => true,
                'monitor'  => $monitor,
                'redirect' => route('dashboard', ['selected' => $monitor->id]),
            ]);
        }

        return redirect()->route('monitors.index')->with('success', 'Monitor berhasil ditambahkan.');
    }

    public function show(Monitor $monitor)
    {
        $logs    = $monitor->logs()->latest('checked_at')->paginate(50);
        $ips     = $monitor->ips()->where('is_active', true)->get();
        $heartbeats = $monitor->logs()->latest('checked_at')->limit(90)->get()->reverse()->values();

        $responseHistory = $monitor->logs()
            ->latest('checked_at')
            ->limit(48)
            ->get(['status', 'response_time', 'checked_at'])
            ->reverse()
            ->values();

        return view('monitors.show', compact('monitor', 'logs', 'ips', 'heartbeats', 'responseHistory'));
    }

    public function edit(Monitor $monitor)
    {
        $channels = NotificationChannel::where('is_active', true)->get();
        $tags     = Tag::orderBy('name')->get();
        return view('monitors.edit', compact('monitor', 'channels', 'tags'));
    }

    public function update(Request $request, Monitor $monitor)
    {
        $rules = $this->monitorValidationRules($request);
        $rules['is_active'] = 'boolean';
        $data = $request->validate($rules);

        $monitor->update($data);
        $monitor->tags()->sync($request->input('tags', []));
        AuditService::log('monitor.updated', "Monitor \"{$monitor->name}\" diperbarui", $monitor);

        if ($request->expectsJson()) {
            return response()->json([
                'ok'       => true,
                'monitor'  => $monitor,
                'redirect' => route('dashboard', ['selected' => $monitor->id]),
            ]);
        }

        return redirect()->route('monitors.show', $monitor)->with('success', 'Monitor berhasil diperbarui.');
    }

    public function destroy(Request $request, Monitor $monitor)
    {
        AuditService::log('monitor.deleted', "Monitor \"{$monitor->name}\" dihapus");
        $monitor->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('monitors.index')->with('success', 'Monitor berhasil dihapus.');
    }

    public function receivePush(string $token)
    {
        $monitor = Monitor::where('push_token', $token)->where('type', 'push')->firstOrFail();
        $monitor->update(['last_push_at' => now(), 'last_status' => 'up', 'last_checked_at' => now()]);
        return response()->json(['ok' => true, 'msg' => 'Heartbeat received']);
    }

    public function receiveCronHeartbeat(string $token)
    {
        $monitor = Monitor::where('push_token', $token)->where('type', 'cron')->firstOrFail();
        $monitor->update(['last_heartbeat_at' => now(), 'last_status' => 'up', 'last_checked_at' => now()]);
        return response()->json(['ok' => true, 'msg' => 'Cron heartbeat received']);
    }

    public function clone(Monitor $monitor)
    {
        $new = $monitor->replicate();
        $new->name = $monitor->name . ' (copy)';
        $new->is_active = false;
        $new->last_status = 'pending';
        $new->current_retries = 0;
        $new->push_token = \Illuminate\Support\Str::random(32);
        $new->save();
        $new->tags()->sync($monitor->tags->pluck('id'));
        AuditService::log('monitor.cloned', "Monitor \"{$monitor->name}\" diduplikat menjadi \"{$new->name}\"", $new);
        return redirect()->route('monitors.edit', $new)->with('success', "Monitor berhasil diduplikat.");
    }

    public function topology()
    {
        $monitors = Monitor::with('dependencies')->where('is_active', true)->get();
        $topologyData = $monitors->map(fn($m) => [
            'id'     => $m->id,
            'name'   => $m->name,
            'status' => $m->last_status,
            'deps'   => $m->dependencies->pluck('id')->toArray(),
        ])->values()->all();
        return view('monitors.topology', compact('monitors', 'topologyData'));
    }

    public function simulate(Request $request, Monitor $monitor)
    {
        $event = $request->input('event', 'down');
        match ($event) {
            'down' => $this->notifier->notifyDown($monitor),
            'up'   => $this->notifier->notifyRecovered($monitor),
            'slow' => $this->notifier->notifySlow($monitor),
        };
        AuditService::log('monitor.simulate', "Alert simulator: event={$event} pada \"{$monitor->name}\"", $monitor);
        return response()->json(['ok' => true, 'event' => $event]);
    }

    public function toggle(Monitor $monitor)
    {
        $monitor->update(['is_active' => !$monitor->is_active]);
        $status = $monitor->is_active ? 'diaktifkan' : 'dijeda';
        AuditService::log('monitor.toggled', "Monitor \"{$monitor->name}\" {$status}", $monitor);
        return back()->with('success', $monitor->is_active ? 'Monitor diaktifkan.' : 'Monitor dijeda.');
    }

    public function silence(Request $request, Monitor $monitor)
    {
        $request->validate(['duration' => 'required|in:1h,4h,24h,custom', 'custom_end' => 'required_if:duration,custom|nullable|date|after:now']);

        $end = match($request->duration) {
            '1h'     => now()->addHour(),
            '4h'     => now()->addHours(4),
            '24h'    => now()->addDay(),
            'custom' => \Carbon\Carbon::parse($request->custom_end),
        };

        MaintenanceWindow::create([
            'title'       => 'Silence: ' . $monitor->name,
            'description' => 'Quick silence dari dashboard',
            'start_at'    => now(),
            'end_at'      => $end,
            'monitor_ids' => [$monitor->id],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'end_at' => $end->format('d-m-Y H:i')]);
        }
        return back()->with('success', "Monitor disilence hingga {$end->format('d-m-Y H:i')}.");
    }

    public function checkNow(Request $request, Monitor $monitor)
    {
        $previousStatus = $monitor->last_status;

        $result = $this->checker->check($monitor);
        $this->checker->saveResult($monitor, $result);
        $this->resolver->resolve($monitor);

        if ($ssl = $this->sslChecker->check($monitor)) {
            $this->sslChecker->saveResult($monitor, $ssl);
        }

        $currentStatus = $result['status'];

        $withNotify = filter_var($request->query('notify', false), FILTER_VALIDATE_BOOLEAN);

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
            // Explicit notify: selalu kirim. Auto: hanya kirim jika baru down
            if ($withNotify || ($previousStatus !== 'down' && !$hasOpenIncident)) {
                $this->notifier->notifyDown($monitor);
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
                $this->notifier->notifyRecovered($monitor);
            }
        }

        $msg = "Cek selesai: " . strtoupper($currentStatus)
            . ($result['response_time'] ? " ({$result['response_time']}ms)" : '');
        AuditService::log('monitor.checked', "Cek manual \"{$monitor->name}\": {$msg}", $monitor);

        if ($request->expectsJson()) {
            return response()->json([
                'status'        => $currentStatus,
                'response_time' => $result['response_time'] ?? null,
                'message'       => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}
