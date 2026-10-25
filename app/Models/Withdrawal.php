<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['user_id', 'name', 'email', 'address', 'order_reference', 'ordered_on', 'message', 'status', 'staff_note', 'handled_at', 'handled_by'])]
class Withdrawal extends Model
{
    protected function casts(): array
    {
        return ['ordered_on' => 'date', 'handled_at' => 'datetime'];
    }
}
