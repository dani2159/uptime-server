<?php

namespace App\Services;

use App\Models\PagespeedCheck;
use App\Models\PagespeedMonitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PagespeedService
{
    const API_BASE = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';

    public function check(PagespeedMonitor $monitor): PagespeedCheck
    {
        // Build URL manually — Google API needs repeated params: category=perf&category=seo
        // Laravel Http::get() sends arrays as category[0]=perf which Google ignores
        $query = http_build_query([
            'url'      => $monitor->url,
            'strategy' => $monitor->strategy,
        ]);
        $query .= '&category=performance&category=accessibility&category=best-practices&category=seo';
        if ($monitor->api_key) {
            $query .= '&key=' . urlencode($monitor->api_key);
        }
        $url = self::API_BASE . '?' . $query;

        try {
            $response = Http::timeout(60)->get($url);

            if (! $response->successful()) {
                return $this->errorCheck($monitor, 'API error: HTTP ' . $response->status());
            }

            $data = $response->json();

            $cats   = $data['lighthouseResult']['categories'] ?? [];
            $audits = $data['lighthouseResult']['audits'] ?? [];

            $perf  = isset($cats['performance']['score'])     ? (int) round($cats['performance']['score'] * 100)     : null;
            $a11y  = isset($cats['accessibility']['score'])   ? (int) round($cats['accessibility']['score'] * 100)   : null;
            $bp    = isset($cats['best-practices']['score'])  ? (int) round($cats['best-practices']['score'] * 100)  : null;
            $seo   = isset($cats['seo']['score'])             ? (int) round($cats['seo']['score'] * 100)             : null;

            $cls   = isset($audits['cumulative-layout-shift']['numericValue'])
                ? round($audits['cumulative-layout-shift']['numericValue'], 3) : null;
            $si    = isset($audits['speed-index']['numericValue'])
                ? round($audits['speed-index']['numericValue'] / 1000, 2) : null;
            $fcp   = isset($audits['first-contentful-paint']['numericValue'])
                ? round($audits['first-contentful-paint']['numericValue'] / 1000, 2) : null;
            $lcp   = isset($audits['largest-contentful-paint']['numericValue'])
                ? round($audits['largest-contentful-paint']['numericValue'] / 1000, 2) : null;
            $tbt   = isset($audits['total-blocking-time']['numericValue'])
                ? (int) round($audits['total-blocking-time']['numericValue']) : null;

            return PagespeedCheck::create([
                'pagespeed_monitor_id' => $monitor->id,
                'performance_score'    => $perf,
                'accessibility_score'  => $a11y,
                'best_practices_score' => $bp,
                'seo_score'            => $seo,
                'cls'                  => $cls,
                'speed_index'          => $si,
                'fcp'                  => $fcp,
                'lcp'                  => $lcp,
                'tbt'                  => $tbt,
                'checked_at'           => now(),
            ]);

        } catch (\Throwable $e) {
            Log::error('PagespeedService error: ' . $e->getMessage(), ['monitor' => $monitor->id]);
            return $this->errorCheck($monitor, $e->getMessage());
        }
    }

    private function errorCheck(PagespeedMonitor $monitor, string $message): PagespeedCheck
    {
        return PagespeedCheck::create([
            'pagespeed_monitor_id' => $monitor->id,
            'error_message'        => $message,
            'checked_at'           => now(),
        ]);
    }
}
