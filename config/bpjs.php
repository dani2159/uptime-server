<?php

/*
 * BPJS API services — hanya URL dan endpoints, tanpa credentials.
 *
 * CDN vs Non-CDN:
 *   Non-CDN : https://apijkn.bpjs-kesehatan.go.id   (direct)
 *   CDN     : https://new-apijkn.bpjs-kesehatan.go.id (via CDN)
 *
 * Mode aktif dikontrol via UI toggle, disimpan di cache.
 * Default dari env BPJS_CDN_MODE (non_cdn / cdn).
 */

return [
    'timeout'      => (int) env('BPJS_TIMEOUT', 15),
    'default_mode' => env('BPJS_CDN_MODE', 'non_cdn'),

    'hosts' => [
        'non_cdn' => env('BPJS_HOST_NON_CDN', 'https://apijkn.bpjs-kesehatan.go.id'),
        'cdn'     => env('BPJS_HOST_CDN',     'https://new-apijkn.bpjs-kesehatan.go.id'),
    ],

    'services' => [

        'vclaim' => [
            'label'      => 'VClaim',
            'path'       => '/vclaim-rest',
            'endpoints'  => [
                ['key' => 'vclaim_cari_kartu',           'label' => 'Cari No Kartu',          'method' => 'GET',    'path' => '/Peserta/noKartu/0000000000000/tglSEP/' . date('Y-m-d')],
                ['key' => 'vclaim_cari_rujukan',         'label' => 'Cari No Rujukan',        'method' => 'GET',    'path' => '/Rujukan/noRujukan/0'],
                ['key' => 'vclaim_cari_sep',             'label' => 'Cari SEP',               'method' => 'GET',    'path' => '/SEP/noSep/0'],
                ['key' => 'vclaim_dokter',               'label' => 'Dokter BPJS',            'method' => 'GET',    'path' => '/referensi/dokter/PPK/0/nama/a'],
                ['key' => 'vclaim_cari_surat_kontrol',   'label' => 'Cari Surat Kontrol',     'method' => 'GET',    'path' => '/SuratKontrol/noSuratKontrol/0'],
                ['key' => 'vclaim_insert_sep',           'label' => 'Insert SEP',             'method' => 'POST',   'path' => '/SEP/2.0/insert'],
                ['key' => 'vclaim_delete_sep',           'label' => 'Delete SEP',             'method' => 'DELETE', 'path' => '/SEP/2.0/delete'],
                ['key' => 'vclaim_update_surat_kontrol', 'label' => 'Update Surat Kontrol',   'method' => 'PUT',    'path' => '/SuratKontrol/update'],
                ['key' => 'vclaim_delete_surat_kontrol', 'label' => 'Delete Surat Kontrol',   'method' => 'DELETE', 'path' => '/SuratKontrol/delete'],
                ['key' => 'vclaim_list_rujukan',         'label' => 'List Rujukan',           'method' => 'GET',    'path' => '/Rujukan/PPK/peserta/' . date('Y-m-d') . '/' . date('Y-m-d') . '/1'],
                ['key' => 'vclaim_insert_spri',          'label' => 'Insert SPRI',            'method' => 'POST',   'path' => '/SPRI/insert'],
                ['key' => 'vclaim_insert_rencana',       'label' => 'Insert Rencana Kontrol', 'method' => 'POST',   'path' => '/SuratKontrol/insert'],
            ],
        ],

        'antrean_rs' => [
            'label'     => 'Antrean RS',
            'path'      => '/antreanrs',
            'endpoints' => [
                ['key' => 'antrean_rs_status', 'label' => 'Status Antrean', 'method' => 'GET',  'path' => '/antrean/status'],
                ['key' => 'antrean_rs_list',   'label' => 'List Antrean',   'method' => 'GET',  'path' => '/antrean/pendaftaran/tanggal/' . date('Y-m-d')],
                ['key' => 'antrean_rs_ambil',  'label' => 'Ambil Antrean',  'method' => 'POST', 'path' => '/antrean/pendaftaran/buat'],
                ['key' => 'antrean_rs_batal',  'label' => 'Batal Antrean',  'method' => 'POST', 'path' => '/antrean/pendaftaran/batal'],
            ],
        ],

        'antrean_fktp' => [
            'label'     => 'Antrean FKTP',
            'path'      => '/antreanfktp',
            'endpoints' => [
                ['key' => 'fktp_ambil',  'label' => 'Ambil Antrean FKTP',  'method' => 'POST', 'path' => '/antrean/ambil'],
                ['key' => 'fktp_status', 'label' => 'Status Antrean FKTP', 'method' => 'GET',  'path' => '/antrean/status'],
            ],
        ],

        'apotek' => [
            'label'     => 'Apotek',
            'path'      => '/apotek-rest',
            'endpoints' => [
                ['key' => 'apotek_resep', 'label' => 'Data Resep', 'method' => 'GET', 'path' => '/resep/noSep/0'],
                ['key' => 'apotek_obat',  'label' => 'Data Obat',  'method' => 'GET', 'path' => '/obat/kodeObat/0'],
            ],
        ],

        'pcare' => [
            'label'     => 'PCare',
            // PCare menggunakan domain berbeda — tidak mengikuti switch CDN/non-CDN
            'path'      => '/pcare-rest',
            'base_url'  => env('BPJS_PCARE_URL', 'https://apijkn.kesehatan.go.id/pcare-rest'),
            'endpoints' => [
                ['key' => 'pcare_peserta',   'label' => 'Data Peserta', 'method' => 'GET', 'path' => '/peserta/noKartu/0000000000000'],
                ['key' => 'pcare_kunjungan', 'label' => 'Kunjungan',    'method' => 'GET', 'path' => '/kunjungan/startDate/' . date('Y-m-d') . '/endDate/' . date('Y-m-d')],
            ],
        ],

        'icare' => [
            'label'     => 'iCare JKN',
            'path'      => '/ihs',
            'endpoints' => [
                ['key' => 'icare_riwayat', 'label' => 'Riwayat Pelayanan', 'method' => 'GET', 'path' => '/riwayat/noKartu/0000000000000'],
            ],
        ],

        'erekam' => [
            'label'     => 'eRekamMedis',
            'path'      => '/erekammedis',
            'endpoints' => [
                ['key' => 'erekam_pasien', 'label' => 'Data Pasien', 'method' => 'GET', 'path' => '/pasien/noKartu/0000000000000'],
            ],
        ],

    ],
];
