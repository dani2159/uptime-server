<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public    $timestamps  = false;
    protected $fillable    = ['action', 'subject_type', 'subject_id', 'description', 'ip_address', 'created_at'];
    protected $casts       = ['created_at' => 'datetime'];
}
