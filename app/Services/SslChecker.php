<?php

namespace App\Services;

use App\Models\Monitor;

class SslChecker
{
    public function check(Monitor $monitor): ?array
    {
        if ($monitor->type !== 'http' && $monitor->type !== 'keyword') {
            return null;
        }

        $url = $monitor->url;
        if (!str_starts_with($url, 'https://')) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return null;
        }

        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                ],
            ]);

            $client = @stream_socket_client(
                "ssl://{$host}:443",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (!$client) {
                return ['ssl_valid' => false, 'ssl_expiry_at' => null, 'ssl_days_remaining' => null];
            }

            $params = stream_context_get_params($client);
            fclose($client);

            $cert    = $params['options']['ssl']['peer_certificate'] ?? null;
            if (!$cert) {
                return ['ssl_valid' => false, 'ssl_expiry_at' => null, 'ssl_days_remaining' => null];
            }

            $certInfo  = openssl_x509_parse($cert);
            $validTo   = $certInfo['validTo_time_t'] ?? null;

            if (!$validTo) {
                return ['ssl_valid' => false, 'ssl_expiry_at' => null, 'ssl_days_remaining' => null];
            }

            $expiryDate    = \Carbon\Carbon::createFromTimestamp($validTo);
            $daysRemaining = (int) now()->diffInDays($expiryDate, false);

            return [
                'ssl_valid'         => $daysRemaining > 0,
                'ssl_expiry_at'     => $expiryDate->toDateString(),
                'ssl_days_remaining' => $daysRemaining,
            ];
        } catch (\Throwable) {
            return ['ssl_valid' => false, 'ssl_expiry_at' => null, 'ssl_days_remaining' => null];
        }
    }

    public function saveResult(Monitor $monitor, array $sslResult): void
    {
        $monitor->update($sslResult);
    }
}
