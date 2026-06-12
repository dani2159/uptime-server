<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusPage extends Model
{
    protected $fillable = ['slug', 'title', 'description', 'is_public', 'monitor_ids', 'sections'];

    protected $casts = [
        'is_public'   => 'boolean',
        'monitor_ids' => 'array',
        'sections'    => 'array',
    ];

    public function allMonitorIds(): array
    {
        if (!empty($this->sections)) {
            return collect($this->sections)
                ->flatMap(fn($s) => array_map('intval', $s['monitor_ids'] ?? []))
                ->unique()
                ->values()
                ->toArray();
        }

        return array_map('intval', $this->monitor_ids ?? []);
    }
}
