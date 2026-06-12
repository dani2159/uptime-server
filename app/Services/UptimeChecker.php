<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitorLog;

class UptimeChecker
{
    public function check(Monitor $monitor): array
    {
        return match ($monitor->type) {
            'ping'    => $this->checkPing($monitor),
            'keyword' => $this->checkKeyword($monitor),
            'tcp'     => $this->checkTcp($monitor),
            'dns'     => $this->checkDns($monitor),
            'push'    => $this->checkPush($monitor),
            default   => $this->checkHttp($monitor),
        };
    }

    private function checkHttp(Monitor $monitor): array
    {
        $start   = microtime(true);
        $context = stream_context_create([
            'http' => ['timeout' => $monitor->timeout, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        try {
            $headers      = @get_headers($monitor->url, true, $context);
            $responseTime = (int) ((microtime(true) - $start) * 1000);

            if ($headers === false) {
                return $this->buildResult('down', $responseTime, null, 'Connection failed');
            }

            $statusLine = is_array($headers[0]) ? end($headers[0]) : $headers[0];
            preg_match('/(\d{3})/', $statusLine, $m);
            $httpStatus = $m[1] ?? null;

            // Up = server merespons apapun; Down = koneksi gagal total
            return $this->buildResult(
                $httpStatus !== null ? 'up' : 'down',
                $responseTime,
                $httpStatus,
                $httpStatus !== null ? null : 'No response'
            );
        } catch (\Throwable $e) {
            return $this->buildResult('down', (int) ((microtime(true) - $start) * 1000), null, $e->getMessage());
        }
    }

    private function checkKeyword(Monitor $monitor): array
    {
        $start   = microtime(true);
        $context = stream_context_create([
            'http' => ['timeout' => $monitor->timeout, 'ignore_errors' => true],
            'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        try {
            $body         = @file_get_contents($monitor->url, false, $context);
            $responseTime = (int) ((microtime(true) - $start) * 1000);

            if ($body === false) {
                return $this->buildResult('down', $responseTime, null, 'Connection failed');
            }

            $keyword = $monitor->keyword ?? '';
            $found   = $keyword !== '' && str_contains($body, $keyword);

            // Parse status dari $http_response_header global yang di-set file_get_contents
            $httpStatus = null;
            if (!empty($http_response_header)) {
                preg_match('/(\d{3})/', $http_response_header[0], $m);
                $httpStatus = $m[1] ?? null;
            }

            return $this->buildResult(
                $found ? 'up' : 'down',
                $responseTime,
                $httpStatus,
                $found ? null : "Keyword \"{$keyword}\" tidak ditemukan"
            );
        } catch (\Throwable $e) {
            return $this->buildResult('down', (int) ((microtime(true) - $start) * 1000), null, $e->getMessage());
        }
    }

    private function checkTcp(Monitor $monitor): array
    {
        $host  = $monitor->tcp_host ?: (parse_url($monitor->url, PHP_URL_HOST) ?: $monitor->url);
        $port  = (int) ($monitor->tcp_port ?? 80);
        $start = microtime(true);

        $conn = @fsockopen($host, $port, $errno, $errstr, $monitor->timeout);
        $responseTime = (int) ((microtime(true) - $start) * 1000);

        if ($conn) {
            fclose($conn);
            return $this->buildResult('up', $responseTime, null, null);
        }

        return $this->buildResult('down', $responseTime, null, "TCP {$host}:{$port} — {$errstr}");
    }

    private function checkDns(Monitor $monitor): array
    {
        $domain   = $monitor->domain;
        $type     = strtoupper($monitor->dns_resolve_type ?? 'A');
        $expected = $monitor->dns_expected_value;
        $start    = microtime(true);

        $dnsConst = match ($type) {
            'AAAA'  => DNS_AAAA,
            'CNAME' => DNS_CNAME,
            'MX'    => DNS_MX,
            default => DNS_A,
        };

        $records = @dns_get_record($domain, $dnsConst);

        if (!$records && $type === 'A') {
            $ipv4 = @gethostbynamel($domain);
            $records = $ipv4 ? array_map(fn($ip) => ['ip' => $ip], $ipv4) : [];
        }

        $responseTime = (int) ((microtime(true) - $start) * 1000);

        if (empty($records)) {
            return $this->buildResult('down', $responseTime, null, "DNS {$type} untuk {$domain} tidak ditemukan");
        }

        // Jika ada expected value, cocokkan
        if ($expected) {
            $values = array_map(fn($r) => $r['ip'] ?? $r['ipv6'] ?? $r['target'] ?? $r['host'] ?? '', $records);
            $matched = in_array($expected, $values);
            return $this->buildResult(
                $matched ? 'up' : 'down',
                $responseTime,
                null,
                $matched ? null : "Expected {$expected}, got: " . implode(', ', array_filter($values))
            );
        }

        return $this->buildResult('up', $responseTime, null, implode(', ', array_slice(
            array_map(fn($r) => $r['ip'] ?? $r['ipv6'] ?? $r['target'] ?? '', $records), 0, 3
        )));
    }

    private function checkPush(Monitor $monitor): array
    {
        // Push: cek apakah heartbeat terakhir masih dalam interval yang diharapkan
        $interval     = $monitor->check_interval; // menit
        $lastPush     = $monitor->last_push_at;
        $responseTime = 0;

        if (!$lastPush) {
            return $this->buildResult('pending', $responseTime, null, 'Belum ada heartbeat diterima');
        }

        $minutesSince = $lastPush->diffInMinutes(now());
        $isUp         = $minutesSince <= ($interval * 2); // toleransi 2x interval

        return $this->buildResult(
            $isUp ? 'up' : 'down',
            $responseTime,
            null,
            $isUp
                ? "Heartbeat {$minutesSince} menit lalu"
                : "Tidak ada heartbeat selama {$minutesSince} menit (threshold: " . ($interval * 2) . " menit)"
        );
    }

    private function checkPing(Monitor $monitor): array
    {
        $host      = parse_url($monitor->url, PHP_URL_HOST) ?: $monitor->url;
        $start     = microtime(true);
        $isWindows = PHP_OS_FAMILY === 'Windows';
        $timeout   = $monitor->timeout;
        $command   = $isWindows
            ? "ping -n 1 -w {$timeout}000 {$host}"
            : "ping -c 1 -W {$timeout} {$host}";

        exec($command, $output, $exitCode);
        $responseTime = (int) ((microtime(true) - $start) * 1000);

        if ($isWindows && $exitCode === 0) {
            foreach ($output as $line) {
                if (preg_match('/time[=<](\d+)ms/i', $line, $m)) {
                    $responseTime = (int) $m[1];
                    break;
                }
            }
        }

        return $this->buildResult(
            $exitCode === 0 ? 'up' : 'down',
            $responseTime,
            null,
            $exitCode === 0 ? null : 'Ping timeout'
        );
    }

    public function saveResult(Monitor $monitor, array $result): void
    {
        MonitorLog::create([
            'monitor_id'    => $monitor->id,
            'status'        => $result['status'],
            'response_time' => $result['response_time'],
            'http_status'   => $result['http_status'],
            'message'       => $result['message'],
            'checked_at'    => now(),
        ]);

        $monitor->update([
            'last_status'        => $result['status'],
            'last_response_time' => $result['response_time'],
            'last_checked_at'    => now(),
            'last_down_at'       => $result['status'] === 'down' ? now() : $monitor->last_down_at,
        ]);

        $this->recalculateUptime($monitor);
    }

    private function recalculateUptime(Monitor $monitor): void
    {
        $periods = [
            'uptime_percentage' => null,
            'uptime_24h'        => now()->subDay(),
            'uptime_7d'         => now()->subDays(7),
            'uptime_30d'        => now()->subDays(30),
        ];

        $updates = [];
        foreach ($periods as $col => $since) {
            $q     = MonitorLog::where('monitor_id', $monitor->id);
            $q     = $since ? $q->where('checked_at', '>=', $since) : $q;
            $total = $q->count();
            if ($total === 0) {
                continue;
            }
            $up            = (clone $q)->where('status', 'up')->count();
            $updates[$col] = round(($up / $total) * 100, 2);
        }

        if (!empty($updates)) {
            $monitor->update($updates);
        }
    }

    private function buildResult(string $status, int $responseTime, ?string $httpStatus, ?string $message): array
    {
        return [
            'status'        => $status,
            'response_time' => $responseTime,
            'http_status'   => $httpStatus,
            'message'       => $message,
            // alias untuk kompatibilitas
            'responseTime'  => $responseTime,
            'httpStatus'    => $httpStatus,
        ];
    }
}
