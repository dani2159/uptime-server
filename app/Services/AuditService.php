<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public static function log(string $action, string $description, ?Model $subject = null, ?string $ip = null): void
    {
        AuditLog::create([
            'action'       => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'description'  => $description,
            'ip_address'   => $ip ?? request()->ip(),
            'created_at'   => now(),
        ]);
    }
}
