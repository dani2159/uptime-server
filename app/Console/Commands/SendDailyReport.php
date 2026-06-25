<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Models\Incident;
use App\Models\Monitor;
use App\Models\NotificationChannel;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendDailyReport extends Command
{
    protected $signature   = 'monitor:report {--period=daily : daily or weekly}';
    protected $description = 'Kirim laporan uptime otomatis ke channel terkonfigurasi';

    public function handle(): int
    {
        if (!AppSetting::get('report_enabled', '0')) {
            $this->info('Laporan dinonaktifkan di Settings.');
            return 0;
        }

        $period  = $this->option('period');
        $setting = $period === 'weekly' ? 'report_weekly' : 'report_daily';
        if (!AppSetting::get($setting, '0')) {
            $this->info("Laporan {$period} dinonaktifkan.");
            return 0;
        }

        $channelIds = json_decode(AppSetting::get('report_channel_ids', '[]'), true);
        if (empty($channelIds)) {
            $this->warn('Tidak ada channel laporan terkonfigurasi.');
            return 0;
        }

        $since    = $period === 'weekly' ? now()->subWeek() : now()->subDay();
        $label    = $period === 'weekly' ? 'Mingguan' : 'Harian';
        $from     = $since->format('d-m-Y H:i');
        $to       = now()->format('d-m-Y H:i');

        $monitors = Monitor::with(['heartbeatLogs' => fn($q) => $q->where('created_at', '>=', $since)])->get();
        $total    = $monitors->count();
        $upCount  = 0;
        $downCount = 0;
        $slowCount = 0;
        $rows     = [];

        foreach ($monitors as $mon) {
            $logs     = $mon->heartbeatLogs;
            $upLogs   = $logs->where('status', 'up')->count();
            $allLogs  = $logs->count();
            $sla      = $allLogs > 0 ? round($upLogs / $allLogs * 100, 1) : 100;
            $status   = $mon->last_status ?? 'unknown';

            if ($status === 'up') $upCount++;
            if ($status === 'down') $downCount++;
            if ($mon->last_is_slow) $slowCount++;

            $rows[] = "• {$mon->name}: SLA {$sla}% | Status " . strtoupper($status);
        }

        $incidents = Incident::where('started_at', '>=', $since)->count();
        $openInc   = Incident::where('status', 'open')->count();

        $reportLines = array_merge([
            "📊 <b>Laporan {$label} WatchTower</b>",
            "Periode: {$from} → {$to}",
            "",
            "🖥 Total Monitor: {$total}",
            "🟢 UP: {$upCount} | 🔴 DOWN: {$downCount} | 🟡 Lambat: {$slowCount}",
            "⚠️ Insiden periode ini: {$incidents} (open: {$openInc})",
            "",
            "<b>Detail per Monitor:</b>",
        ], $rows);

        $htmlMsg  = implode("\n", $reportLines);
        $plainMsg = strip_tags($htmlMsg);

        $channels = NotificationChannel::whereIn('id', $channelIds)->where('is_active', true)->get();
        foreach ($channels as $channel) {
            try {
                match ($channel->type) {
                    'telegram' => $this->sendTelegram($channel, $htmlMsg),
                    'whatsapp' => $this->sendFonnte($channel, $plainMsg),
                    default    => null,
                };
            } catch (\Throwable $e) {
                Log::error("SendDailyReport channel {$channel->id}: {$e->getMessage()}");
            }
        }

        $this->info("Laporan {$label} terkirim ke " . $channels->count() . " channel.");
        return 0;
    }

    private function parseTelegramTarget(string $target): array
    {
        if (str_contains($target, ':')) {
            [$chatId, $threadId] = explode(':', $target, 2);
            return ['chat_id' => $chatId, 'message_thread_id' => (int) $threadId];
        }
        return ['chat_id' => $target];
    }

    private function sendTelegram($channel, string $msg): void
    {
        $payload = $this->parseTelegramTarget($channel->target);
        $payload['text']       = $msg;
        $payload['parse_mode'] = 'HTML';
        Http::timeout(10)->post("https://api.telegram.org/bot{$channel->token}/sendMessage", $payload);
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
}
