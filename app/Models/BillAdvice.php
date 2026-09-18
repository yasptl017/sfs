<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillAdvice extends Model
{
    protected $table = 'bill_advices';

    protected $fillable = [
        'division_id',
        'advice_no',
        'advice_date',
        'bill_type',
        'total_amount',
        'total_bills_count',
        'selected_entries',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'advice_date' => 'date',
            'total_amount' => 'decimal:2',
            'selected_entries' => 'array',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(User::class, 'division_id');
    }
}
