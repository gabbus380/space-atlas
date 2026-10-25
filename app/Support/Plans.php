<?php

namespace App\Support;

use App\Models\FileUnlock;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * What each reader may see. The server filters data with these rules; the page only explains them.
 */
class Plans
{
    public const PAID = ['personal', 'teams'];

    public static function tier(?User $user): string
    {
        if (! $user) {
            return 'guest';
        }
        if ($user->team_id && ($team = $user->team) && $team->isActive() && $team->hasSeatFor($user)) {
            return 'teams';
        }
        if ($user->hasPersonalOverride() || $user->personalSubscription()) {
            return 'personal';
        }

        return 'free';
    }

    public static function isPaid(string $tier): bool
    {
        return in_array($tier, self::PAID, true);
    }

    public static function features(string $tier): array
    {
        $paid = self::isPaid($tier);

        return [
            'account' => $tier !== 'guest',
            'files' => $paid,
            'brief' => $paid,
            'archive' => $paid,
            'audio' => $paid,
            'programs' => $paid,
            'eco' => $paid,
            'engagements' => $paid,
            'csv' => $tier === 'teams',
            'notes' => $tier === 'teams',
            'watch' => $tier === 'teams' ? 'team' : ($tier === 'personal' ? 'account' : 'local'),
            'support' => $tier === 'teams' ? 'priority' : 'email',
        ];
    }

    public static function prices(): array
    {
        $p = config('atlas.prices');

        return [
            'personal_year' => $p['personal']['year'],
            'personal_month' => $p['personal']['month'],
            'personal_year_month' => (int) round($p['personal']['year'] / 12),
            'personal_saving' => $p['personal']['month'] * 12 - $p['personal']['year'],
            'teams_year' => $p['teams']['year'],
            'teams_month' => $p['teams']['month'],
            'teams_year_month' => (int) round($p['teams']['year'] / 12),
            'teams_saving' => $p['teams']['month'] * 12 - $p['teams']['year'],
            'teams_min' => config('atlas.teams_min_seats'),
            'api_year' => $p['api']['year'],
        ];
    }

    /** @return string[] ISO3 codes a free reader opened this month */
    public static function unlockedThisMonth(User $user): array
    {
        return FileUnlock::where('user_id', $user->id)->where('month', FileUnlock::currentMonth())->orderBy('created_at')->pluck('iso3')->all();
    }

    public static function credits(User $user): array
    {
        $unlocked = self::unlockedThisMonth($user);
        $limit = (int) config('atlas.free_files_per_month');
        $next = now('UTC')->startOfMonth()->addMonth();

        return [
            'limit' => $limit,
            'used' => count($unlocked),
            'left' => max(0, $limit - count($unlocked)),
            'unlocked' => $unlocked,
            'month' => FileUnlock::currentMonth(),
            'renews' => $next->format('F j'),
        ];
    }

    /** The plan details the atlas page needs (window.ATLAS_ENT). */
    public static function ent(?User $user, Request $request, ?string $tier = null): array
    {
        $tier ??= self::tier($user);
        $ent = [
            'tier' => $tier,
            'features' => self::features($tier),
            'prices' => self::prices(),
            'urls' => [
                'live' => '/app/live', 'login' => '/login', 'register' => '/register', 'pricing' => '/pricing',
                'account' => '/account', 'unlock' => '/app/unlock', 'watchlist' => '/app/watchlist',
                'scope' => '/app/watchlist/scope', 'notes' => '/app/notes', 'verify' => '/email/verify',
            ],
            'csrf' => $request->hasSession() && $request->session()->isStarted() ? $request->session()->token() : null,
        ];
        if ($user) {
            $ent['user'] = ['name' => $user->name];
            $ent['verified'] = $user->hasVerifiedEmail();
            if ($tier === 'teams') {
                $ent['team'] = ['name' => $user->team->name, 'role' => $user->team->owner_id === $user->id ? 'owner' : 'member'];
                $ent['watchScope'] = $user->watch_scope === 'team' ? 'team' : 'me';
            }
            if ($tier === 'free') {
                $ent['credits'] = self::credits($user);
            }
        }

        return $ent;
    }
}
