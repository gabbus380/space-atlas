<?php

namespace App\Support;

use App\Models\User;

/** Personal readers keep one watchlist on their account. Teams members switch between their own and the team's. */
class Watchlists
{
    public static function scope(User $user, string $tier): string
    {
        return $tier === 'teams' && $user->watch_scope === 'team' ? 'team' : 'me';
    }

    public static function doc(User $user, string $tier): ?array
    {
        if (self::scope($user, $tier) === 'team') {
            $t = $user->team;

            return is_array($t->watchlist) ? ['iso3' => $t->watchlist, 'updated_at' => $t->watchlist_updated_at?->toIso8601ZuluString(), 'scope' => 'team'] : null;
        }

        return is_array($user->watchlist) ? ['iso3' => $user->watchlist, 'updated_at' => $user->watchlist_updated_at?->toIso8601ZuluString(), 'scope' => 'me'] : null;
    }

    /** @param string[] $iso3 */
    public static function save(User $user, string $tier, array $iso3): array
    {
        $list = array_values(array_unique(array_map('strtoupper', $iso3)));
        if (self::scope($user, $tier) === 'team') {
            $user->team->forceFill(['watchlist' => $list, 'watchlist_updated_at' => now()])->save();
        } else {
            $user->forceFill(['watchlist' => $list, 'watchlist_updated_at' => now()])->save();
        }

        return self::doc($user->fresh(), $tier);
    }
}
