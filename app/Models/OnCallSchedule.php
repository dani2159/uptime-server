<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class OnCallSchedule extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];
    protected $casts    = ['is_active' => 'boolean'];
    public function shifts(): HasMany { return $this->hasMany(OnCallShift::class, 'schedule_id'); }
    public function currentShift(): ?OnCallShift
    {
        $now = now();
        $dow = (int) $now->format('w');
        $time = $now->format('H:i:s');
        return $this->shifts()
            ->where(fn($q) => $q->whereNull('day_of_week')->orWhere('day_of_week', $dow))
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->first();
    }
}
