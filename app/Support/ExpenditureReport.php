<?php

namespace App\Support;

use App\Models\FinancialYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Expenditure figures shared by the Summary and Monthly reports. Definitions proven against the spreadsheets:
 *   expenditure = payable amount of vouchers APPROVED by the treasury, split by treasury approval date
 *                 P (up to last month) = approved on or before the last day of the previous month
 *                 C (this month)       = approved within the selected month
 *   allotment   = the LATEST Circle entry of the budget code that is dated on/before the end of the month
 *   released    = L.C. issued (lc_entries) on/before the end of the month, per scheme and class
 *   total requirement = allotment (+ manual change, none recorded); remaining = requirement - total expenditure
 * Summary sheet, October: 282/282 budget codes identical for allotment, P and C.
 */
class ExpenditureReport
{
    public const MONTHS = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];

    private const MONTH_NUMBER = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];

    /** @return array{start: string, end: string, prev_end: string} */
    public static function window(int $financialYearId, string $month): array
    {
        $startYear = (int) substr(FinancialYear::findOrFail($financialYearId)->code, 0, 4);
        $i = array_search($month, self::MONTHS, true);
        $i = $i === false ? 0 : $i;
        $start = Carbon::create($i <= 8 ? $startYear : $startYear + 1, self::MONTH_NUMBER[$i], 1);

        return ['start' => $start->toDateString(), 'end' => $start->copy()->endOfMonth()->toDateString(), 'prev_end' => $start->copy()->subDay()->toDateString()];
    }

    /**
     * One row per budget code of the year, in the sheet's own order.
     *
     * @return array<int, object{budget_code: string, operating_head: ?string, scheme: ?string, item: ?string, object_class: ?string, model: ?string, allotment: float, p_exp: float, c_exp: float}>
     */
    public static function codeRows(int $financialYearId, string $month, string $paymentMode = 'ALL'): array
    {
        $w = self::window($financialYearId, $month);
        $filter = in_array($paymentMode, ['IFMS', 'SNA', 'PFMS'], true);
        $m = $filter ? ' AND v.payment_mode = :mode' : '';
        $pCond = self::approvedSql('v', $financialYearId, $month, 'before');
        $cCond = self::approvedSql('v', $financialYearId, $month, 'in');
        // expenditure: one grouped pass over the year's vouchers (was two correlated sub-queries per budget code)
        $sql = "SELECT b.budget_code, b.operating_head, b.scheme, b.item, b.object_class, b.object_code, b.description, b.model,
                  COALESCE((SELECT f.amount FROM allotments_from_circle f WHERE f.budget_code_id = b.id AND (f.allotment_date IS NULL OR DATE(f.allotment_date) <= :end0)
                            ORDER BY f.source_time_stamp DESC, f.id DESC LIMIT 1), 0) AS allotment,
                  COALESCE(e.p_exp, 0) AS p_exp, COALESCE(e.c_exp, 0) AS c_exp
                FROM budget_codes b
                LEFT JOIN (SELECT v.budget_code,
                                  SUM(CASE WHEN {$pCond} THEN v.payable_total_amount ELSE 0 END) AS p_exp,
                                  SUM(CASE WHEN {$cCond} THEN v.payable_total_amount ELSE 0 END) AS c_exp
                           FROM vouchers v WHERE v.financial_year_id = :fyv AND v.treasury_status = 'Approved'{$m} GROUP BY v.budget_code) e ON e.budget_code = b.budget_code
                LEFT JOIN lookups o ON o.financial_year_id = b.financial_year_id AND o.list_name = 'System Order of Budget Codes' AND o.value = b.budget_code
                WHERE b.financial_year_id = :fy AND b.scheme IS NOT NULL   -- a code that exists only in the allotment ledger (2025-26 U168, no scheme, net 0) is not a report line
                ORDER BY COALESCE(o.source_row, 99999), b.budget_code";
        $params = ['end0' => $w['end'], 'fy' => $financialYearId, 'fyv' => $financialYearId];
        if ($filter) {
            $params['mode'] = $paymentMode;
        }

        return array_map(function ($r) {
            $r->allotment = (float) $r->allotment;
            $r->p_exp = (float) $r->p_exp;
            $r->c_exp = (float) $r->c_exp;

            return $r;
        }, DB::select($sql, $params));
    }

    /**
     * SQL condition "passed by the treasury in / before the month" for a vouchers alias.
     * Years that record the treasury DATE (2026-27) use it; the 2025-26 register only has the Approval Month, which is used when the date is empty.
     * $mode 'in' = within the month, 'before' = on or before the last day of the previous month.
     */
    public static function approvedSql(string $a, int $financialYearId, string $month, string $mode): string
    {
        $w = self::window($financialYearId, $month);
        $i = array_search($month, self::MONTHS, true);
        $i = $i === false ? 0 : $i;
        $names = $mode === 'before' ? array_slice(self::MONTHS, 0, $i) : [self::MONTHS[$i]];
        $list = "'".implode("','", $names)."'";   // fixed month names, never user input
        $date = $mode === 'before' ? "$a.treasury_approval_date <= '{$w['prev_end']}'" : "$a.treasury_approval_date BETWEEN '{$w['start']}' AND '{$w['end']}'";

        return "$a.treasury_status = 'Approved' AND ($date OR ($a.treasury_approval_date IS NULL AND $a.treasury_approval_month IN ($list)))";
    }

    /** Query-builder form of approvedSql(): restrict a vouchers query to those passed in ('in') or before ('before') the month. */
    public static function whereApproved($query, int $financialYearId, string $month, string $mode, string $alias = 'vouchers'): void
    {
        $query->whereRaw(self::approvedSql($alias, $financialYearId, $month, $mode));
    }

    /** Month name (Apr..Mar) of the latest treasury approval of the year, by date or - for years without dates - by Approval Month. */
    public static function latestApprovedMonth(int $financialYearId): ?string
    {
        $last = DB::table('vouchers')->where('financial_year_id', $financialYearId)->where('treasury_status', 'Approved')->max('treasury_approval_date');
        if ($last) {
            return Carbon::parse($last)->format('M');
        }
        $present = DB::table('vouchers')->where('financial_year_id', $financialYearId)->where('treasury_status', 'Approved')->whereNotNull('treasury_approval_month')->distinct()->pluck('treasury_approval_month')->all();
        foreach (array_reverse(self::MONTHS) as $m) {
            if (in_array($m, $present, true)) {
                return $m;
            }
        }

        return null;
    }

    /** L.C. issued up to the end of the month: [scheme][class] => amount */
    public static function released(int $financialYearId, string $month): array
    {
        $end = self::window($financialYearId, $month)['end'];
        $out = [];
        foreach (DB::table('lc_entries')->where('financial_year_id', $financialYearId)->where('entry_date', '<=', $end)->groupBy('scheme', 'class')->selectRaw('scheme, class, SUM(amount) AS amt')->get() as $r) {
            $out[$r->scheme][$r->class] = (float) $r->amt;
        }

        return $out;
    }

    /** 'Class-1' codes carry wages / salary of daily wagers. */
    public static function isSalaryCode(object $row): bool
    {
        return $row->object_class === 'Class-1';
    }
}
