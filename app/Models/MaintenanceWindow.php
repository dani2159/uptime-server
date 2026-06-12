<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MaintenanceWindow extends Model
{
    protected $fillable = ['title', 'description', 'start_at', 'end_at', 'monitor_ids'];

    protected $casts = [
        'start_at'    => 'datetime',
        'end_at'      => 'datetime',
        'monitor_ids' => 'array',
    ];

    public function isActiveFor(Monitor $monitor): bool
    {
        $now = Carbon::now();
        if ($now->lt($this->start_at) || $now->gt($this->end_at)) {
            return false;
        }
        // null monitor_ids = berlaku untuk semua monitor
        return $this->monitor_ids === null || in_array($monitor->id, $this->monitor_ids);
    }

    public static function isMonitorInMaintenance(Monitor $monitor): bool
    {
        return static::where('start_at', '<=', now())
            ->where('end_at', '>=', now())
            ->get()
            ->contains(fn($w) => $w->isActiveFor($monitor));
    }
}
