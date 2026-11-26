<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One voucher (any of the six entry types). Columns mirror the spreadsheet's Master All header;
 * see config/voucher_columns.php for header => column. Amounts are the stored (computed) amounts.
 */
class Voucher extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['only_in_older_export' => 'boolean'];
    }

    public function range(): BelongsTo
    {
        return $this->belongsTo(User::class, 'range_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VoucherItem::class)->orderBy('line_no');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(VoucherVersion::class)->orderByDesc('id');
    }

    /** Vouchers a user may see: a range sees its own; a division sees its ranges + itself; admin sees all. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }
        if ($user->isDivision()) {
            return $query->whereIn('range_id', User::where('division_id', $user->id)->pluck('id')->push($user->id));
        }

        return $query->where('range_id', $user->id);
    }

    public function scopeForYear(Builder $query, int $financialYearId): Builder
    {
        return $query->where('financial_year_id', $financialYearId);
    }
}
