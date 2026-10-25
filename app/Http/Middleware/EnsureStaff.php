<?php

namespace App\Http\Middleware;

use App\Support\Staff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Staff areas return 404 to everyone else, the same way /admin already did. */
class EnsureStaff
{
    public function handle(Request $request, Closure $next, string $ability = 'dashboard'): Response
    {
        abort_unless(Staff::allows($request->user(), $ability), 404);

        return $next($request);
    }
}
