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

    // Default templates — used when no DB value exists
    public static function defaults(): array
    {
        return [
            'notif_down_body' =>
                "🔴 <b>{name}</b> is DOWN\n" .
                "URL: {url}\n" .
                "Waktu: {timestamp}",

            'notif_recovered_body' =>
                "🟢 <b>{name}</b> is UP kembali\n" .
                "URL: {url}\n" .
                "Durasi down: {duration}\n" .
                "Waktu: {timestamp}",
        ];
    }
}
