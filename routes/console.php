<?php

use App\Models\AppSetting;
use App\Models\Setting;
use Illuminate\Support\Facades\Schedule;

Schedule::command('monitor:check')->everyMinute();
Schedule::command('monitor:check-cron')->everyMinute();
Schedule::command('monitor:ssl-check')->twiceDaily(8, 20);
Schedule::command('monitor:check-domain-expiry')->dailyAt('03:00');
Schedule::command('monitor:auto-close-incidents')->everyFiveMinutes();

// Laporan otomatis — konfigurasi dari AppSetting
try {
    if (AppSetting::get('report_enabled', '0')) {
        $reportTime = AppSetting::get('report_time', '07:00');
        if (AppSetting::get('report_daily', '0')) {
            Schedule::command('monitor:report --period=daily')->dailyAt($reportTime);
        }
        if (AppSetting::get('report_weekly', '0')) {
            Schedule::command('monitor:report --period=weekly')->weeklyOn(1, $reportTime);
        }
    }
} catch (\Throwable) {
    // AppSetting table may not exist yet
}

// BPJS interval dibaca dari settings — default 10 menit jika tabel belum ada
try {
    $bpjsEnabled  = (bool) Setting::get('bpjs_auto_check', 1);
    $bpjsInterval = (int)  Setting::get('bpjs_check_interval', 10);
    $bpjsInterval = max(5, min(1440, $bpjsInterval));
} catch (\Throwable) {
    $bpjsEnabled  = true;
    $bpjsInterval = 10;
}

if ($bpjsEnabled) {
    $cron = $bpjsInterval % 60 === 0
        ? "0 */" . ($bpjsInterval / 60) . " * * *"
        : "*/{$bpjsInterval} * * * *";

    Schedule::command('api:health-check')->cron($cron);
}
