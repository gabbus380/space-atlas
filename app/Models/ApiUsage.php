<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiUsage extends Model
{
    protected $table = 'api_usage';

    public $timestamps = false;

    protected $fillable = ['api_key_id', 'day', 'count'];
}
