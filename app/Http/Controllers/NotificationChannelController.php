<?php

namespace App\Http\Controllers;

use App\Models\NotificationChannel;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationChannelController extends Controller
{
    public function index()
    {
        $channels = NotificationChannel::orderBy('name')->get();
        return view('channels.index', compact('channels'));
    }

    public function create()
    {
        return view('channels.create');
    }

    public function store(Request $request)
    {
        $isWebhook = $request->type === 'webhook';

        $data = $request->validate([
            'name'   => 'required|string|max:100',
            'type'   => 'required|in:telegram,whatsapp,webhook',
            'token'  => $isWebhook ? 'nullable|string|max:255' : 'required|string',
            'target' => $isWebhook ? 'required|url|max:500' : 'required|string|max:255',
        ]);

        NotificationChannel::create($data);

        return redirect()->route('channels.index')->with('success', 'Channel berhasil ditambahkan.');
    }

    public function edit(NotificationChannel $channel)
    {
        return view('channels.edit', compact('channel'));
    }

    public function update(Request $request, NotificationChannel $channel)
    {
        $isWebhook = $request->type === 'webhook';

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:telegram,whatsapp,webhook',
            'token'     => 'nullable|string|max:255',
            'target'    => $isWebhook ? 'required|url|max:500' : 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        if (empty($data['token'])) {
            unset($data['token']);
        }

        $channel->update($data);

        return redirect()->route('channels.index')->with('success', 'Channel berhasil diperbarui.');
    }

    public function destroy(NotificationChannel $channel)
    {
        $channel->delete();
        return redirect()->route('channels.index')->with('success', 'Channel berhasil dihapus.');
    }

    public function test(NotificationChannel $channel, NotificationService $notifier)
    {
        $result = $notifier->sendTest($channel);
        return response()->json($result);
    }
}
