<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ApiUsage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/** API access: a bearer key issued under an Order Form, a daily quota and a per-minute ceiling. */
class ApiKeyAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken() ?: $request->header('X-API-Key');
        $key = $plain ? ApiKey::findByPlain($plain) : null;
        if (! $key || ! $key->isUsable()) {
            return response()->json(['error' => ['code' => 'unauthorized', 'message' => 'A valid API key is required. Contact '.config('atlas.contact').'.']], 401);
        }

        $minute = 'api-minute:'.$key->id;
        if (RateLimiter::tooManyAttempts($minute, 120)) {
            return response()->json(['error' => ['code' => 'rate_limited', 'message' => 'Too many requests. Slow down to 120 a minute.']], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($minute));
        }
        RateLimiter::hit($minute, 60);

        $day = now('UTC')->toDateString();
        $used = DB::transaction(function () use ($key, $day) {
            $row = ApiUsage::firstOrCreate(['api_key_id' => $key->id, 'day' => $day], ['count' => 0]);
            $row->increment('count');

            return $row->count;
        });
        if ($used > $key->daily_quota) {
            return response()->json(['error' => ['code' => 'quota_exceeded', 'message' => 'The daily quota in your Order Form is used up. It resets at 00:00 UTC.']], 429);
        }
        $key->forceFill(['last_used_at' => now()])->saveQuietly();
        $request->attributes->set('api_key', $key);

        $response = $next($request);
        $response->headers->set('X-Quota-Limit', (string) $key->daily_quota);
        $response->headers->set('X-Quota-Remaining', (string) max(0, $key->daily_quota - $used));

        return $response;
    }
}
