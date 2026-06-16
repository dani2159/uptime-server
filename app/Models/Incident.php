<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Incident extends Model
{
    protected $fillable = [
        'monitor_id', 'category', 'severity', 'title',
        'started_at', 'resolved_at', 'status',
        'duration_seconds', 'note', 'reporter_name', 'reporter_contact', 'is_manual',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'resolved_at' => 'datetime',
        'is_manual'   => 'boolean',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', 'closed');
    }

    public function scopeMonitorDowntime(Builder $query): Builder
    {
        return $query->where('category', 'monitor_downtime');
    }

    public function scopeGeneral(Builder $query): Builder
    {
        return $query->where('category', 'general');
    }

    public function scopeClientReport(Builder $query): Builder
    {
        return $query->where('category', 'client_report');
    }

    public function getDisplayTitleAttribute(): string
    {
        return $this->title ?: $this->monitor?->name ?: 'Insiden';
    }

    public function getDurationLabelAttribute(): string
    {
        $seconds = $this->duration_seconds ?? $this->started_at->diffInSeconds(now());
        if ($seconds < 60) return "{$seconds} detik";
        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) return "{$minutes} menit";
        $hours = intdiv($minutes, 60);
        $remMinutes = $minutes % 60;
        return $remMinutes > 0 ? "{$hours} jam {$remMinutes} menit" : "{$hours} jam";
    }
}
