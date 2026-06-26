<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MonitorTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Web
            ['name' => 'HTTP Website', 'category' => 'web', 'icon' => 'globe',
             'config' => ['type' => 'http', 'http_method' => 'GET', 'check_interval' => 5, 'timeout' => 10, 'retry_count' => 3, 'follow_redirects' => true]],
            ['name' => 'HTTPS + SSL Check', 'category' => 'web', 'icon' => 'lock',
             'config' => ['type' => 'http', 'http_method' => 'GET', 'check_interval' => 5, 'timeout' => 10, 'follow_redirects' => true, 'accepted_status_codes' => '200,301,302']],
            ['name' => 'Keyword Monitor', 'category' => 'web', 'icon' => 'search',
             'config' => ['type' => 'keyword', 'check_interval' => 5, 'timeout' => 15, 'retry_count' => 2]],

            // Database
            ['name' => 'MySQL Database', 'category' => 'database', 'icon' => 'database',
             'config' => ['type' => 'database', 'url' => 'mysql://root:@127.0.0.1:3306/mydb', 'check_interval' => 5, 'timeout' => 5]],
            ['name' => 'PostgreSQL Database', 'category' => 'database', 'icon' => 'database',
             'config' => ['type' => 'database', 'url' => 'pgsql://postgres:@127.0.0.1:5432/mydb', 'check_interval' => 5, 'timeout' => 5]],
            ['name' => 'Redis Cache', 'category' => 'database', 'icon' => 'server',
             'config' => ['type' => 'database', 'url' => 'redis://127.0.0.1:6379', 'check_interval' => 5, 'timeout' => 3]],

            // API
            ['name' => 'REST API JSON', 'category' => 'api', 'icon' => 'code',
             'config' => ['type' => 'http', 'http_method' => 'GET', 'accepted_status_codes' => '200', 'body_assertion_path' => '$.status', 'body_assertion_value' => 'ok', 'body_assertion_op' => 'equals', 'check_interval' => 5, 'timeout' => 10]],
            ['name' => 'REST API POST', 'category' => 'api', 'icon' => 'code',
             'config' => ['type' => 'http', 'http_method' => 'POST', 'request_body' => '{}', 'accepted_status_codes' => '200,201', 'check_interval' => 5, 'timeout' => 15]],
            ['name' => 'API with Bearer Auth', 'category' => 'api', 'icon' => 'key',
             'config' => ['type' => 'http', 'auth_type' => 'bearer', 'accepted_status_codes' => '200', 'check_interval' => 5, 'timeout' => 10]],

            // SatuSehat / BPJS
            ['name' => 'SatuSehat Auth', 'category' => 'satusehat', 'icon' => 'hospital',
             'config' => ['type' => 'http', 'url' => 'https://api-satusehat.kemkes.go.id/oauth2/v1/accesstoken?grant_type=client_credentials', 'http_method' => 'POST', 'accepted_status_codes' => '200', 'body_assertion_path' => '$.token_type', 'body_assertion_value' => 'Bearer', 'check_interval' => 10, 'timeout' => 15, 'environment' => 'production']],
            ['name' => 'SatuSehat FHIR Patient', 'category' => 'satusehat', 'icon' => 'hospital',
             'config' => ['type' => 'http', 'url' => 'https://api-satusehat.kemkes.go.id/fhir-r4/v1/Patient', 'http_method' => 'GET', 'auth_type' => 'bearer', 'accepted_status_codes' => '200,401', 'check_interval' => 10, 'timeout' => 15, 'environment' => 'production']],
            ['name' => 'BPJS VClaim', 'category' => 'satusehat', 'icon' => 'hospital',
             'config' => ['type' => 'http', 'url' => 'https://apijkn.bpjs-kesehatan.go.id/vclaim-rest/', 'accepted_status_codes' => '200,401,403', 'check_interval' => 5, 'timeout' => 15, 'environment' => 'production']],
            ['name' => 'BPJS Antrian', 'category' => 'satusehat', 'icon' => 'hospital',
             'config' => ['type' => 'http', 'url' => 'https://apijkn.bpjs-kesehatan.go.id/antrean/', 'accepted_status_codes' => '200,401,403', 'check_interval' => 5, 'timeout' => 15, 'environment' => 'production']],

            // Infrastructure
            ['name' => 'TCP Port Check', 'category' => 'infra', 'icon' => 'network',
             'config' => ['type' => 'tcp', 'check_interval' => 5, 'timeout' => 5]],
            ['name' => 'Ping Monitor', 'category' => 'infra', 'icon' => 'signal',
             'config' => ['type' => 'ping', 'check_interval' => 5, 'timeout' => 5]],
            ['name' => 'DNS Record', 'category' => 'infra', 'icon' => 'dns',
             'config' => ['type' => 'dns', 'dns_resolve_type' => 'A', 'check_interval' => 30]],
            ['name' => 'Docker Container', 'category' => 'infra', 'icon' => 'docker',
             'config' => ['type' => 'docker', 'check_interval' => 5, 'timeout' => 5]],

            // Domain
            ['name' => 'Domain Expiry (WHOIS)', 'category' => 'domain', 'icon' => 'calendar',
             'config' => ['type' => 'whois', 'check_interval' => 1440, 'domain_expiry_alert_days' => 30]],

            // Cron
            ['name' => 'Cron Job Monitor', 'category' => 'cron', 'icon' => 'clock',
             'config' => ['type' => 'cron', 'heartbeat_interval' => 60, 'check_interval' => 5]],
        ];

        foreach ($templates as $t) {
            \App\Models\MonitorTemplate::updateOrCreate(
                ['name' => $t['name'], 'category' => $t['category']],
                ['icon' => $t['icon'], 'config' => $t['config'], 'is_builtin' => true]
            );
        }
    }
}
