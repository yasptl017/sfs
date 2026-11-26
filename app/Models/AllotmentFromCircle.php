<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialYear;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Circle allotment ledger. Each row is a CUMULATIVE restatement: the current allotment of a
 * budget code is its LATEST row (by source_time_stamp), not the sum.
 */
class AllotmentFromCircle extends Model
{
    use BelongsToFinancialYear;

    protected $table = 'allotments_from_circle';

    protected $fillable = ['division_id', 'budget_code_id', 'ledger_sr', 'rate', 'target', 'amount', 'allotment_date', 'source_time_stamp', 'entered_by', 'times_edited'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'rate' => 'decimal:2', 'target' => 'decimal:2'];
    }

    public function budgetCode(): BelongsTo
    {
        return $this->belongsTo(BudgetCode::class);
    }

    /** Legacy view-compat: the array the existing screens read. */
    protected function data(): Attribute
    {
        return Attribute::get(function () {
            $b = $this->budgetCode;

            return [
                'budget_code' => $b?->budget_code, 'allotment_date' => $this->allotment_date ? substr((string) $this->allotment_date, 0, 10) : null,
                'rate' => $this->rate, 'target' => $this->target, 'allotment' => $this->amount,
                'operating_head' => $b?->operating_head, 'scheme' => $b?->scheme, 'object_class' => $b?->object_class, 'object_code' => $b?->object_code,
                'description' => $b?->description, 'item' => $b?->item, 'model' => $b?->model, 'scheme_year' => $b?->scheme_year,
            ];
        });
    }
}
