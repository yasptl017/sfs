<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RangeLocation extends Model
{ use BelongsToFinancialYear;
    protected $fillable = ['sr', 'range_id', 'round', 'beat', 'place', 'hectares', 'place_year'];

    protected function casts(): array
    {
        return ['hectares' => 'decimal:2'];
    }

    public function range(): BelongsTo
    {
        return $this->belongsTo(User::class, 'range_id');
    }
}
