<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialYear;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Moves `amount` of allotment from one budget code to another. */
class AllotmentAdjustment extends Model
{
    use BelongsToFinancialYear;

    protected $fillable = ['division_id', 'from_budget_code_id', 'to_budget_code_id', 'ledger_sr', 'amount', 'remark', 'source_time_stamp', 'entered_by', 'times_edited'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function fromBudget(): BelongsTo
    {
        return $this->belongsTo(BudgetCode::class, 'from_budget_code_id');
    }

    public function toBudget(): BelongsTo
    {
        return $this->belongsTo(BudgetCode::class, 'to_budget_code_id');
    }

    protected function data(): Attribute
    {
        return Attribute::get(fn () => [
            'from' => ['budget_code' => $this->fromBudget?->budget_code],
            'to' => ['budget_code' => $this->toBudget?->budget_code],
        ]);
    }
}
