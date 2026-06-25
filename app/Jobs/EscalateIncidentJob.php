<?php

namespace App\Jobs;

use App\Models\AppSetting;
use App\Models\EscalationRule;
use App\Models\Incident;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EscalateIncidentJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 1;
    public int $timeout = 60;

    public function __construct(
        public readonly int $incidentId,
        public readonly int $ruleId,
    ) {}

    public function handle(): void
    {
        $incident = Incident::find($this->incidentId);
        $rule     = EscalationRule::with('channel')->find($this->ruleId);

        if (!$incident || !$rule || !$rule->is_active) {
            return;
        }

        // Only escalate if incident still open
        if ($incident->status !== 'open') {
            return;
        }

        $monitor  = $incident->monitor;
        $duration = '-';
        $s        = now()->diffInSeconds($incident->started_at);
        $duration = ($s >= 3600 ? floor($s / 3600) . 'j ' : '')
                  . ($s >= 60   ? floor(($s % 3600) / 60) . 'm ' : '')
                  . ($s % 60) . 'd';

        $vars = [
            '{name}'          => $monitor?->name ?? '(Monitor dihapus)',
            '{url}'           => $monitor?->url ?? '-',
            '{duration}'      => trim($duration),
            '{rule}'          => $rule->name,
            '{timestamp}'     => now()->format('d-m-Y H:i:s'),
        ];

        $defaults = AppSetting::defaults();
        $htmlMsg  = str_replace(
            array_keys($vars), array_values($vars),
            AppSetting::get('notif_escalation_body', $defaults['notif_escalation_body'])
        );

        $channel = $rule->channel;
        if (!$channel || !$channel->is_active) {
            return;
        }

        $notifier = app(NotificationService::class);

        try {
            match ($channel->type) {
                'telegram' => $this->sendTelegram($channel, $htmlMsg),
                'whatsapp' => $this->sendFonnte($channel, strip_tags($htmlMsg)),
                'webhook'  => $this->sendWebhook($channel, $htmlMsg, $monitor),
                default    => null,
            };
        } catch (\Throwable $e) {
            Log::error("EscalateIncidentJob failed: {$e->getMessage()}");
        }
    }

    private function sendTelegram($channel, string $msg): void
    {
        $payload = $this->parseTelegramTarget($channel->target);
        $payload['text']       = $msg;
        $payload['parse_mode'] = 'HTML';
        Http::timeout(10)->post("https://api.telegram.org/bot{$channel->token}/sendMessage", $payload);
    }

    private function parseTelegramTarget(string $target): array
    {
        if (str_contains($target, ':')) {
            [$chatId, $threadId] = explode(':', $target, 2);
            return ['chat_id' => $chatId, 'message_thread_id' => (int) $threadId];
        }
        return ['chat_id' => $target];
    }

    private function sendFonnte($channel, string $msg): void
    {
        Http::withHeaders(['Authorization' => $channel->token])
            ->timeout(15)->asForm()
            ->post('https://api.fonnte.com/send', [
                'target'      => $channel->target,
                'message'     => $msg,
                'countryCode' => '62',
            ]);
    }

    private function sendWebhook($channel, string $msg, $monitor): void
    {
        $payload = json_encode([
            'event'     => 'escalation',
            'message'   => $msg,
            'monitor'   => $monitor ? ['id' => $monitor->id, 'name' => $monitor->name] : null,
            'timestamp' => now()->toIso8601String(),
        ]);
        $headers = ['Content-Type' => 'application/json', 'User-Agent' => 'WatchTower/1.0'];
        if (!empty($channel->token)) {
            $headers['X-WatchTower-Signature'] = 'sha256=' . hash_hmac('sha256', $payload, $channel->token);
        }
        Http::withHeaders($headers)->withBody($payload, 'application/json')->timeout(10)->post($channel->target);
    }
}
