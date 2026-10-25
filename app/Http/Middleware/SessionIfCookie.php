<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * The atlas page and its data are served to anonymous readers without a session, so the open
 * atlas sets no cookies. A reader who signed in brings a session (or remember-me) cookie, and only
 * then is the session started so the server knows their plan.
 */
class SessionIfCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        $signedIn = $request->cookies->has(config('session.cookie'))
            || collect($request->cookies->keys())->contains(fn ($k) => Str::startsWith($k, 'remember_web_'));

        if (! $signedIn) {
            return $next($request);
        }

        return (new Pipeline(app()))
            ->send($request)
            ->through([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class])
            ->then($next);
    }
}
