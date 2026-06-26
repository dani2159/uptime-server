<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class IncidentPostMortem extends Model
{
    protected $fillable = ['incident_id', 'title', 'timeline', 'root_cause', 'impact', 'action_items', 'severity', 'author'];
    public function incident(): BelongsTo { return $this->belongsTo(Incident::class); }
}
