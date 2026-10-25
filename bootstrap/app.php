<?php

use App\Http\Middleware\AtlasHeaders;
use App\Http\Middleware\SessionIfCookie;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'v1',
        then: function () {
            Route::middleware([AtlasHeaders::class, SessionIfCookie::class])->group(base_path('routes/atlas.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Laravel Cloud terminates TLS at its load balancer
        $middleware->trustProxies(at: '*');
        // Paddle webhooks are signed, not CSRF-protected
        $middleware->preventRequestForgery(except: ['paddle/*', 'email/preferences/*', 'login/*/callback']);
        $middleware->appendToGroup('web', AtlasHeaders::class);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/account');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('v1/*') || $request->is('app/*') || $request->expectsJson(),
        );
    })->create();
