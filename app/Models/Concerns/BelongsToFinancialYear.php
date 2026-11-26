<?php

namespace App\Models\Concerns;

use App\Support\CurrentFinancialYear;
use Illuminate\Database\Eloquent\Builder;

/** Scopes every query to the signed-in user's financial year and stamps new rows with it. */
trait BelongsToFinancialYear
{
    protected static function bootBelongsToFinancialYear(): void
    {
        static::addGlobalScope('financial_year', function (Builder $builder) {
            $builder->where($builder->getModel()->getTable().'.financial_year_id', CurrentFinancialYear::id());
        });
        static::creating(function ($model) {
            $model->financial_year_id ??= CurrentFinancialYear::id();
        });
    }
}
