<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialYear;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tender-party register (the sheet's "PartyCode"). Typed columns; `data` is a compatibility view for the existing form. */
class DivisionTenderParty extends Model
{
    use BelongsToFinancialYear;

    /** legacy form key => typed column (keys not listed here use the same name as the column) */
    public const KEY_TO_COLUMN = [
        'additional_sgst' => 'sgst', 'additional_cgst' => 'cgst', 'additional_igst' => 'igst', 'additional_labour_cess' => 'labour_cess',
        'emd_date' => 'emd_dd_cheque_date', 'emd_mode' => 'emd_by_dd_cheque', 'emd_no' => 'emd_dd_no_cheque_no', 'emd_refund_date' => 'emd_refund_to_party_date',
        'security_amount' => 'security_deposite_amount', 'security_challan_no' => 'security_deposite_remittance_chalan_no',
        'security_date' => 'security_deposite_dd_fd_remittance_deposite_date', 'security_given_date' => 'security_deposite_given_to_party_date',
        'security_mode' => 'security_deposite_dd_fd_remittance_deposite', 'security_no' => 'security_deposite_dd_no_fd_remittance_deposite_no',
        'security_received_date' => 'security_deposite_received_date', 'security_remittance_date' => 'security_deposite_remittance_date',
        'security_submitted' => 'security_deposite_submitted_to_remmitance', 'tender_fee_date' => 'tender_fee_dd_cheque_date',
        'tender_fee_mode' => 'tender_fee_by_dd_cheque', 'tender_fee_no' => 'tender_fee_dd_no_cheque_no', 'tender_validity_date' => 'tender_validity_up_to_date',
    ];

    private const SAME = ['account_no', 'approved_percent', 'bank_name', 'branch', 'deduction_cgst', 'deduction_igst', 'deduction_labour_cess', 'deduction_sgst', 'deposit_deduction',
        'emd_amount', 'gst_no', 'ifsc', 'link_of_doc', 'pan_card_no', 'party_aadhaar_no', 'party_address', 'party_approval_date', 'party_approval_no', 'party_code', 'party_email',
        'party_mobile_no', 'party_name', 'round', 'small_description', 'tds', 'tender_code', 'tender_fee_amount', 'tender_mode', 'tender_no', 'tender_opening_date', 'tender_type'];

    private const DATE_COLUMNS = ['party_approval_date', 'tender_opening_date', 'tender_validity_up_to_date', 'tender_fee_dd_cheque_date', 'emd_dd_cheque_date',
        'emd_refund_to_party_date', 'security_deposite_dd_fd_remittance_deposite_date', 'security_deposite_received_date', 'security_deposite_remittance_date', 'security_deposite_given_to_party_date'];

    protected $fillable = ['division_id', 'serial_number', 'data', 'attachments', 'party_status'];

    protected function casts(): array
    {
        return ['attachments' => 'array'];
    }

    public function range(): BelongsTo
    {
        return $this->belongsTo(User::class, 'range_id');
    }

    private static function columnFor(string $key): string
    {
        return self::KEY_TO_COLUMN[$key] ?? $key;
    }

    protected function data(): Attribute
    {
        return Attribute::make(
            get: function () {
                $out = ['range' => $this->range_name ?? $this->range?->name];
                foreach (array_merge(self::SAME, array_keys(self::KEY_TO_COLUMN)) as $key) {
                    $col = self::columnFor($key);
                    $v = $this->getAttributeValue($col);
                    $out[$key] = in_array($col, self::DATE_COLUMNS, true) && $v ? substr((string) $v, 0, 10) : $v;
                }

                return $out;
            },
            set: function (array $data) {
                $attrs = [];
                foreach ($data as $key => $value) {
                    if ($key === 'range') {
                        $name = $value === null || $value === '' ? null : (string) $value;
                        $attrs['range_name'] = $name;
                        $attrs['range_id'] = $name ? User::whereIn('role', ['range', 'division'])->where('name', $name)->value('id') : null;

                        continue;
                    }
                    $attrs[self::columnFor($key)] = $value === '' ? null : $value;
                }

                return $attrs;
            },
        );
    }

    public function toFormArray(): array
    {
        return array_merge($this->data ?? [], ['party_sr_no' => $this->serial_number, 'party_status' => $this->party_status]);
    }
}
