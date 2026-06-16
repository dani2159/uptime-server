<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Schedule;

Schedule::command('monitor:check')->everyMinute();
Schedule::command('monitor:ssl-check')->twiceDaily(8, 20);

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
