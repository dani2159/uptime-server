<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BusinessHour extends Model
{
    protected $fillable = ['day_of_week', 'open_time', 'close_time', 'is_working_day'];
    protected $casts    = ['is_working_day' => 'boolean'];
    public static function isBusinessHours(): bool
    {
        $now  = now();
        $dow  = (int) $now->format('w');
        $time = $now->format('H:i:s');
        $bh   = static::where('day_of_week', $dow)->first();
        if (!$bh || !$bh->is_working_day) return false;
        return $time >= $bh->open_time && $time <= $bh->close_time;
    }
}
