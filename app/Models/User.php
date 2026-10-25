<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Paddle\Billable;

#[Fillable(['name', 'email', 'password', 'organization', 'marketing_opt_in', 'terms_accepted_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'marketing_opt_in' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'team_joined_at' => 'datetime',
            'watchlist' => 'array',
            'watchlist_updated_at' => 'datetime',
            'override_until' => 'date',
            'last_seen_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function ownedTeam(): HasOne
    {
        return $this->hasOne(Team::class, 'owner_id');
    }

    public function unlocks(): HasMany
    {
        return $this->hasMany(FileUnlock::class);
    }

    /** Complimentary Personal access granted by EMMA, still running. */
    public function hasPersonalOverride(): bool
    {
        return $this->plan_override === 'personal' && $this->override_until && $this->override_until->endOfDay()->isFuture();
    }

    public function personalSubscription(): ?\Laravel\Paddle\Subscription
    {
        $s = $this->subscription('personal');

        return $s && $s->valid() ? $s : null;
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0] ?: $this->name;
    }
}
