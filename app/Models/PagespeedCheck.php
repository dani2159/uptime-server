<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagespeedCheck extends Model
{
    protected $fillable = [
        'pagespeed_monitor_id',
        'performance_score', 'accessibility_score', 'best_practices_score', 'seo_score',
        'cls', 'speed_index', 'fcp', 'lcp', 'tbt',
        'error_message', 'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(PagespeedMonitor::class, 'pagespeed_monitor_id');
    }

    public function scoreColor(int $score): string
    {
        if ($score >= 90) return '#22c55e';
        if ($score >= 50) return '#f59e0b';
        return '#ef4444';
    }

    public function scoreLabel(int $score): string
    {
        if ($score >= 90) return 'Good';
        if ($score >= 50) return 'Needs Improvement';
        return 'Poor';
    }
}
