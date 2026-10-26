<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OptionalSanctumAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('sanctum');

        if ($request->bearerToken()) {
            $guard->forgetUser();
        }

        $user = $guard->user();

        if ($user) {
            Auth::shouldUse('sanctum');
            $request->setUserResolver(fn () => $user);
        }

        return $next($request);
    }
}
