<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitorLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class UptimeChecker
{
    public function check(Monitor $monitor): array
    {
        $result = match ($monitor->type) {
            'ping'         => $this->checkPing($monitor),
            'keyword'      => $this->checkHttp($monitor, keyword: true),
            'tcp'          => $this->checkTcp($monitor),
            'dns'          => $this->checkDns($monitor),
            'push'         => $this->checkPush($monitor),
            'cron'         => $this->checkCron($monitor),
            'database'     => $this->checkDatabase($monitor),
            'docker'       => $this->checkDocker($monitor),
            'whois'        => $this->checkWhois($monitor),
            default        => $this->checkHttp($monitor),
        };

        // Suppress false positives via regex
        if ($result['status'] === 'down' && $monitor->suppress_pattern && $result['message']) {
            try {
                if (preg_match('/' . $monitor->suppress_pattern . '/i', $result['message'])) {
                    $result['status']  = 'up';
                    $result['message'] = '[suppressed] ' . $result['message'];
                }
            } catch (\Throwable) {}
        }

        // Response size check
        if ($result['status'] === 'up' && isset($result['response_size'])) {
            $size = $result['response_size'];
            if ($monitor->min_response_size && $size < $monitor->min_response_size) {
                $result['status']  = 'down';
                $result['message'] = "Response terlalu kecil: {$size} bytes (min: {$monitor->min_response_size})";
            } elseif ($monitor->max_response_size && $size > $monitor->max_response_size) {
                $result['status']  = 'down';
                $result['message'] = "Response terlalu besar: {$size} bytes (max: {$monitor->max_response_size})";
            }
        }

        // Slow flag
        $result['is_slow'] = $monitor->response_time_warning
            && $result['response_time'] > $monitor->response_time_warning
            && $result['status'] === 'up';

        // Latency trend alert (5 consecutive rising)
        if ($result['status'] === 'up' && $monitor->latency_trend_alert) {
            $result['latency_trending'] = $this->detectLatencyTrend($monitor, $result['response_time']);
        }

        return $result;
    }

    private function checkHttp(Monitor $monitor, bool $keyword = false): array
    {
        $start = microtime(true);

        try {
            $pending = Http::timeout($monitor->timeout ?? 10);

            // TLS
            if ($monitor->ignore_tls_error) {
                $pending = $pending->withoutVerifying();
            }

            // Redirects
            if (!$monitor->follow_redirects) {
                $pending = $pending->withOptions(['allow_redirects' => false]);
            } elseif ($monitor->max_redirects) {
                $pending = $pending->withOptions(['allow_redirects' => ['max' => $monitor->max_redirects]]);
            }

            // User-Agent
            $ua = $monitor->custom_user_agent ?: 'WatchTower/2.0 Uptime Monitor';
            $pending = $pending->withHeaders(['User-Agent' => $ua]);

            // Custom headers
            if ($monitor->custom_headers) {
                $pending = $pending->withHeaders($monitor->custom_headers);
            }

            // Auth
            if ($monitor->auth_type === 'basic' && $monitor->auth_username) {
                $pending = $pending->withBasicAuth($monitor->auth_username, $monitor->auth_password ?? '');
            } elseif ($monitor->auth_type === 'bearer' && $monitor->auth_password) {
                $pending = $pending->withToken($monitor->auth_password);
            }

            // Proxy
            if ($monitor->proxy_url) {
                $pending = $pending->withOptions(['proxy' => $monitor->proxy_url]);
            }

            // Method + body
            $method = strtolower($monitor->http_method ?: 'GET');
            $url    = $monitor->url;
            $body   = $monitor->request_body;

            $response = match ($method) {
                'post'   => $pending->withBody($body ?? '', 'application/json')->post($url),
                'put'    => $pending->withBody($body ?? '', 'application/json')->put($url),
                'patch'  => $pending->withBody($body ?? '', 'application/json')->patch($url),
                'delete' => $pending->delete($url),
                'head'   => $pending->head($url),
                default  => $pending->get($url),
            };

            $responseTime = (int) ((microtime(true) - $start) * 1000);
            $httpStatus   = (string) $response->status();
            $responseBody = $response->body();
            $responseSize = strlen($responseBody);

            // Accepted status codes
            $accepted = $monitor->accepted_status_codes
                ? array_map('trim', explode(',', $monitor->accepted_status_codes))
                : null;

            $statusOk = $accepted
                ? in_array($httpStatus, $accepted)
                : ((int)$httpStatus >= 100);

            // Keyword check
            if ($keyword && $statusOk) {
                $kw = $monitor->keyword ?? '';
                if ($kw !== '' && !str_contains($responseBody, $kw)) {
                    return $this->buildResult('down', $responseTime, $httpStatus,
                        "Keyword \"{$kw}\" tidak ditemukan", $responseSize);
                }
            }

            // Body assertion
            if ($statusOk && $monitor->body_assertion_path) {
                [$assertOk, $assertMsg] = $this->checkBodyAssertion($monitor, $responseBody);
                if (!$assertOk) {
                    return $this->buildResult('down', $responseTime, $httpStatus, $assertMsg, $responseSize);
                }
            }

            return $this->buildResult(
                $statusOk ? 'up' : 'down',
                $responseTime,
                $httpStatus,
                $statusOk ? null : "HTTP {$httpStatus}",
                $responseSize
            );

        } catch (\Throwable $e) {
            return $this->buildResult('down', (int) ((microtime(true) - $start) * 1000), null, $e->getMessage());
        }
    }

    private function checkBodyAssertion(Monitor $monitor, string $body): array
    {
        $path  = $monitor->body_assertion_path;
        $op    = $monitor->body_assertion_op ?: 'equals';
        $expected = $monitor->body_assertion_value ?? '';

        // Try JSON path ($.key.nested)
        try {
            $data  = json_decode($body, true);
            $keys  = explode('.', ltrim($path, '$.'));
            $value = $data;
            foreach ($keys as $k) {
                if (!isset($value[$k])) { $value = null; break; }
                $value = $value[$k];
            }
            $actual = is_array($value) ? json_encode($value) : (string)($value ?? '');
        } catch (\Throwable) {
            $actual = $body;
        }

        $ok = match ($op) {
            'contains'     => str_contains($actual, $expected),
            'not_contains' => !str_contains($actual, $expected),
            default        => $actual === $expected,
        };

        return [$ok, $ok ? '' : "Assertion gagal ({$op}): expected '{$expected}', got '{$actual}'"];
    }

    private function checkTcp(Monitor $monitor): array
    {
        $host  = $monitor->tcp_host ?: (parse_url($monitor->url, PHP_URL_HOST) ?: $monitor->url);
        $port  = (int) ($monitor->tcp_port ?? 80);
        $start = microtime(true);

        $conn = @fsockopen($host, $port, $errno, $errstr, $monitor->timeout ?? 10);
        $responseTime = (int) ((microtime(true) - $start) * 1000);

        if ($conn) { fclose($conn); return $this->buildResult('up', $responseTime, null, null); }
        return $this->buildResult('down', $responseTime, null, "TCP {$host}:{$port} — {$errstr}");
    }

    private function checkDns(Monitor $monitor): array
    {
        $domain   = $monitor->domain;
        $type     = strtoupper($monitor->dns_resolve_type ?? 'A');
        $expected = $monitor->dns_expected_value;
        $start    = microtime(true);

        $dnsConst = match ($type) {
            'AAAA'  => DNS_AAAA, 'CNAME' => DNS_CNAME, 'MX' => DNS_MX, default => DNS_A,
        };

        $records = @dns_get_record($domain, $dnsConst);
        if (!$records && $type === 'A') {
            $ipv4    = @gethostbynamel($domain);
            $records = $ipv4 ? array_map(fn($ip) => ['ip' => $ip], $ipv4) : [];
        }
        $responseTime = (int) ((microtime(true) - $start) * 1000);

        if (empty($records)) return $this->buildResult('down', $responseTime, null, "DNS {$type} tidak ditemukan");

        if ($expected) {
            $values  = array_map(fn($r) => $r['ip'] ?? $r['ipv6'] ?? $r['target'] ?? $r['host'] ?? '', $records);
            $matched = in_array($expected, $values);
            return $this->buildResult($matched ? 'up' : 'down', $responseTime, null,
                $matched ? null : "Expected {$expected}, got: " . implode(', ', array_filter($values)));
        }
        return $this->buildResult('up', $responseTime, null, null);
    }

    private function checkPush(Monitor $monitor): array
    {
        $interval = $monitor->check_interval;
        $lastPush = $monitor->last_push_at;
        if (!$lastPush) return $this->buildResult('pending', 0, null, 'Belum ada heartbeat');
        $mins = $lastPush->diffInMinutes(now());
        $isUp = $mins <= ($interval * 2);
        return $this->buildResult($isUp ? 'up' : 'down', 0, null,
            $isUp ? "Heartbeat {$mins}m lalu" : "Tidak ada heartbeat {$mins}m (threshold: " . ($interval*2) . "m)");
    }

    private function checkCron(Monitor $monitor): array
    {
        $interval    = $monitor->heartbeat_interval ?? 60;
        $lastBeat    = $monitor->last_heartbeat_at;
        if (!$lastBeat) return $this->buildResult('pending', 0, null, 'Menunggu heartbeat pertama');
        $mins = $lastBeat->diffInMinutes(now());
        $isUp = $mins <= ($interval + max(1, (int)($interval * 0.1)));
        return $this->buildResult($isUp ? 'up' : 'down', 0, null,
            $isUp ? "Heartbeat {$mins}m lalu" : "Heartbeat overdue {$mins}m (expected: {$interval}m)");
    }

    private function checkDatabase(Monitor $monitor): array
    {
        $url   = $monitor->url; // e.g. mysql://user:pass@host:3306/db or redis://host:6379
        $start = microtime(true);
        try {
            $parsed = parse_url($url);
            $scheme = strtolower($parsed['scheme'] ?? 'mysql');

            if ($scheme === 'redis') {
                $host = $parsed['host'] ?? '127.0.0.1';
                $port = $parsed['port'] ?? 6379;
                $sock = @fsockopen($host, $port, $errno, $errstr, $monitor->timeout ?? 5);
                $rt   = (int) ((microtime(true) - $start) * 1000);
                if (!$sock) return $this->buildResult('down', $rt, null, "Redis {$errstr}");
                fwrite($sock, "PING\r\n");
                $resp = fgets($sock, 128);
                fclose($sock);
                return $this->buildResult(str_contains($resp, 'PONG') ? 'up' : 'down', $rt, null,
                    str_contains($resp, 'PONG') ? null : 'Redis: unexpected response');
            }

            // MySQL/PostgreSQL via PDO
            $host   = $parsed['host'] ?? '127.0.0.1';
            $port   = $parsed['port'] ?? ($scheme === 'pgsql' ? 5432 : 3306);
            $dbname = ltrim($parsed['path'] ?? '/test', '/');
            $user   = $parsed['user'] ?? 'root';
            $pass   = $parsed['pass'] ?? '';
            $dsn    = "{$scheme}:host={$host};port={$port};dbname={$dbname}";
            $pdo    = new \PDO($dsn, $user, $pass, [\PDO::ATTR_TIMEOUT => $monitor->timeout ?? 5]);
            $pdo->query('SELECT 1');
            $rt = (int) ((microtime(true) - $start) * 1000);
            return $this->buildResult('up', $rt, null, null);
        } catch (\Throwable $e) {
            return $this->buildResult('down', (int) ((microtime(true) - $start) * 1000), null, $e->getMessage());
        }
    }

    private function checkDocker(Monitor $monitor): array
    {
        // Docker socket or remote API: url = unix:///var/run/docker.sock|container_name
        // or http://docker-host:2375/containers/{name}/json
        $start = microtime(true);
        try {
            $url  = $monitor->url; // http://host:2375/containers/name/json
            $resp = Http::timeout($monitor->timeout ?? 5)->get($url);
            $rt   = (int) ((microtime(true) - $start) * 1000);
            if (!$resp->ok()) return $this->buildResult('down', $rt, (string)$resp->status(), 'Docker API error');
            $data   = $resp->json();
            $state  = $data['State']['Status'] ?? 'unknown';
            $running = $data['State']['Running'] ?? false;
            return $this->buildResult($running ? 'up' : 'down', $rt, null,
                $running ? "Container: {$state}" : "Container: {$state}");
        } catch (\Throwable $e) {
            return $this->buildResult('down', (int) ((microtime(true) - $start) * 1000), null, $e->getMessage());
        }
    }

    private function checkWhois(Monitor $monitor): array
    {
        $domain = $monitor->domain;
        $start  = microtime(true);
        try {
            $tld    = explode('.', $domain);
            $tld    = end($tld);
            $server = "whois.iana.org";
            $sock   = @fsockopen($server, 43, $errno, $errstr, $monitor->timeout ?? 10);
            $rt     = (int) ((microtime(true) - $start) * 1000);
            if (!$sock) return $this->buildResult('down', $rt, null, "WHOIS server unreachable");
            fwrite($sock, $domain . "\r\n");
            $response = '';
            while (!feof($sock)) $response .= fgets($sock, 1024);
            fclose($sock);

            // Parse expiry date
            $expiry = null;
            foreach (['Expiry Date:', 'Expiration Date:', 'Registry Expiry Date:', 'paid-till:'] as $key) {
                if (preg_match('/' . preg_quote($key, '/') . '\s*(.+)/i', $response, $m)) {
                    try { $expiry = new \Carbon\Carbon(trim($m[1])); } catch (\Throwable) {}
                    break;
                }
            }

            if ($expiry) {
                $days = (int) now()->diffInDays($expiry, false);
                $monitor->update([
                    'domain_expiry_at'            => $expiry->toDateString(),
                    'domain_expiry_days_remaining' => max(0, $days),
                ]);
                $alertDays = $monitor->domain_expiry_alert_days ?? 30;
                if ($days <= 0) return $this->buildResult('down', $rt, null, "Domain expired {$expiry->format('d-m-Y')}");
                if ($days <= $alertDays) return $this->buildResult('down', $rt, null, "Domain expire {$days}h lagi ({$expiry->format('d-m-Y')})");
                return $this->buildResult('up', $rt, null, "Domain expire {$days}h lagi");
            }
            return $this->buildResult('up', $rt, null, 'Expiry date tidak ditemukan di WHOIS');
        } catch (\Throwable $e) {
            return $this->buildResult('down', (int)((microtime(true) - $start) * 1000), null, $e->getMessage());
        }
    }

    private function checkPing(Monitor $monitor): array
    {
        $host  = parse_url($monitor->url, PHP_URL_HOST) ?: $monitor->url;
        $start = microtime(true);
        $isWin = PHP_OS_FAMILY === 'Windows';
        $cmd   = $isWin ? "ping -n 1 -w {$monitor->timeout}000 {$host}" : "ping -c 1 -W {$monitor->timeout} {$host}";
        exec($cmd, $out, $code);
        $rt = (int) ((microtime(true) - $start) * 1000);
        if ($isWin && $code === 0) {
            foreach ($out as $line) {
                if (preg_match('/time[=<](\d+)ms/i', $line, $m)) { $rt = (int)$m[1]; break; }
            }
        }
        return $this->buildResult($code === 0 ? 'up' : 'down', $rt, null, $code === 0 ? null : 'Ping timeout');
    }

    private function detectLatencyTrend(Monitor $monitor, int $currentRt): bool
    {
        $recent = MonitorLog::where('monitor_id', $monitor->id)
            ->where('status', 'up')
            ->latest('checked_at')
            ->limit(5)
            ->pluck('response_time')
            ->toArray();

        if (count($recent) < 4) return false;
        array_unshift($recent, $currentRt);
        for ($i = 0; $i < count($recent) - 1; $i++) {
            if ($recent[$i] <= $recent[$i + 1]) return false;
        }
        return true;
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
        $this->updateHealthScore($monitor);
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
            if ($total === 0) continue;
            $up            = (clone $q)->where('status', 'up')->count();
            $updates[$col] = round(($up / $total) * 100, 2);
        }
        if (!empty($updates)) $monitor->update($updates);
    }

    private function updateHealthScore(Monitor $monitor): void
    {
        $uptime30  = (float) ($monitor->uptime_30d ?? 100);
        $avgRt     = MonitorLog::where('monitor_id', $monitor->id)
            ->where('status', 'up')
            ->where('checked_at', '>=', now()->subDays(7))
            ->avg('response_time') ?? 0;
        $incidents = \App\Models\Incident::where('monitor_id', $monitor->id)
            ->where('started_at', '>=', now()->subDays(30))
            ->count();

        // Score: 60% uptime, 25% RT, 15% incident frequency
        $uptimeScore    = $uptime30 * 0.60;
        $rtScore        = max(0, 25 - ($avgRt / 200));
        $incidentScore  = max(0, 15 - ($incidents * 3));
        $score          = (int) min(100, $uptimeScore + $rtScore + $incidentScore);

        $monitor->update(['health_score' => $score]);
    }

    private function buildResult(string $status, int $responseTime, ?string $httpStatus, ?string $message, ?int $size = null): array
    {
        return [
            'status'        => $status,
            'response_time' => $responseTime,
            'http_status'   => $httpStatus,
            'message'       => $message,
            'response_size' => $size,
            'responseTime'  => $responseTime,
            'httpStatus'    => $httpStatus,
        ];
    }
}
