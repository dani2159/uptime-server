<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class OnCallShift extends Model
{
    protected $fillable = ['schedule_id', 'name', 'channel_id', 'contact_info', 'label', 'day_of_week', 'start_time', 'end_time'];
    public function schedule(): BelongsTo { return $this->belongsTo(OnCallSchedule::class, 'schedule_id'); }
    public function channel(): BelongsTo  { return $this->belongsTo(NotificationChannel::class, 'channel_id'); }
}
