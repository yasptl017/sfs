<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialYear;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CashAccount extends Model
{
    use BelongsToFinancialYear;

    protected $fillable = ['division_id', 'range_id', 'range_name', 'serial_number', 'cash_account_month', 'designation', 'round_range', 'details_of_recovery',
        'designation_of_recoverer', 'recovery_officer_name', 'recovery_type', 'recovery_date', 'bank_deposit_date', 'bank_name_branch', 'challan_no', 'amount'];

    protected function casts(): array
    {
        return ['recovery_date' => 'date', 'bank_deposit_date' => 'date', 'amount' => 'decimal:2'];
    }

    /** The sheet keeps the month as text ("Apr-26"); the form works with Y-m. */
    public static function monthToText(?string $ym): ?string
    {
        return $ym ? Carbon::createFromFormat('!Y-m', $ym)->format('M-y') : null;
    }

    public static function monthToInput(?string $text): ?string
    {
        if (! $text) {
            return null;
        }
        try {
            return Carbon::createFromFormat('!M-y', $text)->format('Y-m');
        } catch (\Throwable) {
            return $text;
        }
    }

    protected function data(): Attribute
    {
        return Attribute::get(fn () => [
            'cash_account_month' => self::monthToInput($this->cash_account_month), 'designation' => $this->designation, 'round_range' => $this->round_range,
            'recovery_details' => $this->details_of_recovery, 'recoverer_designation' => $this->designation_of_recoverer,
            'recovery_officer_name' => $this->recovery_officer_name, 'recovery_type' => $this->recovery_type,
            'recovery_date' => $this->recovery_date?->toDateString(), 'bank_deposit_date' => $this->bank_deposit_date?->toDateString(),
            'bank_name_branch' => $this->bank_name_branch, 'challan_no' => $this->challan_no, 'amount' => $this->amount,
        ]);
    }
}
