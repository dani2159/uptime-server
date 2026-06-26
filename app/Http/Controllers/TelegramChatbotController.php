<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TelegramChatbotController extends Controller
{
    public function webhook(Request $request)
    {
        $update = $request->all();
        $msg    = $update['message'] ?? $update['channel_post'] ?? null;
        if (!$msg) return response()->json(['ok' => true]);

        $chatId = $msg['chat']['id'] ?? null;
        $text   = trim($msg['text'] ?? '');
        $from   = $msg['from']['username'] ?? $msg['from']['first_name'] ?? 'unknown';

        if (!$chatId) return response()->json(['ok' => true]);

        $reply = $this->processCommand($text, $chatId, $from);
        if ($reply) $this->sendReply($chatId, $reply);

        return response()->json(['ok' => true]);
    }

    private function processCommand(string $text, $chatId, string $from): ?string
    {
        $cmd = strtolower(explode(' ', $text)[0]);
        $arg = trim(substr($text, strlen($cmd)));

        return match ($cmd) {
            '/status'  => $this->cmdStatus(),
            '/down'    => $this->cmdDown(),
            '/uptime'  => $this->cmdUptime($arg),
            '/ack'     => $this->cmdAck($arg, $from),
            '/silence' => $this->cmdSilence($arg),
            '/help'    => $this->cmdHelp(),
            default    => null,
        };
    }

    private function cmdStatus(): string
    {
        $monitors = \App\Models\Monitor::where('is_active', true)->get();
        $down     = $monitors->where('last_status', 'down');
        $up       = $monitors->where('last_status', 'up');
        $lines    = ["📊 <b>WatchTower Status</b>", "✅ UP: {$up->count()} | ❌ DOWN: {$down->count()}"];
        foreach ($down as $m) $lines[] = "  • <b>{$m->name}</b> — {$m->url}";
        return implode("\n", $lines);
    }

    private function cmdDown(): string
    {
        $down = \App\Models\Monitor::where('last_status', 'down')->get();
        if ($down->isEmpty()) return '✅ Semua monitor UP';
        $lines = ["❌ <b>Monitor DOWN (" . $down->count() . "):</b>"];
        foreach ($down as $m) {
            $since = $m->last_down_at?->diffForHumans() ?? '-';
            $lines[] = "• <b>{$m->name}</b> — down sejak {$since}";
        }
        return implode("\n", $lines);
    }

    private function cmdUptime(string $name): string
    {
        $m = \App\Models\Monitor::where('name', 'like', "%{$name}%")->first();
        if (!$m) return "Monitor '{$name}' tidak ditemukan.";
        return "<b>{$m->name}</b>\n24h: {$m->uptime_24h}% | 7d: {$m->uptime_7d}% | 30d: {$m->uptime_30d}%\nHealth: {$m->health_score}/100";
    }

    private function cmdAck(string $arg, string $from): string
    {
        // /ack <incident_id> or /ack all
        if ($arg === 'all') {
            $incidents = \App\Models\Incident::open()->get();
        } else {
            $incidents = \App\Models\Incident::open()->where('id', (int)$arg)->get();
        }
        if ($incidents->isEmpty()) return "Tidak ada insiden terbuka.";
        foreach ($incidents as $incident) {
            \App\Models\AlertAcknowledgement::create([
                'incident_id'  => $incident->id,
                'acked_by'     => $from,
                'channel_type' => 'telegram',
                'acked_at'     => now(),
            ]);
            \App\Services\AuditService::log('incident.acked', "Insiden #{$incident->id} di-ack oleh @{$from} via Telegram");
        }
        return "✅ {$incidents->count()} insiden di-acknowledge oleh @{$from}";
    }

    private function cmdSilence(string $arg): string
    {
        // /silence <monitor_name> <hours>
        $parts   = explode(' ', $arg);
        $name    = $parts[0] ?? '';
        $hours   = (int)($parts[1] ?? 1);
        $monitor = \App\Models\Monitor::where('name', 'like', "%{$name}%")->first();
        if (!$monitor) return "Monitor '{$name}' tidak ditemukan.";
        \App\Models\MaintenanceWindow::create([
            'name'        => "Silence via Telegram",
            'starts_at'   => now(),
            'ends_at'     => now()->addHours($hours),
            'monitor_ids' => [$monitor->id],
        ]);
        return "🔕 {$monitor->name} di-silence selama {$hours} jam.";
    }

    private function cmdHelp(): string
    {
        return "<b>WatchTower Bot Commands:</b>\n"
            . "/status — ringkasan semua monitor\n"
            . "/down — daftar monitor yang DOWN\n"
            . "/uptime [nama] — uptime % monitor\n"
            . "/ack [id|all] — acknowledge insiden\n"
            . "/silence [nama] [jam] — silence monitor\n"
            . "/help — bantuan";
    }

    private function sendReply($chatId, string $text): void
    {
        $channels = \App\Models\NotificationChannel::where('type', 'telegram')->where('is_active', true)->first();
        if (!$channels) return;
        try {
            \Illuminate\Support\Facades\Http::timeout(10)
                ->post("https://api.telegram.org/bot{$channels->token}/sendMessage", [
                    'chat_id'    => $chatId,
                    'text'       => $text,
                    'parse_mode' => 'HTML',
                ]);
        } catch (\Throwable) {}
    }
}
