<?php

namespace App\Support;

use App\Models\Voucher;
use App\Models\VoucherVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Final voucher numbers (SR- / CONT- / SNA-), as the sheets use them: one running series per bill type and per MONTH of the
 * treasury approval (or of the advice date while not yet approved) - every month starts again at 1. Verified on the migrated
 * year: each month's series is 1..n without gaps (SNA numbers are sometimes shared by two vouchers in the sheets; new numbers are one per voucher).
 */
class FinalVoucherNumbering
{
    public const PREFIX = ['Simple Receipt' => 'SR', 'Contingency' => 'CONT', 'SNA' => 'SNA'];

    /** The date that decides the month of the series. */
    public static function seriesDate(Voucher $v): ?string
    {
        $d = $v->treasury_approval_date ?? $v->advice_date;

        return $d ? Carbon::parse($d)->toDateString() : null;
    }

    /** Highest number already used in that series and month. */
    public static function highest(string $prefix, ?string $date, int $financialYearId): int
    {
        $q = Voucher::query()->forYear($financialYearId)->where('voucher_no_final', 'like', $prefix.'-%');
        if ($date !== null) {
            $c = Carbon::parse($date);
            $q->whereRaw('COALESCE(treasury_approval_date, advice_date) BETWEEN ? AND ?', [$c->copy()->startOfMonth()->toDateString(), $c->copy()->endOfMonth()->toDateString()]);
        }

        return (int) $q->pluck('voucher_no_final')->map(fn ($n) => (int) substr($n, strlen($prefix) + 1))->max();
    }

    /**
     * Numbers the given (unnumbered, already ordered) vouchers; the series continues after the highest number of the same type and month.
     *
     * @param  Collection<int, Voucher>  $vouchers
     * @return array{assigned: int, skipped: int}
     */
    public static function assign(Collection $vouchers, int $financialYearId): array
    {
        $next = [];
        $assigned = $skipped = 0;
        foreach ($vouchers as $v) {
            $prefix = self::PREFIX[$v->ifms_bill_type] ?? null;
            if ($prefix === null) {
                $skipped++;

                continue;
            }
            $date = self::seriesDate($v);
            $key = $prefix.'|'.($date ? substr($date, 0, 7) : '-');
            $next[$key] ??= self::highest($prefix, $date, $financialYearId);
            $no = $prefix.'-'.(++$next[$key]);

            VoucherVersion::create(['financial_year_id' => $v->financial_year_id, 'range_id' => $v->range_id, 'entry_type' => $v->entry_type, 'serial_number' => $v->serial_number, 'voucher_id' => $v->id,
                'version_kind' => 'advice:final_number', 'source_file' => 'division processing', 'source_rows' => [],
                'snapshot' => json_encode(['rows' => [['r' => null, 'c' => array_filter(['Voucher No. Final' => $v->voucher_no_final], fn ($x) => $x !== null)]]], JSON_UNESCAPED_UNICODE),
                'differs_from_live' => true, 'diff_fields' => ['Voucher No. Final']]);
            $v->forceFill(['voucher_no_final' => $no])->save();
            $assigned++;
        }

        return ['assigned' => $assigned, 'skipped' => $skipped];
    }
}
