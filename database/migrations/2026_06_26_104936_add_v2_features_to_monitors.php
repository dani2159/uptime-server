<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            // Notes & Runbook
            $table->text('notes')->nullable()->after('response_time_warning');
            $table->string('runbook_url')->nullable()->after('notes');

            // HTTP options
            $table->string('http_method', 10)->default('GET')->after('runbook_url');
            $table->text('request_body')->nullable()->after('http_method');
            $table->string('auth_type', 20)->default('none')->after('request_body');
            $table->string('auth_username')->nullable()->after('auth_type');
            $table->string('auth_password')->nullable()->after('auth_username');
            $table->json('custom_headers')->nullable()->after('auth_password');
            $table->string('accepted_status_codes')->nullable()->after('custom_headers');
            $table->boolean('ignore_tls_error')->default(false)->after('accepted_status_codes');
            $table->boolean('follow_redirects')->default(true)->after('ignore_tls_error');
            $table->unsignedTinyInteger('max_redirects')->default(5)->after('follow_redirects');
            $table->string('custom_user_agent')->nullable()->after('max_redirects');
            $table->string('proxy_url')->nullable()->after('custom_user_agent');

            // Response assertions
            $table->string('body_assertion_path')->nullable()->after('proxy_url');
            $table->string('body_assertion_value')->nullable()->after('body_assertion_path');
            $table->string('body_assertion_op', 20)->default('equals')->after('body_assertion_value');
            $table->string('suppress_pattern')->nullable()->after('body_assertion_op');
            $table->unsignedInteger('min_response_size')->nullable()->after('suppress_pattern');
            $table->unsignedInteger('max_response_size')->nullable()->after('min_response_size');

            // Flap detection
            $table->boolean('flap_detection')->default(false)->after('max_response_size');
            $table->unsignedTinyInteger('flap_window_minutes')->default(5)->after('flap_detection');
            $table->unsignedTinyInteger('flap_count_threshold')->default(3)->after('flap_window_minutes');
            $table->timestamp('flap_first_at')->nullable()->after('flap_count_threshold');
            $table->unsignedTinyInteger('flap_occurrences')->default(0)->after('flap_first_at');

            // Latency trend
            $table->boolean('latency_trend_alert')->default(false)->after('flap_occurrences');

            // Environment grouping
            $table->string('environment', 20)->nullable()->after('latency_trend_alert');

            // Cron heartbeat
            $table->unsignedSmallInteger('heartbeat_interval')->nullable()->after('environment');
            $table->timestamp('last_heartbeat_at')->nullable()->after('heartbeat_interval');

            // WHOIS domain expiry
            $table->date('domain_expiry_at')->nullable()->after('last_heartbeat_at');
            $table->unsignedSmallInteger('domain_expiry_days_remaining')->nullable()->after('domain_expiry_at');
            $table->unsignedSmallInteger('domain_expiry_alert_days')->default(30)->after('domain_expiry_days_remaining');

            // Health score
            $table->unsignedTinyInteger('health_score')->default(100)->after('domain_expiry_alert_days');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn([
                'notes', 'runbook_url',
                'http_method', 'request_body',
                'auth_type', 'auth_username', 'auth_password', 'custom_headers',
                'accepted_status_codes', 'ignore_tls_error', 'follow_redirects',
                'max_redirects', 'custom_user_agent', 'proxy_url',
                'body_assertion_path', 'body_assertion_value', 'body_assertion_op',
                'suppress_pattern', 'min_response_size', 'max_response_size',
                'flap_detection', 'flap_window_minutes', 'flap_count_threshold',
                'flap_first_at', 'flap_occurrences',
                'latency_trend_alert', 'environment',
                'heartbeat_interval', 'last_heartbeat_at',
                'domain_expiry_at', 'domain_expiry_days_remaining', 'domain_expiry_alert_days',
                'health_score',
            ]);
        });
    }
};
