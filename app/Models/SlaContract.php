<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SlaContract extends Model
{
    protected $fillable = ['monitor_id', 'name', 'target_uptime', 'max_downtime_seconds', 'period', 'is_active'];
    protected $casts    = ['is_active' => 'boolean', 'target_uptime' => 'decimal:2'];
    public function monitor(): BelongsTo { return $this->belongsTo(Monitor::class); }
}
