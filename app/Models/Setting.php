<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        $row = static::query()->where('key', $key)->first();
        if (! $row) {
            return $default;
        }
        try {
            return Crypt::decryptString($row->value);
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function put(string $key, ?string $value): void
    {
        if ($value === null || $value === '') {
            static::query()->where('key', $key)->delete();

            return;
        }
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => Crypt::encryptString($value)],
        );
    }

    public static function has(string $key): bool
    {
        return static::query()->where('key', $key)->exists();
    }

    /** Last four characters, for confirming a secret is stored without showing it. */
    public static function hint(string $key): ?string
    {
        $value = static::get($key);
        if ($value === null || $value === '') {
            return null;
        }

        return str_repeat('•', 8).substr($value, -4);
    }
}
