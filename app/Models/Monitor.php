<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Monitor extends Model
{
    protected $fillable = [
        'name', 'url', 'type', 'is_active', 'check_interval', 'timeout',
        'retry_count', 'current_retries', 'expected_status',
        'keyword', 'tcp_host', 'tcp_port',
        'push_token', 'last_push_at', 'dns_resolve_type', 'dns_expected_value',
        'last_status', 'last_response_time', 'last_checked_at', 'last_down_at',
        'uptime_percentage', 'uptime_24h', 'uptime_7d', 'uptime_30d',
        'ssl_expiry_at', 'ssl_valid', 'ssl_days_remaining',
        'notification_channels',
        'response_time_warning', 'last_is_slow',
        // v2
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
    ];

    protected $casts = [
        'is_active'                  => 'boolean',
        'ssl_valid'                  => 'boolean',
        'last_is_slow'               => 'boolean',
        'ignore_tls_error'           => 'boolean',
        'follow_redirects'           => 'boolean',
        'flap_detection'             => 'boolean',
        'latency_trend_alert'        => 'boolean',
        'last_checked_at'            => 'datetime',
        'last_down_at'               => 'datetime',
        'last_push_at'               => 'datetime',
        'last_heartbeat_at'          => 'datetime',
        'flap_first_at'              => 'datetime',
        'ssl_expiry_at'              => 'date',
        'domain_expiry_at'           => 'date',
        'notification_channels'      => 'array',
        'custom_headers'             => 'array',
        'uptime_percentage'          => 'decimal:2',
        'uptime_24h'                 => 'decimal:2',
        'uptime_7d'                  => 'decimal:2',
        'uptime_30d'                 => 'decimal:2',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'monitor_tag');
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class, 'monitor_dependencies', 'monitor_id', 'depends_on_id');
    }

    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class, 'monitor_dependencies', 'depends_on_id', 'monitor_id');
    }

    public function ips(): HasMany
    {
        return $this->hasMany(MonitorIp::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MonitorLog::class);
    }

    public function latestLogs(): HasMany
    {
        return $this->hasMany(MonitorLog::class)->latest('checked_at')->limit(50);
    }

    public function heartbeatLogs(): HasMany
    {
        return $this->hasMany(MonitorLog::class)->latest('checked_at')->limit(20);
    }

    public function slaContracts(): HasMany
    {
        return $this->hasMany(SlaContract::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->last_status) {
            'up'      => 'green',
            'down'    => 'red',
            default   => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->last_status) {
            'up'      => 'UP',
            'down'    => 'DOWN',
            default   => 'PENDING',
        };
    }

    public function getDomainAttribute(): string
    {
        $host = parse_url($this->url, PHP_URL_HOST);
        return $host ?: $this->url;
    }

    public function isDependencyDown(): bool
    {
        return $this->dependencies()->where('last_status', 'down')->exists();
    }

    public function totalDowntimeMinutes(): int
    {
        return (int) $this->logs()
            ->where('status', 'down')
            ->sum(\DB::raw('COALESCE(response_time, 0)')) / 60000;
    }

    public function availabilityCalendar(int $days = 90): array
    {
        $from  = now()->subDays($days - 1)->startOfDay();
        $rows  = $this->logs()
            ->where('checked_at', '>=', $from)
            ->selectRaw('DATE(checked_at) as day, SUM(status = "up") as up_count, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $calendar = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d   = now()->subDays($i)->format('Y-m-d');
            $row = $rows->get($d);
            $pct = $row && $row->total > 0 ? round($row->up_count / $row->total * 100) : null;
            $calendar[] = ['date' => $d, 'pct' => $pct, 'total' => $row?->total ?? 0];
        }
        return $calendar;
    }

    public function getHealthScoreBadgeAttribute(): string
    {
        $s = $this->health_score ?? 0;
        if ($s >= 90) return 'bg-emerald-900/50 text-emerald-300';
        if ($s >= 70) return 'bg-amber-900/50 text-amber-300';
        return 'bg-red-900/50 text-red-300';
    }
}
