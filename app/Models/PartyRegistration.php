<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartyRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'range_id',
        'serial_number',
        'party_code',
        'round',
        'approved_percent',
        'small_description',
        'party_name',
        'pan_card_no',
        'gst_no',
        'bank_name',
        'account_no',
        'ifsc',
        'branch',
        'deduction_sgst',
        'deduction_cgst',
        'deduction_igst',
        'deduction_labour_cess',
        'deposit_deduction',
        'tds',
        'party_approval_no',
        'party_aadhaar_no',
        'party_mobile_no',
        'party_email',
        'link_of_doc',
        'party_address',
        'party_status',
    ];

    protected function casts(): array
    {
        return [
            'approved_percent' => 'decimal:2',
            'deduction_sgst' => 'decimal:2',
            'deduction_cgst' => 'decimal:2',
            'deduction_igst' => 'decimal:2',
            'deduction_labour_cess' => 'decimal:2',
            'deposit_deduction' => 'decimal:2',
            'tds' => 'decimal:2',
        ];
    }

    public function range(): BelongsTo
    {
        return $this->belongsTo(User::class, 'range_id');
    }

    public function toFormArray(): array
    {
        return [
            'party_sr_no' => $this->serial_number,
            'party_code' => $this->party_code,
            'round' => $this->round,
            'approved_percent' => $this->approved_percent,
            'small_description' => $this->small_description,
            'party_name' => $this->party_name,
            'pan_card_no' => $this->pan_card_no,
            'gst_no' => $this->gst_no,
            'bank_name' => $this->bank_name,
            'account_no' => $this->account_no,
            'ifsc' => $this->ifsc,
            'branch' => $this->branch,
            'deduction_sgst' => $this->deduction_sgst,
            'deduction_cgst' => $this->deduction_cgst,
            'deduction_igst' => $this->deduction_igst,
            'deduction_labour_cess' => $this->deduction_labour_cess,
            'deposit_deduction' => $this->deposit_deduction,
            'tds' => $this->tds,
            'party_approval_no' => $this->party_approval_no,
            'party_aadhaar_no' => $this->party_aadhaar_no,
            'party_mobile_no' => $this->party_mobile_no,
            'party_email' => $this->party_email,
            'link_of_doc' => $this->link_of_doc,
            'party_address' => $this->party_address,
            'party_status' => $this->party_status,
        ];
    }
}
