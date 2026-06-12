<?php

namespace App\Services;

use App\Models\Monitor;
use App\Models\MonitorIp;

class DnsResolver
{
    public function resolve(Monitor $monitor): array
    {
        $domain = $monitor->domain;

        // Skip DNS resolution for bare IP addresses
        if (filter_var($domain, FILTER_VALIDATE_IP)) {
            return [];
        }

        $resolved = [];

        $aRecords = @dns_get_record($domain, DNS_A);
        if ($aRecords) {
            foreach ($aRecords as $record) {
                $resolved[] = ['ip' => $record['ip'], 'type' => 'A'];
            }
        }

        $aaaaRecords = @dns_get_record($domain, DNS_AAAA);
        if ($aaaaRecords) {
            foreach ($aaaaRecords as $record) {
                $resolved[] = ['ip' => $record['ipv6'], 'type' => 'AAAA'];
            }
        }

        // Fallback: gethostbynamel() works reliably on Windows when dns_get_record() returns empty
        if (empty($resolved)) {
            $ipv4List = @gethostbynamel($domain);
            if ($ipv4List) {
                foreach ($ipv4List as $ip) {
                    $resolved[] = ['ip' => $ip, 'type' => 'A'];
                }
            }
        }

        $this->syncIps($monitor, $resolved);

        return $resolved;
    }

    public function pingIp(string $ip, int $timeout = 5): array
    {
        $start = microtime(true);
        $isWindows = PHP_OS_FAMILY === 'Windows';

        $command = $isWindows
            ? "ping -n 1 -w {$timeout}000 {$ip}"
            : "ping -c 1 -W {$timeout} {$ip}";

        exec($command, $output, $exitCode);
        $ms = (int) ((microtime(true) - $start) * 1000);

        return [
            'status' => $exitCode === 0 ? 'up' : 'down',
            'ms'     => $ms,
        ];
    }

    private function syncIps(Monitor $monitor, array $resolved): void
    {
        $existing = $monitor->ips()->get()->keyBy('ip_address');
        $resolvedIps = collect($resolved)->pluck('ip')->toArray();

        MonitorIp::where('monitor_id', $monitor->id)
            ->whereNotIn('ip_address', $resolvedIps)
            ->where('type', '!=', 'manual')
            ->update(['is_active' => false]);

        foreach ($resolved as $item) {
            if (!$existing->has($item['ip'])) {
                MonitorIp::create([
                    'monitor_id' => $monitor->id,
                    'ip_address' => $item['ip'],
                    'type'       => $item['type'],
                    'is_active'  => true,
                ]);
            } else {
                $existing->get($item['ip'])->update(['is_active' => true]);
            }
        }
    }
}
