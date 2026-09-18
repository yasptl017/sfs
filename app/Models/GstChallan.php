<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GstChallan extends Model
{
    protected $fillable = [
        'division_id',
        'bill_register_no',
        'advice_no',
        'gst_amount',
        'gst_challan_no',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'gst_amount' => 'decimal:2',
        ];
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(User::class, 'division_id');
    }
}
