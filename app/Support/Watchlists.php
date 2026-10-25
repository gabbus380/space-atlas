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

    /**
     * The list the atlas should show. A team scope always returns a document, including an empty
     * unsaved list, so the page can leave the personal list and show the shared one.
     */
    public static function doc(User $user, string $tier): ?array
    {
        if (self::scope($user, $tier) === 'team') {
            $t = $user->team;
            if (! $t) {
                return null;
            }
            $saved = is_array($t->watchlist);

            return [
                'iso3' => $saved ? array_values($t->watchlist) : [],
                'saved' => $saved,
                'updated_at' => $t->watchlist_updated_at?->toIso8601ZuluString(),
                'scope' => 'team',
            ];
        }

        $saved = is_array($user->watchlist);

        return [
            'iso3' => $saved ? array_values($user->watchlist) : [],
            'saved' => $saved,
            'updated_at' => $user->watchlist_updated_at?->toIso8601ZuluString(),
            'scope' => 'me',
        ];
    }

    /** @param string[] $iso3 */
    public static function save(User $user, string $tier, array $iso3): ?array
    {
        $list = array_values(array_unique(array_map('strtoupper', $iso3)));
        if (self::scope($user, $tier) === 'team') {
            $team = $user->team;
            abort_unless($team, 409, 'This account is not on a team.');
            $team->forceFill(['watchlist' => $list, 'watchlist_updated_at' => now()])->save();
        } else {
            $user->forceFill(['watchlist' => $list, 'watchlist_updated_at' => now()])->save();
        }

        return self::doc($user->fresh(), $tier);
    }
}
