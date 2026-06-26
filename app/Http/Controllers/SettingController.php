<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SettingController extends Controller
{
    public function index()
    {
        $settings = [
            'bpjs_check_interval' => Setting::get('bpjs_check_interval', 10),
            'bpjs_auto_check'     => Setting::get('bpjs_auto_check', 1),
        ];

        $ipInfo = $this->fetchIpInfo();

        return view('settings.index', compact('settings', 'ipInfo'));
    }

    public function ipInfo()
    {
        Cache::forget('server_ip_info');
        return response()->json($this->fetchIpInfo());
    }

    private function fetchIpInfo(): array
    {
        return Cache::remember('server_ip_info', 300, function () {
            try {
                $res = Http::timeout(5)->get('http://ip-api.com/json?fields=status,query,isp,org,country,regionName,city');
                if ($res->ok() && $res->json('status') === 'success') {
                    return [
                        'ip'      => $res->json('query'),
                        'isp'     => $res->json('isp'),
                        'org'     => $res->json('org'),
                        'city'    => $res->json('city'),
                        'region'  => $res->json('regionName'),
                        'country' => $res->json('country'),
                        'error'   => null,
                    ];
                }
            } catch (\Throwable $e) {
                // fallback below
            }
            return ['ip' => null, 'isp' => null, 'org' => null, 'city' => null, 'region' => null, 'country' => null, 'error' => 'Gagal mengambil info IP'];
        });
    }

    public function notifications()
    {
        $defaults       = AppSetting::defaults();
        $down           = AppSetting::get('notif_down_body',       $defaults['notif_down_body']);
        $recovered      = AppSetting::get('notif_recovered_body',  $defaults['notif_recovered_body']);
        $slow           = AppSetting::get('notif_slow_body',       $defaults['notif_slow_body']);
        $escalation_tpl = AppSetting::get('notif_escalation_body', $defaults['notif_escalation_body']);
        return view('settings.notifications', compact('down', 'recovered', 'slow', 'escalation_tpl', 'defaults'));
    }

    public function saveNotifications(Request $request)
    {
        $request->validate([
            'notif_down_body'       => 'required|string|max:2000',
            'notif_recovered_body'  => 'required|string|max:2000',
            'notif_slow_body'       => 'nullable|string|max:2000',
            'notif_escalation_body' => 'nullable|string|max:2000',
        ]);

        AppSetting::set('notif_down_body',       $request->input('notif_down_body'));
        AppSetting::set('notif_recovered_body',  $request->input('notif_recovered_body'));
        if ($request->filled('notif_slow_body')) {
            AppSetting::set('notif_slow_body', $request->input('notif_slow_body'));
        }
        if ($request->filled('notif_escalation_body')) {
            AppSetting::set('notif_escalation_body', $request->input('notif_escalation_body'));
        }

        return back()->with('success', 'Template notifikasi berhasil disimpan.');
    }

    public function saveReportSettings(Request $request)
    {
        $request->validate([
            'report_enabled'    => 'boolean',
            'report_daily'      => 'boolean',
            'report_weekly'     => 'boolean',
            'report_time'       => 'required|date_format:H:i',
            'report_channel_ids' => 'nullable|array',
            'report_channel_ids.*' => 'integer|exists:notification_channels,id',
        ]);

        AppSetting::set('report_enabled',    $request->boolean('report_enabled') ? '1' : '0');
        AppSetting::set('report_daily',      $request->boolean('report_daily')   ? '1' : '0');
        AppSetting::set('report_weekly',     $request->boolean('report_weekly')  ? '1' : '0');
        AppSetting::set('report_time',       $request->input('report_time', '07:00'));
        AppSetting::set('report_channel_ids', json_encode($request->input('report_channel_ids', [])));

        return back()->with('success', 'Pengaturan laporan disimpan.');
    }

    public function sendTestReport()
    {
        try {
            Artisan::call('monitor:report', ['--period' => 'daily']);
            $output = Artisan::output();
            return response()->json(['ok' => true, 'message' => trim($output) ?: 'Laporan terkirim.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'bpjs_check_interval' => 'required|integer|min:5|max:1440',
            'bpjs_auto_check'     => 'nullable|boolean',
        ]);

        Setting::set('bpjs_check_interval', $data['bpjs_check_interval']);
        Setting::set('bpjs_auto_check',     isset($data['bpjs_auto_check']) ? '1' : '0');

        // v2 advanced settings
        $v2 = $request->only([
            'notif_business_hours_only',
            'correlated_incident_threshold',
            'incident_auto_close_minutes',
            'telegram_bot_token',
            'telegram_chat_id',
        ]);
        foreach ($v2 as $key => $value) {
            if ($value !== null) \App\Models\AppSetting::set($key, $value);
        }
        \App\Models\AppSetting::set('notif_business_hours_only', $request->has('notif_business_hours_only') ? '1' : '0');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
