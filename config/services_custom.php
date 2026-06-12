<?php

/*
 * Custom API services — hanya base_url, label, dan endpoints.
 * Tidak ada credentials/auth. Tujuan: cek konektivitas.
 *
 * Cara tambah service baru:
 *   1. Tambah entry di array ini
 *   2. Set BASE_URL di .env
 *   3. Otomatis muncul di API Health Dashboard
 */

return [

    'sisrute' => [
        'label'     => 'Sisrute',
        'base_url'  => env('SISRUTE_BASE_URL', 'https://sisrute.kemkes.go.id/api'),
        'timeout'   => 15,
        'endpoints' => [
            ['key' => 'sisrute_status', 'label' => 'Status API', 'method' => 'GET', 'path' => '/status'],
            ['key' => 'sisrute_rs',     'label' => 'Data RS',    'method' => 'GET', 'path' => '/rs/list'],
        ],
    ],


];
