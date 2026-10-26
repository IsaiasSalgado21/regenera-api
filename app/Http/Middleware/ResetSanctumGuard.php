<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ResetSanctumGuard
{
    public function handle(Request $request, Closure $next): Response
    {
        $guardAnterior = Auth::getDefaultDriver();

        try {
            return $next($request);
        } finally {
            Auth::guard('sanctum')->forgetUser();
            Auth::shouldUse($guardAnterior);
        }
    }
}
