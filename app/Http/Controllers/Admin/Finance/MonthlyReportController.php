<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CurrentFinancialYear;
use App\Support\ExpenditureReport;
use App\Support\MonthlyReportData;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonthlyReportController extends Controller
{
    protected array $months = [
        'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
        'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'
    ];

    protected array $ifmsBillTypes = [
        'Contingency',
        'Simple Receipt',
    ];

    /** Used only until budget codes exist (fresh install). */
    protected array $defaultSnaSchemes = [
        'Agroforestry Under National Mission', '2406- National Bamboo Mission', '2406- National Bamboo Mission (SCP)', '2406- National Bamboo Mission (Trible)',
        'DCP Nursery', 'DCP Nursery (SCP)', 'DCP Nursery (Trible)', 'RDFL (Rehabilitation of Degraded Forest Land)', 'RDFL (SCP)', 'RDFL (Trible)',
        'VruxKheti Yojana', 'VruxKheti (SCP)', 'VruxKheti (Trible)', 'Grassland Development Scheme', 'Wildlife Protection & Eco-Tourism',
    ];

    /** Schemes whose budget codes are paid through SNA, read from the data. */
    protected function snaSchemes(): array
    {
        $fromData = \Illuminate\Support\Facades\DB::table('budget_codes')->where('financial_year_id', CurrentFinancialYear::id())->where('payment_mode', 'SNA')->whereNotNull('scheme')->distinct()->orderBy('scheme')->pluck('scheme')->all();

        return $fromData ?: $this->defaultSnaSchemes;
    }

    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        $selectedMonth = $request->query('month', $this->defaultMonth());
        $selectedPaymentMode = $request->query('payment_mode', 'IFMS');
        $selectedBillTypes = (array) $request->query('bill_types', ['Contingency', 'Simple Receipt']);
        $selectedSchemes = (array) $request->query('schemes', []);

        $recentReports = $this->getRecentReportsSummary($request->user());

        return view('admin.finance.monthly-reports.index', [
            'months' => $this->months,
            'ifmsBillTypes' => $this->ifmsBillTypes,
            'snaSchemes' => $this->snaSchemes(),
            'selectedMonth' => $selectedMonth,
            'selectedPaymentMode' => $selectedPaymentMode,
            'selectedBillTypes' => $selectedBillTypes,
            'selectedSchemes' => $selectedSchemes,
            'recentReports' => $recentReports,
        ]);
    }

    public function schemes(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $paymentMode = $request->query('payment_mode', 'IFMS');

        return response()->json([
            'success' => true,
            'payment_mode' => $paymentMode,
            'options' => $paymentMode === 'IFMS' ? $this->ifmsBillTypes : ($paymentMode === 'SNA' ? $this->snaSchemes() : []),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $validated = $request->validate([
            'month' => ['required', 'string', 'max:50'],
            'payment_mode' => ['nullable', 'string', 'in:IFMS,SNA,PFMS'],
            'bill_types' => ['nullable', 'array'],
            'schemes' => ['nullable', 'array'],
            'report_type' => ['required', 'string'],
        ]);

        $month = $validated['month'];
        $paymentMode = $validated['payment_mode'] ?? 'IFMS';
        $selectedItems = $paymentMode === 'IFMS' ? ($validated['bill_types'] ?? []) : ($validated['schemes'] ?? []);
        $reportType = $validated['report_type'];

        $data = $this->compileReportData($request->user(), $month, $paymentMode, $selectedItems, $reportType);

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
        $reportType = $request->query('report_type', 'form_53_abstract');
        $selectedItems = (array) ($paymentMode === 'IFMS' ? $request->query('bill_types', []) : $request->query('schemes', []));

        $data = $this->compileReportData($request->user(), $month, $paymentMode, $selectedItems, $reportType);
        $profile = $request->user()->getOrCreateOfficeProfile();

        return view('admin.finance.monthly-reports.print', [
            'month' => $month,
            'paymentMode' => $paymentMode,
            'reportType' => $reportType,
            'selectedItems' => $selectedItems,
            'data' => $data,
            'divisionUser' => $request->user(),
            'profile' => $profile,
        ]);
    }

    private function compileReportData(User $divisionUser, string $month, string $paymentMode, array $selectedItems, string $reportType): array
    {
        $month = in_array($month, $this->months, true) ? $month : 'Apr';
        $reportTitles = [
            'form_53_abstract' => 'FORM NO. 53 (ABSTRACT / D-36) - માસિક ગ્રાન્ટ અને ખર્ચ ગોશવારો',
            'form_53_new' => 'FORM NO. 53 (NEW) - વિગતવાર બિલવાર માસિક હિસાબ પત્રક',
            'reconciliation' => 'TREASURY RECONCILIATION STATEMENT - તિજોરી મેળવણી પત્રક',
            'epayment_report' => 'ELECTRONIC PAYMENT REPORT - ઈ-પેમેન્ટ વિગત પત્રક',
            'deduction_reports' => 'MONTHLY STATUTORY DEDUCTIONS REPORT - માસિક કપાત પત્રક',
            'deduction_reports_yearly' => 'ANNUAL CONSOLIDATED DEDUCTIONS REPORT - વાર્ષિક કપાત પત્રક',
            'gpf_nps_reports' => 'GPF & NPS SUBSCRIPTION & CONTRIBUTION STATEMENT',
            'range_reports' => 'RANGE-WISE EXPENDITURE ALLOCATION REPORT - પરિક્ષેત્રવાર માસિક ખર્ચ પત્રક',
            'challan_report' => 'TREASURY & GST REMITTANCE CHALLAN REGISTER - સરકારી ચલણ પત્રક',
            'pay_slips' => 'MONTHLY WAGE & SALARY DISBURSEMENT SLIPS - માસિક પે સ્લીપ',
        ];
        $profile = $divisionUser->getOrCreateOfficeProfile();
        $data = MonthlyReportData::build(CurrentFinancialYear::id(), $month, $paymentMode, $selectedItems, $reportType);

        return $data + [
            'month' => $month, 'payment_mode' => $paymentMode, 'report_type' => $reportType, 'report_title' => $reportTitles[$reportType] ?? 'MONTHLY REPORT',
            'division_name' => $profile->display_name, 'division_name_gujarati' => $profile->display_name_gujarati, 'officer_name' => $profile->display_officer_name,
            'officer_designation' => $profile->display_designation, 'officer_designation_gujarati' => $profile->display_designation_gujarati,
            'logo_url' => $profile->logo_url, 'address' => $profile->formatted_address,
            'selected_items' => ! empty($selectedItems) ? implode(', ', $selectedItems) : 'All Selected',
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    /** Last three months that have treasury approvals, per payment mode (real figures). */
    private function getRecentReportsSummary(User $divisionUser): array
    {
        $rows = \Illuminate\Support\Facades\DB::table('vouchers')->where('financial_year_id', CurrentFinancialYear::id())->where('treasury_status', 'Approved')
            ->selectRaw("MAX(treasury_approval_date) AS last_date, treasury_approval_month AS month, payment_mode AS mode, COUNT(DISTINCT advice_no) AS bills_count, SUM(payable_total_amount) AS gross, SUM(net_amount) AS net")
            ->groupBy('treasury_approval_month', 'payment_mode')->get()
            ->sortByDesc(fn ($r) => $r->last_date ?: (array_search($r->month, \App\Support\ExpenditureReport::MONTHS, true) === false ? -1 : array_search($r->month, \App\Support\ExpenditureReport::MONTHS, true)))->take(3);

        return $rows->map(fn ($r) => ['month' => $r->month, 'mode' => $r->mode, 'type' => 'Treasury-approved bills', 'bills_count' => (int) $r->bills_count, 'gross' => (float) $r->gross, 'net' => (float) $r->net])->all();
    }

    /** Month of the most recent treasury approval of the year. */
    private function defaultMonth(): string
    {
        return \App\Support\ExpenditureReport::latestApprovedMonth(CurrentFinancialYear::id()) ?? 'Apr';
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()?->isDivision(), 403);
    }
}
