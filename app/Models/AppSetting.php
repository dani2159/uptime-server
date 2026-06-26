<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;
    protected $fillable   = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::find($key);
        return $row ? $row->value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function defaults(): array
    {
        return [
            'notif_down_body' =>
                "🔴 <b>{name}</b> is DOWN\nURL: {url}\nWaktu: {timestamp}",
            'notif_recovered_body' =>
                "🟢 <b>{name}</b> is UP kembali\nURL: {url}\nDurasi down: {duration}\nWaktu: {timestamp}",
            'notif_slow_body' =>
                "🟡 <b>{name}</b> LAMBAT\nURL: {url}\nResponse: <b>{response_time}</b> (batas: {threshold}ms)\nWaktu: {timestamp}",
            'notif_escalation_body' =>
                "🚨 <b>ESKALASI: {name}</b> masih DOWN\nURL: {url}\nSudah down selama: <b>{duration}</b>\nAturan: {rule}\nWaktu: {timestamp}",
            // v2 defaults
            'incident_auto_close_minutes'   => '0',
            'notif_business_hours_only'     => '0',
            'correlated_incident_threshold' => '5',
        ];
    }
}
