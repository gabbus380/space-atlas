<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

#[Fillable(['code', 'name', 'months', 'plan', 'max_redemptions', 'expires_on', 'active', 'notes', 'created_by'])]
class Coupon extends Model
{
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'expires_on' => 'date',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function label(): string
    {
        return match ((int) $this->months) {
            12 => 'First year free',
            3 => '3 months free',
            1 => '1 month free',
            default => $this->months.' months free',
        };
    }

    /** Grant the free period. Throws a validation exception when the code cannot be used. */
    public function redeem(User $user): void
    {
        if (! $this->active) {
            throw ValidationException::withMessages(['code' => 'This coupon is switched off.']);
        }
        if ($this->expires_on && $this->expires_on->endOfDay()->isPast()) {
            throw ValidationException::withMessages(['code' => 'This coupon has expired.']);
        }
        if ($this->max_redemptions !== null && $this->times_redeemed >= $this->max_redemptions) {
            throw ValidationException::withMessages(['code' => 'This coupon has been used up.']);
        }
        if ($this->redemptions()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['code' => 'You have already used this coupon.']);
        }

        if ($this->plan === 'teams' && ! ($user->ownedTeam ?: $user->team)) {
            throw ValidationException::withMessages(['code' => 'Create or join a team before using a Teams coupon.']);
        }

        DB::transaction(function () use ($user) {
            $this->redemptions()->create(['user_id' => $user->id]);
            $this->increment('times_redeemed');

            if ($this->plan === 'teams') {
                $team = $user->ownedTeam ?: $user->team;
                $base = $team->override_until && $team->override_until->endOfDay()->isFuture() ? $team->override_until : today();
                $team->forceFill([
                    'override_until' => $base->copy()->addMonths((int) $this->months),
                    'override_seats' => max((int) $team->override_seats, $team->members()->count(), 1),
                ])->save();

                return;
            }

            $base = $user->override_until && $user->override_until->endOfDay()->isFuture() ? $user->override_until : today();
            $user->forceFill([
                'plan_override' => 'personal',
                'override_until' => $base->copy()->addMonths((int) $this->months),
            ])->save();
        });
    }
}
