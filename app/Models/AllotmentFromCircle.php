<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllotmentFromCircle extends Model
{
    protected $table = 'allotments_from_circle';

    protected $fillable = ['division_id', 'budget_code_id', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
