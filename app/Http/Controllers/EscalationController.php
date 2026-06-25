<?php

namespace App\Http\Controllers;

use App\Models\EscalationRule;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use Illuminate\Http\Request;

class EscalationController extends Controller
{
    public function index()
    {
        $rules = EscalationRule::with(['channel', 'monitor'])->orderBy('delay_minutes')->get();
        return view('escalations.index', compact('rules'));
    }

    public function create()
    {
        $channels = NotificationChannel::where('is_active', true)->orderBy('name')->get();
        $monitors = Monitor::orderBy('name')->get(['id', 'name']);
        return view('escalations.create', compact('channels', 'monitors'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'channel_id'    => 'required|exists:notification_channels,id',
            'delay_minutes' => 'required|integer|min:1|max:1440',
            'monitor_id'    => 'nullable|exists:monitors,id',
            'is_active'     => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        EscalationRule::create($data);
        return redirect()->route('escalations.index')->with('success', 'Aturan eskalasi ditambahkan.');
    }

    public function edit(EscalationRule $escalation)
    {
        $channels = NotificationChannel::where('is_active', true)->orderBy('name')->get();
        $monitors = Monitor::orderBy('name')->get(['id', 'name']);
        return view('escalations.edit', compact('escalation', 'channels', 'monitors'));
    }

    public function update(Request $request, EscalationRule $escalation)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'channel_id'    => 'required|exists:notification_channels,id',
            'delay_minutes' => 'required|integer|min:1|max:1440',
            'monitor_id'    => 'nullable|exists:monitors,id',
            'is_active'     => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        $escalation->update($data);
        return redirect()->route('escalations.index')->with('success', 'Aturan eskalasi diperbarui.');
    }

    public function destroy(EscalationRule $escalation)
    {
        $escalation->delete();
        return response()->json(['ok' => true]);
    }
}
