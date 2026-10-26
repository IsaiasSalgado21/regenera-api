<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\OptionalSanctumAuthentication;
use App\Http\Middleware\ResetSanctumGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: ResetSanctumGuard::class);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'auth.optional' => OptionalSanctumAuthentication::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
