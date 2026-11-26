<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialYear;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Allotment to a range. Each row is an INCREMENT (may be negative): a range's allotment is the SUM of its rows. */
class AllotmentToRange extends Model
{
    use BelongsToFinancialYear;

    protected $table = 'allotments_to_range';

    protected $fillable = ['division_id', 'range_id', 'budget_code_id', 'ledger_sr', 'target', 'amount', 'remark', 'source_time_stamp', 'entered_by', 'times_edited'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'target' => 'decimal:2'];
    }

    public function budgetCode(): BelongsTo
    {
        return $this->belongsTo(BudgetCode::class);
    }

    public function range(): BelongsTo
    {
        return $this->belongsTo(User::class, 'range_id');
    }

    protected function data(): Attribute
    {
        return Attribute::get(fn () => ['budget_code' => $this->budgetCode?->budget_code, 'target' => $this->target ?? 0, 'allotment' => $this->amount ?? 0, 'remark' => $this->remark]);
    }
}
