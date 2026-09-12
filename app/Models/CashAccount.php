<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashAccount extends Model
{
    protected $fillable = ['division_id', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }
}
