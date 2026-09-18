<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeBillOrderNo extends Model
{
    use HasFactory;

    protected $table = 'change_bill_order_numbers';

    protected $fillable = [
        'division_id',
        'bill_type',
        'advice_no',
        'advice_date',
        'bill_register_no',
        'order_outward_no',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'advice_date' => 'date',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(User::class, 'division_id');
    }
}
