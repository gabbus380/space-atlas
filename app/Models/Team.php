<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Paddle\Subscription;

#[Fillable(['name', 'owner_id'])]
class Team extends Model
{
    protected function casts(): array
    {
        return [
            'watchlist' => 'array',
            'watchlist_updated_at' => 'datetime',
            'override_until' => 'date',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(User::class)->orderBy('team_joined_at')->orderBy('id');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CountryNote::class);
    }

    public function hasOverride(): bool
    {
        return $this->override_until && $this->override_until->endOfDay()->isFuture();
    }

    /** The owner's Teams subscription while it is valid (active, trial or grace period). */
    public function subscription(): ?Subscription
    {
        $s = $this->owner?->subscription('teams');

        return $s && $s->valid() ? $s : null;
    }

    public function isActive(): bool
    {
        return $this->hasOverride() || $this->subscription() !== null;
    }

    public function seats(): int
    {
        $paid = (int) ($this->subscription()?->items()->sum('quantity') ?? 0);
        $comp = $this->hasOverride() ? (int) $this->override_seats : 0;

        return max($paid, $comp);
    }

    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->whereNull('accepted_at')->where('expires_at', '>', now());
    }

    public function seatsUsed(): int
    {
        return $this->members()->count() + $this->pendingInvitations()->count();
    }

    /** Members keep their seat in the order they joined, so a smaller seat count drops the newest first. */
    public function hasSeatFor(User $user): bool
    {
        $ids = $this->members()->limit(max(1, $this->seats()))->pluck('id');

        return $ids->contains($user->id);
    }
}
