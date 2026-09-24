<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'division_id',
        'bill_no',
        'advice_no',
        'advice_date',
        'bill_type',
        'bill_amount',
        'treasury_name',
        'token_no',
        'token_date',
        'tv_no',
        'tv_date',
        'payment_ref_no',
        'status',
        'remarks',
    ];

    protected $casts = [
        'advice_date' => 'date',
        'token_date' => 'date',
        'tv_date' => 'date',
        'bill_amount' => 'decimal:2',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(User::class, 'division_id');
    }
}
