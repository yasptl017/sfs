<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CurrentFinancialYear;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Allotment vs expenditure per budget code and office. Definitions proven against the spreadsheet's Budget Allotment sheet
 * (3,948 of 3,948 code x office cells identical):
 *   allotment = SUM of allotments given to that office for the code
 *   booked    = payable of vouchers that are part of an advice ("Expenditure Booked")
 *   entered   = payable of vouchers that are not rejected ("Data entered till now")
 *   remaining = allotment - entered
 * Vouchers flagged "only in older export" are included (they are live records) unless $includeOlderOnly is false.
 */
class PositionReportController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $fy = CurrentFinancialYear::id();
        $rangeIds = $user->isAdmin() ? null : ($user->isDivision() ? User::where('division_id', $user->id)->pluck('id')->push($user->id)->all() : [$user->id]);
        $all = collect(self::rows($fy, $rangeIds));
        $rows = $all;

        if (($rid = $request->query('range_id')) && ! $user->isRange()) {
            $rows = $rows->where('range_id', (int) $rid);
        }
        if ($term = mb_strtolower(trim((string) $request->query('q')))) {
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r->budget_code.' '.$r->scheme.' '.$r->item.' '.$r->model), $term));
        }
        if ($request->boolean('overspent')) {
            $rows = $rows->filter(fn ($r) => $r->entered > $r->allotment + 0.5);
        }
        $rows = $rows->values();
        $totals = ['allotment' => $rows->sum('allotment'), 'entered' => $rows->sum('entered'), 'booked' => $rows->sum('booked'), 'remaining' => $rows->sum('allotment') - $rows->sum('entered')];
        $page = LengthAwarePaginator::resolveCurrentPage();
        $paged = new LengthAwarePaginator($rows->forPage($page, 50)->values(), $rows->count(), 50, $page, ['path' => $request->url(), 'query' => $request->query()]);
        $older = DB::table('vouchers')->where('financial_year_id', $fy)->where('only_in_older_export', 1)->when($rangeIds, fn ($q) => $q->whereIn('range_id', $rangeIds))->sum('payable_total_amount');

        return view('admin.reports.position', [
            'rows' => $paged, 'totals' => $totals, 'filters' => $request->query(), 'olderAmount' => (float) $older,
            'ranges' => $user->isRange() ? collect() : User::whereIn('id', $all->pluck('range_id')->unique())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** @return array<int, object> */
    public static function rows(int $fy, ?array $rangeIds = null, bool $includeOlderOnly = true): array
    {
        $older = $includeOlderOnly ? '' : ' AND v.only_in_older_export = 0';
        $scope = $rangeIds !== null ? ' AND p.range_id IN ('.implode(',', array_map('intval', $rangeIds) ?: [0]).')' : '';
        // one aggregation pass per source instead of three correlated sub-queries per code x office (15 s -> well under 1 s)
        $sql = "SELECT p.range_id, u.name AS range_name, b.budget_code, b.scheme, b.item, b.model,
                  COALESCE(a.amt, 0) AS allotment, COALESCE(e.entered, 0) AS entered, COALESCE(e.booked, 0) AS booked
                FROM (SELECT budget_code_id, range_id FROM allotments_to_range WHERE financial_year_id = :fy4
                      UNION SELECT b2.id, v2.range_id FROM vouchers v2 JOIN budget_codes b2 ON b2.budget_code = v2.budget_code AND b2.financial_year_id = v2.financial_year_id WHERE v2.financial_year_id = :fy5) p
                JOIN budget_codes b ON b.id = p.budget_code_id JOIN users u ON u.id = p.range_id
                LEFT JOIN (SELECT budget_code_id, range_id, SUM(amount) AS amt FROM allotments_to_range WHERE financial_year_id = :fy1 GROUP BY budget_code_id, range_id) a
                       ON a.budget_code_id = p.budget_code_id AND a.range_id = p.range_id
                LEFT JOIN (SELECT v.budget_code, v.range_id,
                                  SUM(CASE WHEN v.rejected_reason IS NULL THEN v.payable_total_amount ELSE 0 END) AS entered,
                                  SUM(CASE WHEN v.advice_no IS NOT NULL THEN v.payable_total_amount ELSE 0 END) AS booked
                           FROM vouchers v WHERE v.financial_year_id = :fy2{$older} GROUP BY v.budget_code, v.range_id) e
                       ON e.budget_code = b.budget_code AND e.range_id = p.range_id
                WHERE 1 = 1{$scope} ORDER BY b.budget_code, u.name";

        return array_map(function ($r) {
            $r->allotment = (float) $r->allotment;
            $r->entered = (float) $r->entered;
            $r->booked = (float) $r->booked;
            $r->remaining = $r->allotment - $r->entered;

            return $r;
        }, DB::select($sql, ['fy1' => $fy, 'fy2' => $fy, 'fy4' => $fy, 'fy5' => $fy]));
    }
}
