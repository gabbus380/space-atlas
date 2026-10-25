<?php

namespace App\Http\Middleware;

use App\Support\Staff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Staff::allows($request->user(), 'dashboard'), 404);

        return $next($request);
    }
}
