<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Support\CurrentFinancialYear;
use App\Support\ExpenditureReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SummaryReportController extends Controller
{
    protected array $months = [
        'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
        'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'
    ];

    protected array $paymentModes = [
        'ALL',
        'IFMS',
        'SNA',
        'PFMS',
    ];

    protected array $reportTypes = [
        'summary_abstract' => 'Abstract of Summary Report (ગ્રાન્ટ ગોશવારો)',
        'summary_merged_salary' => 'Summary Report (Merged with Salary / પગાર સાથે)',
        'summary_only_lc' => 'Summary Report (Only LC / માત્ર મજૂરી ખર્ચ)',
    ];

    private function authorizeDivision(Request $request): void
    {
        if ($request->user()?->role !== 'division') {
            abort(403, 'Unauthorized. Division access required.');
        }
    }

    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        $selectedMonth = $request->query('month', $this->defaultMonth());
        $selectedPaymentMode = $request->query('payment_mode', 'ALL');
        $selectedReportType = $request->query('report_type', 'summary_abstract');

        return view('admin.finance.summary-reports.index', [
            'months' => $this->months,
            'paymentModes' => $this->paymentModes,
            'reportTypes' => $this->reportTypes,
            'selectedMonth' => $selectedMonth,
            'selectedPaymentMode' => $selectedPaymentMode,
            'selectedReportType' => $selectedReportType,
            'divisionUser' => $request->user(),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $validated = $request->validate([
            'month' => ['required', 'string', 'max:50'],
            'payment_mode' => ['nullable', 'string', 'in:ALL,IFMS,SNA,PFMS'],
            'report_type' => ['required', 'string'],
        ]);

        $month = $validated['month'];
        $paymentMode = $validated['payment_mode'] ?? 'IFMS';
        $reportType = $validated['report_type'];

        $data = $this->compileSummaryData($request->user(), $month, $paymentMode, $reportType);

        return response()->json([
            'success' => true,
            'month' => $month,
            'payment_mode' => $paymentMode,
            'report_type' => $reportType,
            'data' => $data,
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorizeDivision($request);

        $month = $request->query('month', $this->defaultMonth());
        $paymentMode = $request->query('payment_mode', 'IFMS');
        $reportType = $request->query('report_type', 'summary_abstract');

        $data = $this->compileSummaryData($request->user(), $month, $paymentMode, $reportType);

        return view('admin.finance.summary-reports.print', [
            'month' => $month,
            'paymentMode' => $paymentMode,
            'reportType' => $reportType,
            'data' => $data,
            'divisionUser' => $request->user(),
        ]);
    }

    /** Month of the most recent treasury approval of the year (what a user normally wants to see first). */
    private function defaultMonth(): string
    {
        return \App\Support\ExpenditureReport::latestApprovedMonth(CurrentFinancialYear::id()) ?? 'Apr';
    }

    private function compileSummaryData(User $divisionUser, string $month, string $paymentMode, string $reportType): array
    {
        $profile = $divisionUser->getOrCreateOfficeProfile();
        $month = in_array($month, $this->months, true) ? $month : 'Apr';

        return $reportType === 'summary_abstract'
            ? $this->compileAbstractSummaryData($profile, $month, $paymentMode)
            : $this->compileDetailedSummaryData($profile, $month, $paymentMode, $reportType);
    }

    private const DEMAND_ORDER = ['2406/26', '2406/95', '2406/96', '4406/26', '4406/95', '4406/96'];

    private const DEMAND_LABEL = [
        '2406/26' => 'Demand 26 (Revenue)', '2406/95' => 'Demand 95 (SCSP)', '2406/96' => 'Demand 96 (Tribal)',
        '4406/26' => 'Demand 26 (Capital)', '4406/95' => 'Demand 95 (SCSP Capital)', '4406/96' => 'Demand 96 (Tribal Capital)',
    ];

    /** @return array<string, float|int> */
    private function totalsRow(float $sanctioned, float $released, float $last, float $current): array
    {
        $totExp = $last + $current;

        return [
            'sanctioned' => $sanctioned, 'released' => $released, 'last_month' => $last, 'current_month' => $current, 'total_exp' => $totExp,
            'rem_req' => $sanctioned - $totExp, 'total_req' => $sanctioned,
            'pct_sanctioned' => $sanctioned > 0 ? round($totExp / $sanctioned * 100, 2) : 0,
            'pct_released' => $released > 0 ? round($totExp / $released * 100, 2) : 0,
        ];
    }

    /** @param array<int, array<string, float|int>> $rows */
    private function sumRows(array $rows): array
    {
        return $this->totalsRow(array_sum(array_column($rows, 'sanctioned')), array_sum(array_column($rows, 'released')), array_sum(array_column($rows, 'last_month')), array_sum(array_column($rows, 'current_month')));
    }

    private function header(\App\Models\OfficeProfile $profile, string $month, string $paymentMode): array
    {
        return [
            'division_name' => $profile->display_name, 'division_name_gujarati' => $profile->display_name_gujarati, 'officer_name' => $profile->display_officer_name,
            'officer_designation' => $profile->display_designation, 'officer_designation_gujarati' => $profile->display_designation_gujarati,
            'logo_url' => $profile->logo_url, 'address' => $profile->formatted_address, 'month' => $month, 'payment_mode' => $paymentMode,
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    private function reportYear(string $month): string
    {
        return Carbon::parse(ExpenditureReport::window(CurrentFinancialYear::id(), $month)['start'])->format('Y');
    }

    /** Abstract: per demand (operating head) and scheme - sanctioned (Circle allotment), released (L.C. issued), expenditure up to last month / this month. */
    private function compileAbstractSummaryData(\App\Models\OfficeProfile $profile, string $month, string $paymentMode): array
    {
        $fy = CurrentFinancialYear::id();
        $released = ExpenditureReport::released($fy, $month);
        $byHead = [];
        foreach (ExpenditureReport::codeRows($fy, $month, $paymentMode) as $r) {
            $key = explode(' ', (string) $r->operating_head)[0] ?: 'Other';
            $s = &$byHead[$key][$r->scheme];
            $s['name'] = $r->scheme.' ('.$key.')';
            $s['scheme'] = $r->scheme;
            $s['sanctioned'] = ($s['sanctioned'] ?? 0) + $r->allotment;
            $s['last_month'] = ($s['last_month'] ?? 0) + $r->p_exp;
            $s['current_month'] = ($s['current_month'] ?? 0) + $r->c_exp;
            unset($s);
        }
        uksort($byHead, fn ($a, $b) => (array_search($a, self::DEMAND_ORDER, true) === false ? 99 : array_search($a, self::DEMAND_ORDER, true)) <=> (array_search($b, self::DEMAND_ORDER, true) === false ? 99 : array_search($b, self::DEMAND_ORDER, true)));

        $sections = [];
        $head2406 = [];
        $head4406 = [];
        $all = [];
        foreach ($byHead as $key => $schemes) {
            $rows = [];
            foreach ($schemes as $sch) {
                $row = $this->totalsRow($sch['sanctioned'], array_sum($released[$sch['scheme']] ?? []), $sch['last_month'], $sch['current_month']);
                $rows[] = ['name' => $sch['name']] + $row;
            }
            $sections[] = ['demand_key' => $key, 'demand_title' => $key.' '.(self::DEMAND_LABEL[$key] ?? ''), 'schemes' => $rows, 'subtotal' => $this->sumRows($rows)];
            str_starts_with($key, '2406') ? array_push($head2406, ...$rows) : array_push($head4406, ...$rows);
            array_push($all, ...$rows);
        }

        return [
            'report_type' => 'summary_abstract', 'report_title' => "Abstract of SUMMARY of the month {$month}-".$this->reportYear($month),
            'sections' => $sections, 'head_2406_totals' => $this->sumRows($head2406), 'head_4406_totals' => $this->sumRows($head4406), 'grand_totals' => $this->sumRows($all),
        ] + $this->header($profile, $month, $paymentMode);
    }

    /** Detailed summary: per operating head + scheme, one line per budget code. "Only LC" leaves out the salary / wages codes (Class-1). */
    private function compileDetailedSummaryData(\App\Models\OfficeProfile $profile, string $month, string $paymentMode, string $reportType): array
    {
        $isOnlyLc = $reportType === 'summary_only_lc';
        $groups = [];
        foreach (ExpenditureReport::codeRows(CurrentFinancialYear::id(), $month, $paymentMode) as $r) {
            if ($isOnlyLc && ExpenditureReport::isSalaryCode($r)) {
                continue;
            }
            $groups[$r->operating_head.' and Scheme: '.$r->scheme][] = $r;
        }

        $out = [];
        $zero = ['allotment' => 0.0, 'p_exp' => 0.0, 'c_exp' => 0.0, 'total_exp' => 0.0, 'tot_req' => 0.0, 'req_change' => 0.0, 'rem_req' => 0.0];
        $grand = $zero;
        foreach ($groups as $label => $rows) {
            $items = [];
            $tot = $zero;
            foreach ($rows as $n => $r) {
                $totExp = $r->p_exp + $r->c_exp;
                $item = ['sr_no' => $n + 1, 'budget_code' => $r->budget_code, 'item' => $r->item, 'obj_class' => $r->object_class, 'model' => $r->model, 'allotment' => $r->allotment,
                    'p_exp' => $r->p_exp, 'c_exp' => $r->c_exp, 'total_exp' => $totExp, 'tot_req' => $r->allotment, 'req_change' => 0.0, 'rem_req' => $r->allotment - $totExp];
                $items[] = $item;
                foreach (array_keys($tot) as $k) {
                    $tot[$k] += $item[$k];
                }
            }
            foreach (array_keys($grand) as $k) {
                $grand[$k] += $tot[$k];
            }
            $out[] = ['operating_head' => $label, 'items' => $items, 'totals' => $tot];
        }

        $suffix = $isOnlyLc ? '(Only LC / માત્ર મજૂરી ખર્ચ)' : '(Merged with salary / પગાર સહિત)';

        return ['report_type' => $reportType, 'report_title' => "SUMMARY REPORT of the month {$month}-".$this->reportYear($month)." {$suffix}", 'groups' => $out, 'grand_totals' => $grand]
            + $this->header($profile, $month, $paymentMode);
    }
}
