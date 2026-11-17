<?php

namespace App\Http\Middleware;

use App\Services\AccountProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountComplete
{
    public function __construct(private readonly AccountProfile $profiles) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('sanctum')->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->menorEdadMinima && ! $request->is('api/auth/logout')) {
            return response()->json([
                'message' => 'No es posible acceder con esta cuenta. Contacta a Regenerabcs@mail.com.',
                'code' => 'edad_minima',
            ], 403);
        }

        if (! $this->profiles->complete($user)
            && ! $request->is('api/auth/me', 'api/auth/completar-perfil', 'api/auth/logout')) {
            return response()->json([
                'message' => 'Completa tus datos para continuar.',
                'code' => 'perfil_incompleto',
            ], 428);
        }

        return $next($request);
    }
}
