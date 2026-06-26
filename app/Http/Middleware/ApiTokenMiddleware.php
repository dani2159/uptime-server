<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();
        if (!$bearer) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $token = \App\Models\ApiToken::where('token', hash('sha256', $bearer))->first();
        if (!$token || $token->isExpired()) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }
        $token->update(['last_used_at' => now()]);
        return $next($request);
    }
}
