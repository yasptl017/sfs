<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
