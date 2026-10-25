<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

class EmailDispatch extends Model
{
    protected $fillable = ['dedupe'];

    /**
     * Run the send once for this key. A failed send releases the key so a later attempt can retry.
     */
    public static function once(string $dedupe, callable $send): bool
    {
        try {
            $row = static::query()->create(['dedupe' => $dedupe]);
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        try {
            $send();
        } catch (Throwable $e) {
            $row->delete();
            throw $e;
        }

        return true;
    }
}
