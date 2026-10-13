<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureSchoolAccountAccess;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', [AddSecurityHeaders::class, EnsureSchoolAccountAccess::class]);
        $middleware->validateCsrfTokens(except: ['pembayaran/webhook/*']);
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

// Daftar proxy tepercaya dibaca setelah config dimuat (bukan saat bootstrap awal).
$app->booted(function () {
    $trustedProxies = config('app.trusted_proxies', []);

    if ($trustedProxies !== []) {
        TrustProxies::at($trustedProxies);
    }
});

return $app;