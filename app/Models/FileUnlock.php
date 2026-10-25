<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'iso3', 'month'])]
class FileUnlock extends Model
{
    public static function currentMonth(): string
    {
        return now('UTC')->format('Y-m');
    }
}
