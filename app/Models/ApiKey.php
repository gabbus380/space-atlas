<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'contact_email', 'daily_quota', 'max_users', 'expires_on', 'notes'])]
#[Hidden(['key_hash'])]
class ApiKey extends Model
{
    protected function casts(): array
    {
        return ['active' => 'boolean', 'expires_on' => 'date', 'last_used_at' => 'datetime'];
    }

    public function usage(): HasMany
    {
        return $this->hasMany(ApiUsage::class);
    }

    /** Create a key and return [model, plain key]. The plain key is shown once and never stored. */
    public static function issue(array $attributes): array
    {
        $plain = 'ssa_live_'.Str::random(40);
        $key = new static($attributes);
        $key->prefix = substr($plain, 0, 13);
        $key->key_hash = hash('sha256', $plain);
        $key->active = true;
        $key->save();

        return [$key, $plain];
    }

    public static function findByPlain(string $plain): ?self
    {
        return static::where('key_hash', hash('sha256', $plain))->first();
    }

    public function isUsable(): bool
    {
        return $this->active && (! $this->expires_on || $this->expires_on->endOfDay()->isFuture());
    }

    public function usedToday(): int
    {
        return (int) $this->usage()->where('day', now('UTC')->toDateString())->value('count');
    }
}
