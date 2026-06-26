<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ApiToken extends Model
{
    protected $fillable = ['name', 'token', 'abilities', 'last_used_at', 'expires_at'];
    protected $casts    = ['last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    public function isExpired(): bool { return $this->expires_at && $this->expires_at->isPast(); }
    public function can(string $ability): bool { return in_array($ability, explode('|', $this->abilities)) || $this->abilities === 'admin'; }
}
