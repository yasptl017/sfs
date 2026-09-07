<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WlBeneficiary extends Model
{
    protected $fillable = ['range_id', 'serial_number', 'wl_bene_code', 'round', 'beat', 'yojana_name', 'full_name', 'name_gujarati', 'mobile_no', 'gender', 'address', 'pin_code', 'category', 'taluka', 'district', 'village', 'survey_block_no', 'id_type', 'id_no', 'gps_n', 'gps_e', 'gps', 'selection_year', 'remark', 'link_of_doc', 'attachments', 'party_status'];

    protected function casts(): array
    {
        return ['attachments' => 'array'];
    }

    public function range(): BelongsTo
    {
        return $this->belongsTo(User::class, 'range_id');
    }
}