<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function notifyDown(Monitor $monitor): void
    {
        $this->dispatch($monitor, 'DOWN');
    }

    public function notifyRecovered(Monitor $monitor): void
    {
        $this->dispatch($monitor, 'UP');
    }

    public function sendBatchDown(array $monitors): void
    {
        if (empty($monitors)) return;

        if (count($monitors) === 1) {
            $this->dispatch($monitors[0], 'DOWN');
            return;
        }

        $time  = now()->format('d-m-Y H:i:s');
        $count = count($monitors);
        $lines = ["🔴 <b>{$count} Monitor DOWN</b>", "Waktu: {$time}", ""];
        foreach ($monitors as $m) {
            $lines[] = "• <b>{$m->name}</b> — " . ($m->url ?: '-');
        }

        $htmlMsg  = implode("\n", $lines);
        $plainMsg = strip_tags($htmlMsg);
        $this->sendToMergedChannels($monitors, 'monitor.batch_down', $htmlMsg, $plainMsg);
    }

    public function sendBatchUp(array $monitors): void
    {
        if (empty($monitors)) return;

        if (count($monitors) === 1) {
            $this->dispatch($monitors[0], 'UP');
            return;
        }

        $time  = now()->format('d-m-Y H:i:s');
        $count = count($monitors);
        $lines = ["🟢 <b>{$count} Monitor UP kembali</b>", "Waktu: {$time}", ""];
        foreach ($monitors as $m) {
            $incident = Incident::where('monitor_id', $m->id)
                ->where('status', 'closed')
                ->latest('resolved_at')
                ->first();
            $dur = '';
            if ($incident?->duration_seconds) {
                $s   = $incident->duration_seconds;
                $dur = ' (down ' . ($s >= 3600 ? floor($s / 3600) . 'j ' : '')
                     . ($s >= 60 ? floor(($s % 3600) / 60) . 'm ' : '')
                     . ($s % 60) . 'd)';
            }
            $lines[] = "• <b>{$m->name}</b> — " . ($m->url ?: '-') . $dur;
        }

        $htmlMsg  = implode("\n", $lines);
        $plainMsg = strip_tags($htmlMsg);
        $this->sendToMergedChannels($monitors, 'monitor.batch_up', $htmlMsg, $plainMsg);
    }

    public function notifyMajorIncident(array $monitors): void
    {
        $count = count($monitors);
        $time  = now()->format('d-m-Y H:i:s');
        $names = collect($monitors)->pluck('name')->implode(', ');
        $htmlMsg  = "🚨 <b>MAJOR INCIDENT: {$count} Monitor DOWN</b>\nWaktu: {$time}\n\nMonitor: {$names}";
        $plainMsg = strip_tags($htmlMsg);
        $this->sendToMergedChannels($monitors, 'incident.major', $htmlMsg, $plainMsg);
    }

    private function sendToMergedChannels(array $monitors, string $event, string $htmlMsg, string $plainMsg): void
    {
        $allChannelIds = collect($monitors)
            ->flatMap(fn($m) => $m->notification_channels ?? [])
            ->unique()->values()->all();
        if (empty($allChannelIds)) return;

        $channels = NotificationChannel::whereIn('id', $allChannelIds)->where('is_active', true)->get();
        foreach ($channels as $channel) {
            $this->dispatchToChannel($channel, $monitors[0], $event, $htmlMsg, $plainMsg);
        }
    }

    private function dispatchToChannel(NotificationChannel $channel, Monitor $monitor, string $event, string $html, string $plain): void
    {
        match ($channel->type) {
            'telegram' => $this->sendTelegram($channel, $html),
            'whatsapp' => $this->sendFonnte($channel, $plain),
            'webhook'  => $this->sendWebhook($channel, $monitor, $event, $plain),
            'email'    => $this->sendEmail($channel, $event, $plain),
            'slack'    => $this->sendSlack($channel, $html),
            'discord'  => $this->sendDiscord($channel, $plain),
            'ntfy'     => $this->sendNtfy($channel, $plain),
            'pushover' => $this->sendPushover($channel, $plain),
            default    => null,
        };
    }

    public function notifySlow(Monitor $monitor): void
    {
        $vars = [
            '{name}'          => $monitor->name,
            '{url}'           => $monitor->url,
            '{response_time}' => $monitor->last_response_time . 'ms',
            '{threshold}'     => $monitor->response_time_warning,
            '{timestamp}'     => now()->format('d-m-Y H:i:s'),
        ];
        $defaults = AppSetting::defaults();
        $htmlMsg  = str_replace(array_keys($vars), array_values($vars),
            AppSetting::get('notif_slow_body', $defaults['notif_slow_body']));
        $this->send($monitor, 'monitor.slow', $htmlMsg, strip_tags($htmlMsg));
    }

    public function notifySslExpiry(Monitor $monitor): void
    {
        $days   = $monitor->ssl_days_remaining;
        $expiry = $monitor->ssl_expiry_at?->format('d-m-Y') ?? '-';
        $time   = now()->format('d-m-Y H:i:s');

        $plain = "⚠️ SSL Cert akan kedaluwarsa!\n"
            . "Monitor: {$monitor->name}\nURL: {$monitor->url}\n"
            . "Sisa: {$days} hari (expire: {$expiry})";

        $htmlMsg = "⚠️ <b>SSL Cert akan kedaluwarsa!</b>\n"
            . "Monitor: {$monitor->name}\nURL: {$monitor->url}\n"
            . "Sisa: <b>{$days} hari</b> (expire: {$expiry})\nWaktu: {$time}";

        $this->send($monitor, 'monitor.ssl_expiry', $htmlMsg, $plain);
    }

    // ─── private ──────────────────────────────────────────────────────────────

    private function renderTemplate(string $key, array $vars): string
    {
        $defaults = AppSetting::defaults();
        $template = AppSetting::get($key, $defaults[$key] ?? '');
        return str_replace(array_keys($vars), array_values($vars), $template);
    }

    private function dispatch(Monitor $monitor, string $status): void
    {
        $isDown   = $status === 'DOWN';
        $duration = '-';

        if (!$isDown) {
            $incident = Incident::where('monitor_id', $monitor->id)
                ->where('status', 'closed')
                ->latest('resolved_at')
                ->first();
            if ($incident?->duration_seconds) {
                $s = $incident->duration_seconds;
                $duration = ($s >= 3600 ? floor($s / 3600) . 'j ' : '')
                          . ($s >= 60   ? floor(($s % 3600) / 60) . 'm ' : '')
                          . ($s % 60)   . 'd';
            }
        }

        $vars = [
            '{name}'          => $monitor->name,
            '{url}'           => $monitor->url,
            '{status}'        => $status,
            '{response_time}' => $monitor->last_response_time ? $monitor->last_response_time . 'ms' : '-',
            '{timestamp}'     => now()->format('d-m-Y H:i:s'),
            '{duration}'      => $duration,
        ];

        $key     = $isDown ? 'notif_down_body' : 'notif_recovered_body';
        $htmlMsg = $this->renderTemplate($key, $vars);
        // WhatsApp: strip HTML tags
        $plainMsg = strip_tags($htmlMsg);

        $this->send($monitor, 'monitor.' . strtolower($status), $htmlMsg, $plainMsg);
    }

    private function send(Monitor $monitor, string $event, string $htmlMsg, string $plainMsg): void
    {
        $channelIds = $monitor->notification_channels ?? [];
        if (empty($channelIds)) return;

        $channels = NotificationChannel::whereIn('id', $channelIds)->where('is_active', true)->get();
        foreach ($channels as $channel) {
            $this->dispatchToChannel($channel, $monitor, $event, $htmlMsg, $plainMsg);
        }
    }

    private function parseTelegramTarget(string $target): array
    {
        // Format: "chat_id" atau "chat_id:thread_id" untuk supergroup topic
        if (str_contains($target, ':')) {
            [$chatId, $threadId] = explode(':', $target, 2);
            return ['chat_id' => $chatId, 'message_thread_id' => (int) $threadId];
        }
        return ['chat_id' => $target];
    }

    private function sendTelegram(NotificationChannel $channel, string $message): void
    {
        try {
            $payload = $this->parseTelegramTarget($channel->target);
            $payload['text']       = $message;
            $payload['parse_mode'] = 'HTML';

            $res = Http::timeout(10)->post("https://api.telegram.org/bot{$channel->token}/sendMessage", $payload);
            if (!$res->ok()) {
                Log::warning("Telegram rejected: " . $res->body());
            }
        } catch (\Throwable $e) {
            Log::error("Telegram notification failed: {$e->getMessage()}");
        }
    }

    private function sendFonnte(NotificationChannel $channel, string $message): void
    {
        try {
            $res = Http::withHeaders(['Authorization' => $channel->token])
                ->timeout(15)
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target'      => $channel->target,
                    'message'     => $message,
                    'countryCode' => '62',
                ]);

            if (!$res->ok() || $res->json('status') === false) {
                Log::warning("Fonnte rejected: " . $res->body());
            }
        } catch (\Throwable $e) {
            Log::error("Fonnte notification failed: {$e->getMessage()}");
        }
    }

    public function sendTest(NotificationChannel $channel): array
    {
        $msg = "WatchTower Test\nChannel: {$channel->name}\nWaktu: " . now()->format('d-m-Y H:i:s');
        try {
            match ($channel->type) {
                'telegram' => (function() use ($channel, $msg, &$result) {
                    $p = $this->parseTelegramTarget($channel->target);
                    $p['text'] = $msg; $p['parse_mode'] = 'HTML';
                    $r = Http::timeout(10)->post("https://api.telegram.org/bot{$channel->token}/sendMessage", $p);
                    $result = ['ok' => $r->ok(), 'body' => $r->json()];
                })(),
                'whatsapp' => (function() use ($channel, $msg, &$result) {
                    $r = Http::withHeaders(['Authorization' => $channel->token])->timeout(15)->asForm()
                        ->post('https://api.fonnte.com/send', ['target' => $channel->target, 'message' => $msg, 'countryCode' => '62']);
                    $result = ['ok' => $r->ok() && $r->json('status') !== false, 'body' => $r->json()];
                })(),
                'webhook' => (function() use ($channel, $msg, &$result) {
                    $p = json_encode(['event' => 'test', 'message' => $msg, 'timestamp' => now()->toIso8601String()]);
                    $h = ['Content-Type' => 'application/json', 'User-Agent' => 'WatchTower/2.0'];
                    if ($channel->token) $h['X-WatchTower-Signature'] = 'sha256=' . hash_hmac('sha256', $p, $channel->token);
                    $r = Http::withHeaders($h)->timeout(10)->withBody($p, 'application/json')->post($channel->target);
                    $result = ['ok' => $r->ok(), 'body' => ['status_code' => $r->status()]];
                })(),
                'email' => (function() use ($channel, $msg, &$result) {
                    $this->sendEmail($channel, 'test', $msg);
                    $result = ['ok' => true, 'body' => ['info' => 'email queued']];
                })(),
                'slack' => (function() use ($channel, $msg, &$result) {
                    $r = Http::timeout(10)->post($channel->target, ['text' => $msg]);
                    $result = ['ok' => $r->ok(), 'body' => ['status_code' => $r->status()]];
                })(),
                'discord' => (function() use ($channel, $msg, &$result) {
                    $r = Http::timeout(10)->post($channel->target, ['content' => $msg]);
                    $result = ['ok' => $r->ok(), 'body' => ['status_code' => $r->status()]];
                })(),
                'ntfy' => (function() use ($channel, $msg, &$result) {
                    $r = Http::timeout(10)->withHeaders(['Title' => 'WatchTower Test'])->withBody($msg)->post($channel->target);
                    $result = ['ok' => $r->ok(), 'body' => ['status_code' => $r->status()]];
                })(),
                'pushover' => (function() use ($channel, $msg, &$result) {
                    [$appKey, $userKey] = explode('|', $channel->token . '|', 2);
                    $r = Http::timeout(10)->asForm()->post('https://api.pushover.net/1/messages.json', [
                        'token' => trim($appKey), 'user' => trim($userKey), 'message' => $msg, 'title' => 'WatchTower',
                    ]);
                    $result = ['ok' => $r->ok(), 'body' => $r->json()];
                })(),
                default => ($result = ['ok' => false, 'body' => ['error' => 'Unknown channel type']]),
            };
        } catch (\Throwable $e) {
            return ['ok' => false, 'body' => ['error' => $e->getMessage()]];
        }
        return $result ?? ['ok' => false, 'body' => ['error' => 'no result']];
    }

    private function sendEmail(NotificationChannel $channel, string $event, string $message): void
    {
        try {
            // channel->target = email address; channel->token = optional "from" override
            $to = $channel->target;
            \Illuminate\Support\Facades\Mail::raw($message, function ($m) use ($to, $event) {
                $m->to($to)->subject('WatchTower: ' . ucfirst(str_replace('.', ' ', $event)));
            });
        } catch (\Throwable $e) {
            Log::error("Email notification failed: {$e->getMessage()}");
        }
    }

    private function sendSlack(NotificationChannel $channel, string $message): void
    {
        try {
            $plain = strip_tags($message);
            Http::timeout(10)->post($channel->target, [
                'blocks' => [[
                    'type' => 'section',
                    'text' => ['type' => 'mrkdwn', 'text' => $plain],
                ]],
            ]);
        } catch (\Throwable $e) {
            Log::error("Slack notification failed: {$e->getMessage()}");
        }
    }

    private function sendDiscord(NotificationChannel $channel, string $message): void
    {
        try {
            Http::timeout(10)->post($channel->target, ['content' => strip_tags($message)]);
        } catch (\Throwable $e) {
            Log::error("Discord notification failed: {$e->getMessage()}");
        }
    }

    private function sendNtfy(NotificationChannel $channel, string $message): void
    {
        // target = https://ntfy.sh/topic; token = optional auth token
        try {
            $req = Http::timeout(10)->withHeaders(['Title' => 'WatchTower Alert', 'Priority' => 'high']);
            if ($channel->token) $req = $req->withToken($channel->token);
            $req->withBody(strip_tags($message))->post($channel->target);
        } catch (\Throwable $e) {
            Log::error("ntfy notification failed: {$e->getMessage()}");
        }
    }

    private function sendPushover(NotificationChannel $channel, string $message): void
    {
        // token format: "app_token|user_key"
        try {
            [$appKey, $userKey] = explode('|', $channel->token . '|', 2);
            Http::timeout(10)->asForm()->post('https://api.pushover.net/1/messages.json', [
                'token'   => trim($appKey),
                'user'    => trim($userKey),
                'message' => strip_tags($message),
                'title'   => 'WatchTower',
            ]);
        } catch (\Throwable $e) {
            Log::error("Pushover notification failed: {$e->getMessage()}");
        }
    }

    private function sendWebhook(NotificationChannel $channel, Monitor $monitor, string $event, string $message): void
    {
        $payload = json_encode([
            'event'   => $event,
            'monitor' => [
                'id'             => $monitor->id,
                'name'           => $monitor->name,
                'url'            => $monitor->url,
                'type'           => $monitor->type,
                'status'         => $monitor->last_status,
                'last_checked_at'=> $monitor->last_checked_at?->toIso8601String(),
            ],
            'timestamp' => now()->toIso8601String(),
            'message'   => $message,
        ]);

        $headers = ['Content-Type' => 'application/json', 'User-Agent' => 'WatchTower/1.0'];

        // HMAC signature jika secret key diisi
        if (!empty($channel->token)) {
            $headers['X-WatchTower-Signature'] = 'sha256=' . hash_hmac('sha256', $payload, $channel->token);
        }

        try {
            Http::withHeaders($headers)
                ->withBody($payload, 'application/json')
                ->timeout(10)
                ->post($channel->target);
        } catch (\Throwable $e) {
            Log::error("Webhook notification failed [{$channel->target}]: {$e->getMessage()}");
        }
    }
}
