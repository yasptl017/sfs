<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialYear;
use Illuminate\Database\Eloquent\Model;

class LcEntry extends Model
{
    use BelongsToFinancialYear;

    protected $fillable = ['division_id', 'serial_number', 'scheme', 'class', 'entry_date', 'amount', 'source_time_stamp'];

    protected function casts(): array
    {
        return ['entry_date' => 'date', 'amount' => 'decimal:2'];
    }
}
