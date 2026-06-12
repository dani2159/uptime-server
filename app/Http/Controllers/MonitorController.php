<?php

namespace App\Http\Controllers;

use App\Models\Monitor;
use App\Models\NotificationChannel;
use App\Services\DnsResolver;
use App\Services\SslChecker;
use App\Services\UptimeChecker;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function __construct(
        private UptimeChecker $checker,
        private DnsResolver $resolver,
        private SslChecker $sslChecker
    ) {}

    public function index()
    {
        $monitors = Monitor::with('heartbeatLogs')->orderBy('name')->paginate(20);
        return view('monitors.index', compact('monitors'));
    }

    public function create()
    {
        $channels = NotificationChannel::where('is_active', true)->get();
        return view('monitors.create', compact('channels'));
    }

    public function store(Request $request)
    {
        $urlRule = in_array($request->type, ['http', 'keyword']) ? 'required|url|max:500' : 'required|string|max:500';

        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'url'                   => $urlRule,
            'type'                  => 'required|in:http,ping,keyword,tcp,dns,push',
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
        ]);

        $monitor = Monitor::create($data);
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
        return view('monitors.edit', compact('monitor', 'channels'));
    }

    public function update(Request $request, Monitor $monitor)
    {
        $urlRule = in_array($request->type, ['http', 'keyword']) ? 'required|url|max:500' : 'required|string|max:500';

        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'url'                   => $urlRule,
            'type'                  => 'required|in:http,ping,keyword,tcp,dns,push',
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
            'is_active'             => 'boolean',
            'notification_channels' => 'nullable|array',
        ]);

        $monitor->update($data);

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

    public function toggle(Monitor $monitor)
    {
        $monitor->update(['is_active' => !$monitor->is_active]);
        return back()->with('success', $monitor->is_active ? 'Monitor diaktifkan.' : 'Monitor dijeda.');
    }

    public function checkNow(Request $request, Monitor $monitor)
    {
        $result = $this->checker->check($monitor);
        $this->checker->saveResult($monitor, $result);
        $this->resolver->resolve($monitor);

        if ($ssl = $this->sslChecker->check($monitor)) {
            $this->sslChecker->saveResult($monitor, $ssl);
        }

        $msg = "Cek selesai: " . strtoupper($result['status'])
            . ($result['response_time'] ? " ({$result['response_time']}ms)" : '');

        if ($request->expectsJson()) {
            return response()->json([
                'status'        => $result['status'],
                'response_time' => $result['response_time'] ?? null,
                'message'       => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}
