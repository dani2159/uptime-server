<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AlertAcknowledgement extends Model
{
    protected $fillable = ['incident_id', 'acked_by', 'channel_type', 'note', 'acked_at'];
    protected $casts    = ['acked_at' => 'datetime'];
    public function incident(): BelongsTo { return $this->belongsTo(Incident::class); }
}
