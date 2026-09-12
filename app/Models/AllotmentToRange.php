<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllotmentToRange extends Model
{
    protected $table = 'allotments_to_range';

    protected $fillable = ['division_id', 'range_id', 'budget_code_id', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
