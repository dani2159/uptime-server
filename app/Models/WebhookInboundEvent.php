<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WebhookInboundEvent extends Model
{
    protected $fillable = ['source', 'token', 'name', 'is_active', 'last_payload', 'last_status', 'last_received_at', 'monitor_id'];
    protected $casts    = ['is_active' => 'boolean', 'last_payload' => 'array', 'last_received_at' => 'datetime'];
}
