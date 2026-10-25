<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'name', 'email', 'organization', 'job_title', 'account_tier', 'created_account', 'people', 'message', 'status', 'staff_note', 'handled_by', 'handled_at', 'api_key_id'])]
class ApiRequest extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'reviewing' => 'In review',
        'approved' => 'Approved',
        'declined' => 'Declined',
    ];

    protected function casts(): array
    {
        return [
            'created_account' => 'boolean',
            'handled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
