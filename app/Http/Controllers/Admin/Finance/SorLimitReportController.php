<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BudgetCode;
use App\Models\FinancialYear;
use App\Models\RangeLocation;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * SOR limit report: every voucher item that carries a rate-list code, compared with the sanctioned reference recorded in the data.
 *  - Rate: the SOR rate in force on the work date (dated SOR rates), else the approved tender rate, else the estimate rate.
 *  - Quantity / amount limit: only where an estimate line exists for the code (estimate quantity and amount, compared with the
 *    range's cumulative billing of that code in the year). Nothing is invented: where no reference exists the figures show "-".
 */
class SorLimitReportController extends Controller
{
    protected array $months = [
        'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
        'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar',
    ];

    private const TOL = 0.005;

    public function index(Request $request): View
    {
        $this->authorizeRange($request);

        $rangeUser = $request->user();
        $selectedYear = $this->yearLabel();
        $selectedBudget = (string) $request->query('budget_code', '');
        $selectedMonth = (string) $request->query('month', '');
        $selectedRound = (string) $request->query('round', '');
        $selectedBeat = (string) $request->query('beat', '');
        $selectedPlace = (string) $request->query('place', '');
        $selectedSorCode = (string) $request->query('sor_code', '');
        $reportMode = (string) $request->query('report_mode', 'detailed'); // 'detailed', 'summary', 'violations'

        return view('admin.finance.sor-limit-report.index', [
            'months' => $this->months,
            'budgetCodes' => BudgetCode::query()->orderBy('budget_code')->get(),
            'locationHierarchy' => $this->getLocationHierarchy($rangeUser),
            'sorList' => $this->sorList($rangeUser),
            'selectedBudget' => $selectedBudget,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedRound' => $selectedRound,
            'selectedBeat' => $selectedBeat,
            'selectedPlace' => $selectedPlace,
            'selectedSorCode' => $selectedSorCode,
            'reportMode' => $reportMode,
            'reportData' => $this->compileSorReportData($rangeUser, $selectedBudget, $selectedMonth, $selectedYear, $selectedRound, $selectedBeat, $selectedPlace, $selectedSorCode, $reportMode),
            'rangeUser' => $rangeUser,
        ]);
    }

    public function filters(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $rangeUser = $request->user();

        return response()->json([
            'success' => true,
            'budget_codes' => BudgetCode::query()->orderBy('budget_code')->get(['id', 'budget_code', 'scheme', 'model', 'scheme_year']),
            'locations' => $this->getLocationHierarchy($rangeUser),
            'sor_items' => $this->sorList($rangeUser),
            'months' => $this->months,
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $validated = $request->validate([
            'budget_code' => ['nullable', 'string', 'max:100'],
            'month' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'string', 'max:50'],
            'round' => ['nullable', 'string', 'max:100'],
            'beat' => ['nullable', 'string', 'max:100'],
            'place' => ['nullable', 'string', 'max:150'],
            'sor_code' => ['nullable', 'string', 'max:100'],
            'report_mode' => ['nullable', 'string', 'in:detailed,summary,violations'],
        ]);
        $reportMode = $validated['report_mode'] ?? 'detailed';

        return response()->json([
            'success' => true,
            'report_mode' => $reportMode,
            'data' => $this->compileSorReportData(
                $request->user(), $validated['budget_code'] ?? '', $validated['month'] ?? '', $validated['year'] ?? '', $validated['round'] ?? '',
                $validated['beat'] ?? '', $validated['place'] ?? '', $validated['sor_code'] ?? '', $reportMode
            ),
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorizeRange($request);

        [$budgetCode, $month, $year, $round, $beat, $place, $sorCode] = $this->queryFilters($request);
        $reportMode = (string) $request->query('report_mode', 'detailed');

        return view('admin.finance.sor-limit-report.print', [
            'data' => $this->compileSorReportData($request->user(), $budgetCode, $month, $year, $round, $beat, $place, $sorCode, $reportMode),
            'reportMode' => $reportMode,
            'budgetCode' => $budgetCode,
            'month' => $month,
            'year' => $year,
            'round' => $round,
            'beat' => $beat,
            'place' => $place,
            'sorCode' => $sorCode,
            'rangeUser' => $request->user(),
            'profile' => $request->user()->getOrCreateOfficeProfile(),
        ]);
    }

    public function export(Request $request): Response
    {
        $this->authorizeRange($request);

        [$budgetCode, $month, $year, $round, $beat, $place, $sorCode] = $this->queryFilters($request);
        $reportMode = (string) $request->query('report_mode', 'detailed');
        $data = $this->compileSorReportData($request->user(), $budgetCode, $month, $year, $round, $beat, $place, $sorCode, $reportMode);
        $n = fn ($x, $d = 2) => $x === null ? '' : number_format((float) $x, $d, '.', '');

        $filename = 'sor_limit_report_'.strtolower($month ?: 'all').'_'.date('Ymd_His').'.csv';

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['GUJARAT FOREST DEPARTMENT - SOR LIMIT REPORT']);
        fputcsv($handle, ['Range:', $request->user()->name, 'Month:', $month ?: 'All', 'Year:', $year, 'Budget Code:', $budgetCode ?: 'All']);
        fputcsv($handle, []);

        if ($reportMode === 'summary') {
            fputcsv($handle, ['Sr', 'SOR Code', 'Work Description', 'Unit', 'Sanctioned Rate (Rs)', 'Sanctioned Limit Qty', 'Total Sanctioned Limit (Rs)', 'Actual Executed Qty', 'Actual Billed Rate (Rs)', 'Total Billed Amount (Rs)', 'Balance Limit (Rs)', 'Utilization %', 'Status']);
            foreach ($data['summary_rows'] as $idx => $r) {
                fputcsv($handle, [$idx + 1, $r['sor_code'], $r['work_description'], $r['unit'], $n($r['sanctioned_rate']), $n($r['sanctioned_qty'], 3), $n($r['sanctioned_limit']),
                    $n($r['executed_qty'], 3), $n($r['actual_rate']), $n($r['actual_amount']), $n($r['balance_amount']), $r['utilization_percent'] === null ? '' : $r['utilization_percent'].'%', $r['status']]);
            }
        } else {
            fputcsv($handle, ['Sr', 'Voucher No', 'Date', 'Party Name', 'SOR Code', 'Work Description', 'Unit', 'SOR Sanctioned Rate (Rs)', 'Actual Billed Rate (Rs)', 'Sanctioned Qty', 'Billed Qty', 'Sanctioned Limit (Rs)', 'Billed Amount (Rs)', 'Variance/Savings (Rs)', 'Status']);
            foreach ($data['detailed_rows'] as $idx => $r) {
                fputcsv($handle, [$idx + 1, $r['voucher_no'], $r['date'], $r['party_name'], $r['sor_code'], $r['work_description'], $r['unit'], $n($r['sanctioned_rate']), $n($r['actual_rate']),
                    $n($r['sanctioned_qty'], 3), $n($r['actual_qty'], 3), $n($r['sanctioned_limit']), $n($r['actual_amount']), $n($r['variance']), $r['status']]);
            }
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ------------------------------------------------------------------------------------------------ data
    /** @return array{0:string,1:string,2:string,3:string,4:string,5:string,6:string} */
    private function queryFilters(Request $request): array
    {
        return [
            (string) $request->query('budget_code', ''), (string) $request->query('month', ''), $this->yearLabel(), (string) $request->query('round', ''),
            (string) $request->query('beat', ''), (string) $request->query('place', ''), (string) $request->query('sor_code', ''),
        ];
    }

    private function yearLabel(): string
    {
        return (string) (FinancialYear::query()->find(CurrentFinancialYear::id())?->code ?? '');
    }

    private function vouchers(User $rangeUser)
    {
        return Voucher::query()->forYear(CurrentFinancialYear::id())->where('range_id', $rangeUser->id)->whereNull('rejected_reason');
    }

    /** Codes used by the range's voucher items, named from the rate lists (else from the first item description). */
    private function sorList(User $rangeUser): array
    {
        $used = DB::table('voucher_items as i')->join('vouchers as v', 'v.id', '=', 'i.voucher_id')
            ->where('v.financial_year_id', CurrentFinancialYear::id())->where('v.range_id', $rangeUser->id)->whereNull('v.rejected_reason')
            ->whereNotNull('i.sor_code')->where('i.sor_code', '<>', '')
            ->selectRaw('i.sor_code, MIN(i.description) as description, MIN(i.unit) as unit')->groupBy('i.sor_code')->orderBy('i.sor_code')->get();
        $refs = $this->references($rangeUser, $used->pluck('sor_code')->all());

        return $used->map(fn ($u) => [
            'code' => $u->sor_code,
            'name_gu' => $refs[$u->sor_code]['name'] ?? $u->description,
            'name_en' => $u->description,
            'unit' => $refs[$u->sor_code]['unit'] ?? $u->unit,
            'standard_rate' => $refs[$u->sor_code]['latest_rate'] ?? null,
        ])->values()->all();
    }

    /**
     * Reference per code: name, unit, rate source and (for SOR) the dated rate list; estimate quantity/amount where an estimate line exists.
     *
     * @return array<string, array<string, mixed>>
     */
    private function references(User $rangeUser, array $codes): array
    {
        if (! $codes) {
            return [];
        }
        $refs = [];
        foreach (array_chunk($codes, 500) as $chunk) {
            $sorItems = DB::table('sor_items')->whereIn('divi_code', $chunk)->get();
            $allRates = DB::table('sor_rates')->whereIn('sor_item_id', $sorItems->pluck('id'))->orderBy('effective_from')->get(['sor_item_id', 'effective_from', 'rate'])->groupBy('sor_item_id');   // one query, not one per item
            foreach ($sorItems as $s) {
                $rates = ($allRates[$s->id] ?? collect())->map(fn ($r) => ['from' => $r->effective_from, 'rate' => (float) $r->rate])->values()->all();
                $refs[$s->divi_code] = ['source' => 'SOR', 'name' => $s->particular, 'unit' => $s->unit, 'rates' => $rates, 'latest_rate' => $rates ? end($rates)['rate'] : null];
            }
            foreach (DB::table('tender_items')->whereIn('divi_code', $chunk)->orderByRaw('range_id = ? desc', [$rangeUser->id])->get() as $t) {
                $refs[$t->divi_code] ??= ['source' => 'Tender rate', 'name' => $t->particular, 'unit' => $t->unit, 'rate' => $t->rate !== null ? (float) $t->rate : null, 'latest_rate' => $t->rate !== null ? (float) $t->rate : null];
            }
            foreach (DB::table('estimate_items')->whereIn('divi_code', $chunk)->where('range_id', $rangeUser->id)->get() as $e) {
                $refs[$e->divi_code] = ($refs[$e->divi_code] ?? []) + ['name' => $e->particular, 'unit' => $e->unit, 'latest_rate' => $e->rate !== null ? (float) $e->rate : null];
                $refs[$e->divi_code] = array_merge($refs[$e->divi_code], [
                    'source' => 'Estimate', 'rate' => $e->rate !== null ? (float) $e->rate : null, 'est_qty' => $e->quantity !== null ? (float) $e->quantity : null,
                    'est_amount' => $e->amount !== null ? (float) $e->amount : null, 'rates' => [],
                ]);
            }
        }

        return $refs;
    }

    private function sanctionedRate(array $ref, ?string $date): ?float
    {
        if (($ref['source'] ?? '') === 'SOR') {
            $rate = null;
            foreach ($ref['rates'] as $r) {
                if ($date === null || $r['from'] <= $date) {
                    $rate = $r['rate'];
                }
            }

            return $rate;
        }

        return $ref['rate'] ?? null;
    }

    private function compileSorReportData(User $rangeUser, ?string $budgetCode, ?string $month, ?string $year, ?string $round, ?string $beat, ?string $place, ?string $sorCode, string $reportMode): array
    {
        $q = $this->vouchers($rangeUser)->whereHas('items', fn ($i) => $i->whereNotNull('sor_code')->where('sor_code', '<>', ''))
            ->with(['items' => fn ($i) => $i->whereNotNull('sor_code')->where('sor_code', '<>', '')->orderBy('line_no')]);
        if (filled($month)) {
            $q->where('entry_month', $month);
        }
        if (filled($budgetCode)) {
            $q->where('budget_code', $budgetCode);
        }
        foreach (['round' => $round, 'beat' => $beat, 'place' => $place] as $col => $val) {
            if (filled(trim((string) $val))) {
                $q->whereRaw("TRIM($col) = ?", [trim($val)]);
            }
        }
        if (filled($sorCode)) {
            $q->whereHas('items', fn ($i) => $i->where('sor_code', $sorCode));
        }
        $vouchers = $q->orderBy('entry_type')->orderBy('serial_number')->get();

        $codes = $vouchers->flatMap(fn ($v) => $v->items->pluck('sor_code'))->unique()->values()->all();
        $refs = $this->references($rangeUser, $codes);

        // Cumulative billing of each estimate-backed code over the whole year (not only the filtered rows).
        $cumulative = [];
        $estCodes = array_keys(array_filter($refs, fn ($r) => ($r['source'] ?? '') === 'Estimate'));
        if ($estCodes) {
            foreach (DB::table('voucher_items as i')->join('vouchers as v', 'v.id', '=', 'i.voucher_id')
                ->where('v.financial_year_id', CurrentFinancialYear::id())->where('v.range_id', $rangeUser->id)->whereNull('v.rejected_reason')->whereIn('i.sor_code', $estCodes)
                ->selectRaw('i.sor_code, SUM(i.amount) amt, SUM(i.quantity) qty')->groupBy('i.sor_code')->get() as $c) {
                $cumulative[$c->sor_code] = ['amount' => (float) $c->amt, 'qty' => (float) $c->qty];
            }
        }

        $badge = [
            'Exceeded Limit' => 'bg-rose-100 text-rose-800 border-rose-200',
            'Critical (90%+)' => 'bg-amber-100 text-amber-800 border-amber-200',
            'Within Limit' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'Not in rate list' => 'bg-slate-100 text-slate-700 border-slate-200',
        ];

        $detailedRows = [];
        foreach ($vouchers as $v) {
            foreach ($v->items as $it) {
                if (filled($sorCode) && $it->sor_code !== $sorCode) {
                    continue;
                }
                $ref = $refs[$it->sor_code] ?? null;
                $dateRaw = $it->work_start_date ?? $it->work_order_date ?? $v->bill_date;
                $dateIso = $dateRaw ? Carbon::parse($dateRaw)->format('Y-m-d') : null;
                $sRate = $ref ? $this->sanctionedRate($ref, $dateIso) : null;
                $aRate = $it->rate !== null ? (float) $it->rate : null;
                $aQty = $it->quantity !== null ? (float) $it->quantity : null;
                $aAmt = (float) $it->amount;
                $isEstimate = ($ref['source'] ?? '') === 'Estimate' && ($ref['est_amount'] ?? null) !== null;
                $cum = $isEstimate ? ($cumulative[$it->sor_code]['amount'] ?? 0.0) : null;

                $rateViolation = $sRate !== null && $aRate !== null && $aRate > $sRate + self::TOL;
                $limitViolation = $isEstimate && $cum > $ref['est_amount'] + self::TOL;
                $util = $isEstimate && $ref['est_amount'] > 0 ? round($cum / $ref['est_amount'] * 100, 1) : null;

                if ($ref === null || ($sRate === null && ! $isEstimate)) {
                    $status = 'Not in rate list';
                } elseif ($rateViolation || $limitViolation) {
                    $status = 'Exceeded Limit';
                } elseif ($util !== null && $util >= 90.0) {
                    $status = 'Critical (90%+)';
                } else {
                    $status = 'Within Limit';
                }

                $detailedRows[] = [
                    'id' => $v->id,
                    'serial_number' => (int) $v->serial_number,
                    'voucher_no' => $v->voucher_no_final ?: 'Sr. '.$v->serial_number,
                    'date' => $dateRaw ? Carbon::parse($dateRaw)->format('d/m/Y') : '',
                    'entry_type' => $v->entry_type,
                    'party_name' => $v->party_name,
                    'budget_code' => $v->budget_code,
                    'scheme' => $v->scheme,
                    'round' => $v->round,
                    'beat' => $v->beat,
                    'place' => $v->place,
                    'sor_code' => $it->sor_code,
                    'work_description' => trim((string) $it->description) !== '' ? $it->description : ($ref['name'] ?? ''),
                    'unit' => $it->unit ?: ($ref['unit'] ?? ''),
                    'reference' => $ref['source'] ?? '',
                    'sanctioned_rate' => $sRate,
                    'actual_rate' => $aRate,
                    'sanctioned_qty' => $isEstimate ? $ref['est_qty'] : null,
                    'actual_qty' => $aQty,
                    'sanctioned_limit' => $isEstimate ? $ref['est_amount'] : null,
                    'actual_amount' => $aAmt,
                    'cumulative_amount' => $cum,
                    'variance' => $isEstimate ? round($ref['est_amount'] - $cum, 2) : null,
                    'utilization_percent' => $util,
                    'status' => $status,
                    'badge_class' => $badge[$status],
                    'is_violation' => $rateViolation || $limitViolation,
                    'is_rate_violation' => $rateViolation,
                ];
            }
        }

        // Consolidated summary by code (limit counted once per code, billing of the filtered rows).
        $groups = [];
        foreach ($detailedRows as $row) {
            $sc = $row['sor_code'];
            $groups[$sc] ??= [
                'sor_code' => $sc, 'work_description' => $row['work_description'], 'unit' => $row['unit'], 'reference' => $row['reference'],
                'sanctioned_rate' => $row['sanctioned_rate'], 'actual_rate' => $row['actual_rate'], 'sanctioned_qty' => $row['sanctioned_qty'],
                'executed_qty' => 0.0, 'sanctioned_limit' => $row['sanctioned_limit'], 'cumulative_amount' => $row['cumulative_amount'],
                'actual_amount' => 0.0, 'vouchers_count' => 0, 'has_violation' => false, 'has_rate_violation' => false, 'no_reference' => $row['status'] === 'Not in rate list',
            ];
            $groups[$sc]['executed_qty'] += (float) $row['actual_qty'];
            $groups[$sc]['actual_amount'] += $row['actual_amount'];
            $groups[$sc]['vouchers_count']++;
            $groups[$sc]['has_violation'] = $groups[$sc]['has_violation'] || $row['is_violation'];
            $groups[$sc]['has_rate_violation'] = $groups[$sc]['has_rate_violation'] || $row['is_rate_violation'];
        }
        $summaryRows = [];
        foreach ($groups as $g) {
            $hasLimit = $g['sanctioned_limit'] !== null;
            $g['balance_amount'] = $hasLimit ? round($g['sanctioned_limit'] - $g['cumulative_amount'], 2) : null;
            $g['utilization_percent'] = $hasLimit && $g['sanctioned_limit'] > 0 ? round($g['cumulative_amount'] / $g['sanctioned_limit'] * 100, 1) : null;
            if ($g['no_reference']) {
                $g['status'] = 'Not in rate list';
            } elseif ($g['has_violation']) {
                $g['status'] = 'Exceeded Limit';
            } elseif ($g['utilization_percent'] !== null && $g['utilization_percent'] >= 90.0) {
                $g['status'] = 'Critical (90%+)';
            } else {
                $g['status'] = 'Within Limit';
            }
            $g['badge_class'] = $badge[$g['status']];
            $summaryRows[] = $g;
        }

        $displayRows = $reportMode === 'violations' ? array_values(array_filter($detailedRows, fn ($r) => $r['is_violation'])) : $detailedRows;
        $checked = count(array_filter($detailedRows, fn ($r) => $r['status'] !== 'Not in rate list'));
        $violations = count(array_filter($detailedRows, fn ($r) => $r['is_violation']));

        $totals = [
            'total_sanctioned_limit' => array_sum(array_filter(array_column($summaryRows, 'sanctioned_limit'), fn ($x) => $x !== null)),
            'total_actual_expenditure' => array_sum(array_column($detailedRows, 'actual_amount')),
            'total_savings_balance' => array_sum(array_filter(array_column($summaryRows, 'balance_amount'), fn ($x) => $x !== null)),
            'total_items_count' => count($detailedRows),
            'items_checked' => $checked,
            'items_not_in_rate_list' => count($detailedRows) - $checked,
            'total_violations_count' => $violations,
            'compliance_rate' => $checked > 0 ? round(($checked - $violations) / $checked * 100, 1) : 100,
        ];

        $budgetObj = filled($budgetCode) ? BudgetCode::query()->where('budget_code', $budgetCode)->first() : null;

        return [
            'budget_code' => $budgetCode ?: 'All Budget Codes',
            'scheme_name' => $budgetObj?->scheme ?? ($budgetCode ? '-' : 'All schemes'),
            'month' => $month ?: 'All months',
            'year' => $year ?: $this->yearLabel(),
            'round' => $round ?: 'All Rounds',
            'beat' => $beat ?: 'All Beats',
            'place' => $place ?: 'All Sites',
            'sor_code' => $sorCode ?: 'All SOR Codes',
            'report_mode' => $reportMode,
            'range_name' => $rangeUser->name,
            'division_name' => ($rangeUser->division ?? $rangeUser)->getOrCreateOfficeProfile()->display_name,
            'detailed_rows' => $displayRows,
            'summary_rows' => $summaryRows,
            'totals' => $totals,
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    /** Round > beat > places (location list + places the vouchers really use). */
    private function getLocationHierarchy(User $rangeUser): array
    {
        $hierarchy = [];
        $add = function (string $r, string $b, string $p) use (&$hierarchy) {
            $r = trim($r);
            $b = trim($b);
            $p = trim($p);
            if (($r === '' && $b === '' && $p === '') || ($p !== '' && in_array($p, $hierarchy[$r][$b] ?? [], true))) {
                return;
            }
            $hierarchy[$r][$b] ??= [];
            if ($p !== '') {
                $hierarchy[$r][$b][] = $p;
            }
        };
        foreach (RangeLocation::query()->where('range_id', $rangeUser->id)->orderBy('round')->orderBy('beat')->orderBy('place')->get() as $loc) {
            $add((string) $loc->round, (string) $loc->beat, (string) $loc->place);
        }
        foreach ($this->vouchers($rangeUser)->where('place', '<>', '')->select('round', 'beat', 'place')->distinct()->orderBy('round')->orderBy('beat')->orderBy('place')->get() as $v) {
            $add((string) $v->round, (string) $v->beat, (string) $v->place);
        }
        ksort($hierarchy);

        return $hierarchy;
    }

    private function authorizeRange(Request $request): void
    {
        abort_unless($request->user()?->isRange(), 403);
    }
}
