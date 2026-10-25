<?php

namespace App\Support;

use App\Models\User;
use Laravel\Paddle\Subscription;

class Billing
{
    /** Checkout needs the Paddle keys and the four price IDs. */
    public static function ready(): bool
    {
        return filled(config('cashier.api_key')) && filled(config('cashier.client_side_token'));
    }

    public static function priceId(string $plan, string $interval): ?string
    {
        return config("atlas.paddle_prices.$plan.$interval") ?: null;
    }

    public static function interval(Subscription $s): ?string
    {
        if ($s->billing_interval) {
            return $s->billing_interval;
        }
        $price = $s->items->first()?->price_id;
        foreach (['personal', 'teams'] as $plan) {
            foreach (['year', 'month'] as $i) {
                if ($price && $price === self::priceId($plan, $i)) {
                    return $i;
                }
            }
        }

        return null;
    }

    public static function quantity(Subscription $s): int
    {
        return (int) $s->items->sum('quantity') ?: 1;
    }

    /** The subscription a user pays for: their Personal plan, or the Teams plan of the team they own. */
    public static function subscriptionFor(User $user, string $plan): ?Subscription
    {
        $s = $user->subscription($plan);

        return $s && $s->valid() ? $s : null;
    }

    public static function founding(?Subscription $s): bool
    {
        return $s && $s->created_at && $s->created_at->lte(\Illuminate\Support\Carbon::parse(config('atlas.founding_until'))->endOfDay());
    }
}
