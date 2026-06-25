<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'ssl_valid'             => 'boolean',
        'last_checked_at'       => 'datetime',
        'last_down_at'          => 'datetime',
        'last_push_at'          => 'datetime',
        'ssl_expiry_at'         => 'date',
        'notification_channels' => 'array',
        'uptime_percentage'     => 'decimal:2',
        'uptime_24h'            => 'decimal:2',
        'uptime_7d'             => 'decimal:2',
        'uptime_30d'            => 'decimal:2',
    ];

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'monitor_tag');
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
        return $this->hasMany(MonitorLog::class)->latest('checked_at')->limit(90);
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
}
