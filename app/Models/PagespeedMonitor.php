<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PagespeedMonitor extends Model
{
    protected $fillable = [
        'name', 'url', 'strategy', 'interval_minutes', 'is_active', 'api_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function checks(): HasMany
    {
        return $this->hasMany(PagespeedCheck::class);
    }

    public function latestCheck(): HasMany
    {
        return $this->hasMany(PagespeedCheck::class)->latest('checked_at')->limit(1);
    }

    public function getLatestCheckAttribute(): ?PagespeedCheck
    {
        return $this->checks()->latest('checked_at')->first();
    }
}
