<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DivisionTenderParty extends Model
{
    protected $fillable = ['division_id', 'serial_number', 'data', 'attachments', 'party_status'];

    protected function casts(): array
    {
        return ['data' => 'array', 'attachments' => 'array'];
    }

    public function toFormArray(): array
    {
        return array_merge($this->data ?? [], [
            'party_sr_no' => $this->serial_number,
            'party_status' => $this->party_status,
        ]);
    }
}
