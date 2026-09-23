<?php

use App\Http\Middleware\IsAdmin;
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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'is_admin' => IsAdmin::class,
        ]);

        // Bearer-token auth, not cookies: no statefulApi() here.
        // (statefulApi() enables CSRF checks for "stateful" frontend domains,
        // which caused the "CSRF token mismatch" error on /api/register.)

        // Belt and braces: never require a CSRF token on API routes.
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        // Vercel's edge terminates TLS and forwards over HTTP internally, so without
        // this Laravel thinks every request is plain HTTP, breaking url()/APP_URL-based
        // absolute links and any "is this request secure" checks.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();