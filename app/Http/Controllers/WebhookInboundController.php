<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookInboundController extends Controller
{
    public function index()
    {
        $receivers = \App\Models\WebhookInboundEvent::orderByDesc('updated_at')->paginate(20);
        return view('webhook-inbound.index', compact('receivers'));
    }

    public function create()
    {
        return view('webhook-inbound.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:100',
            'source' => 'nullable|in:grafana,zabbix,prometheus,custom',
        ]);
        $data['token'] = substr(md5(uniqid('', true)), 0, 32);
        \App\Models\WebhookInboundEvent::create($data);
        return redirect()->route('webhook-inbound.index')->with('success', 'Webhook receiver dibuat.');
    }

    public function receive(Request $request, string $token)
    {
        $receiver = \App\Models\WebhookInboundEvent::where('token', $token)->where('is_active', true)->firstOrFail();
        $payload  = $request->all();

        // Detect status from common fields
        $status = 'unknown';
        if (isset($payload['state'])) $status = strtolower($payload['state']); // Grafana
        if (isset($payload['status'])) $status = strtolower($payload['status']); // Prometheus
        if (isset($payload['problem'])) $status = $payload['problem'] ? 'down' : 'up'; // Zabbix

        $receiver->update([
            'last_payload'     => $payload,
            'last_status'      => $status,
            'last_received_at' => now(),
        ]);

        // If linked to a monitor, create incident if status is firing/down
        if ($receiver->monitor_id && in_array($status, ['firing', 'down', 'problem', 'alerting'])) {
            $monitor = \App\Models\Monitor::find($receiver->monitor_id);
            if ($monitor) {
                \App\Models\Incident::firstOrCreate(
                    ['monitor_id' => $monitor->id, 'status' => 'open'],
                    ['category' => 'external_alert', 'started_at' => now(), 'status' => 'open']
                );
            }
        }

        \App\Services\AuditService::log('webhook.inbound', "Webhook dari {$receiver->source}: {$status}");
        return response()->json(['ok' => true]);
    }

    public function destroy(string $id)
    {
        \App\Models\WebhookInboundEvent::findOrFail($id)->delete();
        return redirect()->route('webhook-inbound.index')->with('success', 'Receiver dihapus.');
    }
}
