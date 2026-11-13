<?php

use App\Http\Middleware\PreventBackHistory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedProxies = env('TRUSTED_PROXIES');
        if (is_string($trustedProxies) && trim($trustedProxies) !== '') {
            $proxies = trim($trustedProxies) === '*'
                ? '*'
                : array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));

            $middleware->trustProxies(at: $proxies);
        }

        $middleware->web(append: [
            PreventBackHistory::class,
        ]);
        $middleware->redirectUsersTo(fn (Request $request): string => $request->user()?->hasPermission('create_report')
            ? route('supervisi.create')
            : route('supervisi.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
