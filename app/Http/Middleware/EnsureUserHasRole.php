<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->rol, $roles, true)) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], $user ? 403 : 401);
        }

        return $next($request);
    }
}
