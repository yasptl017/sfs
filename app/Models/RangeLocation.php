<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RangeLocation extends Model
{
    protected $fillable = ['range_id', 'round', 'beat', 'place', 'hectares', 'place_year'];

    protected function casts(): array
    {
        return ['hectares' => 'decimal:2'];
    }

    public function range(): BelongsTo
    {
        return $this->belongsTo(User::class, 'range_id');
    }
}
