<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MonitorTemplate extends Model
{
    protected $fillable = ['name', 'category', 'icon', 'config', 'is_builtin'];
    protected $casts    = ['config' => 'array', 'is_builtin' => 'boolean'];
}
