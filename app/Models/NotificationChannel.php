<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationChannel extends Model
{
    protected $fillable = ['name', 'type', 'token', 'target', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = ['token'];
}
