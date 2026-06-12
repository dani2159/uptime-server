<?php

/*
 * Satu Sehat (Kemenkes) API — hanya base_url dan endpoints.
 * Project ini tidak menyimpan credentials.
 * Tujuan: cek konektivitas server, bukan integrasi API.
 *
 * Environments:
 *   Dev  : https://api-satusehat-dev.dto.kemkes.go.id
 *   Stage: https://api-satusehat-stg.dto.kemkes.go.id
 *   Prod : https://api-satusehat.kemkes.go.id
 */

return [
    'base_url' => env('SATUSEHAT_BASE_URL', 'https://api-satusehat-dev.dto.kemkes.go.id'),
    'timeout'  => (int) env('SATUSEHAT_TIMEOUT', 15),

    'endpoints' => [
        ['key' => 'ss_auth',         'label' => 'Auth Endpoint',     'method' => 'GET',  'path' => '/oauth2/v1/accesstoken'],
        ['key' => 'ss_patient',      'label' => 'Patient (FHIR)',    'method' => 'GET',  'path' => '/fhir-r4/v1/Patient'],
        ['key' => 'ss_organization', 'label' => 'Organization',      'method' => 'GET',  'path' => '/fhir-r4/v1/Organization'],
        ['key' => 'ss_encounter',    'label' => 'Encounter',         'method' => 'GET',  'path' => '/fhir-r4/v1/Encounter'],
        ['key' => 'ss_condition',    'label' => 'Condition',         'method' => 'GET',  'path' => '/fhir-r4/v1/Condition'],
        ['key' => 'ss_observation',  'label' => 'Observation',       'method' => 'GET',  'path' => '/fhir-r4/v1/Observation'],
        ['key' => 'ss_procedure',    'label' => 'Procedure',         'method' => 'GET',  'path' => '/fhir-r4/v1/Procedure'],
    ],
];
