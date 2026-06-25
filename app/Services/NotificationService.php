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
        if (empty($channelIds)) {
            return;
        }

        $channels = NotificationChannel::whereIn('id', $channelIds)
            ->where('is_active', true)
            ->get();

        foreach ($channels as $channel) {
            match ($channel->type) {
                'telegram'  => $this->sendTelegram($channel, $htmlMsg),
                'whatsapp'  => $this->sendFonnte($channel, $plainMsg),
                'webhook'   => $this->sendWebhook($channel, $monitor, $event, $plainMsg),
                default     => null,
            };
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
        $message = "✅ *Test Notifikasi WatchTower*\nChannel: {$channel->name}\nWaktu: " . now()->format('d-m-Y H:i:s') . "\nJika pesan ini diterima, konfigurasi sudah benar.";

        try {
            if ($channel->type === 'telegram') {
                $payload = $this->parseTelegramTarget($channel->target);
                $payload['text']       = $message;
                $payload['parse_mode'] = 'HTML';
                $res = Http::timeout(10)->post("https://api.telegram.org/bot{$channel->token}/sendMessage", $payload);
                return ['ok' => $res->ok(), 'body' => $res->json()];
            }

            if ($channel->type === 'whatsapp') {
                $res = Http::withHeaders(['Authorization' => $channel->token])
                    ->timeout(15)
                    ->asForm()
                    ->post('https://api.fonnte.com/send', [
                        'target'      => $channel->target,
                        'message'     => $message,
                        'countryCode' => '62',
                    ]);
                return ['ok' => $res->ok() && $res->json('status') !== false, 'body' => $res->json()];
            }

            if ($channel->type === 'webhook') {
                $payload = json_encode(['event' => 'test', 'message' => $message, 'timestamp' => now()->toIso8601String()]);
                $headers = ['Content-Type' => 'application/json', 'User-Agent' => 'WatchTower/1.0'];
                if (!empty($channel->token)) {
                    $headers['X-WatchTower-Signature'] = 'sha256=' . hash_hmac('sha256', $payload, $channel->token);
                }
                $res = Http::withHeaders($headers)->timeout(10)->withBody($payload, 'application/json')->post($channel->target);
                return ['ok' => $res->ok(), 'body' => ['status_code' => $res->status()]];
            }
        } catch (\Throwable $e) {
            return ['ok' => false, 'body' => ['error' => $e->getMessage()]];
        }

        return ['ok' => false, 'body' => ['error' => 'Unknown channel type']];
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
