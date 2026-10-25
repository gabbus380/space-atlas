<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'iso3', 'text', 'updated_by'])]
class CountryNote extends Model
{
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function toDoc(): array
    {
        return [
            'iso3' => $this->iso3,
            'text' => $this->text,
            'updated_at' => $this->updated_at?->toIso8601ZuluString(),
            'by' => $this->author?->name,
        ];
    }
}
