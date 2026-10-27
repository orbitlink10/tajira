<?php

use App\Http\Middleware\EnsureAdminMfa;
use App\Http\Middleware\EnsureOrderAccess;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleRedirects;
use App\Http\Middleware\ProtectStaging;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'admin.mfa' => EnsureAdminMfa::class,
            'order.access' => EnsureOrderAccess::class,
        ]);

        $middleware->append(SecurityHeaders::class);
        $middleware->append(ProtectStaging::class);
        $middleware->append(HandleRedirects::class);

        $middleware->validateCsrfTokens(except: [
            'payments/mpesa/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
