<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Http\Request;

class ThrottleAttendance
{
    private RateLimiter $limiter;

    public function __construct(CacheFactory $cache)
    {
        $this->limiter = new RateLimiter($cache->store('file'));
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $key = sha1((string) ($request->user()?->getAuthIdentifier() ?? $request->ip()));
        $maxAttempts = 30;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Demasiados intentos de asistencia. Espera un momento e inténtalo de nuevo.',
            ], 429, [
                'Retry-After' => (string) $this->limiter->availableIn($key),
                'X-RateLimit-Limit' => (string) $maxAttempts,
                'X-RateLimit-Remaining' => '0',
            ]);
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }
}