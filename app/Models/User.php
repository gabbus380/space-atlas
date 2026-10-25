<?php

namespace App\Models;

use App\Support\Staff;
use App\Models\ApiKey;
use App\Models\ApiRequest;
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
use Illuminate\Support\Str;
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
            'show_on_organization' => 'boolean',
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

    /** Keys issued to this account, by contact email or by an API request they sent. */
    public function apiKeys()
    {
        $linked = ApiRequest::query()->where('user_id', $this->id)->whereNotNull('api_key_id')->pluck('api_key_id');

        return ApiKey::query()
            ->where(function ($query) use ($linked) {
                $query->whereRaw('lower(contact_email) = ?', [Str::lower($this->email)]);
                if ($linked->isNotEmpty()) {
                    $query->orWhereIn('id', $linked);
                }
            })
            ->latest()
            ->get();
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

    /** Admin, manager, sales, or data analyst. Older rows used only is_admin. */
    public function staffRole(): ?string
    {
        if (in_array($this->staff_role, array_keys(Staff::ROLES), true)) {
            return $this->staff_role;
        }

        return $this->is_admin ? Staff::ADMIN : null;
    }

    public function assignStaffRole(?string $role): void
    {
        $this->forceFill([
            'staff_role' => $role,
            'is_admin' => $role !== null,
        ])->save();
    }
}
