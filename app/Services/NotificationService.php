<?php

namespace App\Services;

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

        $this->send($monitor, 'monitor.ssl_expiry', $plain,
            telegramMsg: "⚠️ <b>SSL Cert akan kedaluwarsa!</b>\n"
                . "Monitor: {$monitor->name}\nURL: {$monitor->url}\n"
                . "Sisa: <b>{$days} hari</b> (expire: {$expiry})",
            whatsappMsg: "⚠️ *SSL Cert akan kedaluwarsa!*\n"
                . "Monitor: {$monitor->name}\nURL: {$monitor->url}\n"
                . "Sisa: *{$days} hari* (expire: {$expiry})"
        );
    }

    // ─── private ──────────────────────────────────────────────────────────────

    private function dispatch(Monitor $monitor, string $status): void
    {
        $emoji = $status === 'DOWN' ? '🔴' : '🟢';
        $time  = now()->format('d-m-Y H:i:s');

        $plain = "{$emoji} {$monitor->name} is {$status}\nURL: {$monitor->url}\nWaktu: {$time}";

        $this->send(
            $monitor,
            'monitor.' . strtolower($status),
            $plain,
            telegramMsg: "{$emoji} <b>{$monitor->name}</b> is <b>{$status}</b>\nURL: {$monitor->url}\nWaktu: {$time}",
            whatsappMsg: "{$emoji} *{$monitor->name}* is *{$status}*\nURL: {$monitor->url}\nWaktu: {$time}"
        );
    }

    private function send(Monitor $monitor, string $event, string $plain, string $telegramMsg, string $whatsappMsg): void
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
                'telegram'  => $this->sendTelegram($channel, $telegramMsg),
                'whatsapp'  => $this->sendFonnte($channel, $whatsappMsg),
                'webhook'   => $this->sendWebhook($channel, $monitor, $event, $plain),
                default     => null,
            };
        }
    }

    private function sendTelegram(NotificationChannel $channel, string $message): void
    {
        try {
            Http::post("https://api.telegram.org/bot{$channel->token}/sendMessage", [
                'chat_id'    => $channel->target,
                'text'       => $message,
                'parse_mode' => 'HTML',
            ]);
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
                $res = Http::post("https://api.telegram.org/bot{$channel->token}/sendMessage", [
                    'chat_id'    => $channel->target,
                    'text'       => $message,
                    'parse_mode' => 'MarkdownV2',
                ]);
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
