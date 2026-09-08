<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DWagerArrearsEntry extends Model
{
    protected $fillable = ['range_id', 'serial_number', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function toFormArray(): array
    {
        return array_merge($this->data ?? [], [
            'entry_sr_no' => $this->serial_number,
        ]);
    }
}
