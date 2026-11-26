<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use App\Support\ExpenditureReport;
use App\Support\FinalVoucherNumbering;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Division: final voucher numbers, Form No. 35 (schedule of vouchers) and the Cash Book, from the real vouchers.
 *  - A voucher belongs to the month of its treasury approval; final numbers restart every month (SR- / CONT- / SNA- series).
 *  - Form 35 lists the approved vouchers of the month and payment mode in final-number order.
 *  - Cash book: receipts = L.C. issued in the month, payments = payable amount of the vouchers approved in the month (all modes, as the L.C. is not split by mode).
 *    The opening balance of the year is not recorded in the data, so the book starts from nil and rolls forward month by month.
 */
class FinalVoucherCashbookController extends Controller
{
    protected array $months = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];

    protected array $paymentModes = ['IFMS', 'SNA', 'PFMS'];

    protected array $scales = [
        'full_compact' => 'Full Compact (ઓછામાં ઓછા પેજ)',
        'compact' => 'Compact (સામાન્ય કોમ્પેક્ટ)',
        'normal' => 'Normal (વિગતવાર)',
    ];

    protected array $pageSizes = ['A4', 'Legal'];

    private const CASHBOOK_TYPES = ['cashbook', 'cashbook_credit', 'cashbook_debit', 'range_cashbook', 'cashbook_title', 'cashbook_sticker', 'cashbook_certificate'];

    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        $division = $request->user();
        $ranges = User::query()->where('role', 'range')->where('division_id', $division->id)->orderBy('name')->get();
        if ($ranges->isEmpty()) {
            $ranges = User::query()->where('role', 'range')->orderBy('name')->get();
        }

        $recent = $this->recentBatches($request);
        $selectedMonth = $recent[0]['month'] ?? Carbon::now()->format('M');

        return view('admin.finance.final-voucher-cashbook.index', [
            'months' => $this->months,
            'selectedMonth' => $selectedMonth,
            'paymentModes' => $this->paymentModes,
            'scales' => $this->scales,
            'pageSizes' => $this->pageSizes,
            'ranges' => $ranges,
            'mergedGroups' => $this->groups(),
            'recentVouchers' => $recent,
        ]);
    }

    /** Gives final voucher numbers to the approved vouchers of the month and mode that have none (continuing the month's series). */
    public function assignVouchers(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $validated = $request->validate([
            'month' => ['required', 'string'],
            'payment_mode' => ['required', 'string', 'in:IFMS,SNA,PFMS'],
            'scale' => ['nullable', 'string'],
            'page_size' => ['nullable', 'string'],
        ]);
        $month = $validated['month'];
        $mode = $validated['payment_mode'];

        $result = DB::transaction(function () use ($request, $month, $mode) {
            $unnumbered = $this->approved($request, $month, $mode)->whereNull('voucher_no_final')
                ->orderBy('advice_no')->orderBy('range_id')->orderBy('entry_type')->orderBy('serial_number')->lockForUpdate()->get();

            return FinalVoucherNumbering::assign($unnumbered, CurrentFinancialYear::id());
        });

        $all = $this->approved($request, $month, $mode)->get(['id', 'payable_total_amount']);
        $msg = $result['assigned'] > 0
            ? "Final voucher numbers assigned for {$month} ({$mode}): {$result['assigned']} voucher(s) numbered, continuing the month's series."
            : "Nothing to assign for {$month} ({$mode}): all {$all->count()} approved voucher(s) already have final numbers.";
        if ($result['skipped'] > 0) {
            $msg .= " {$result['skipped']} voucher(s) have no IFMS bill type, so no series could be chosen.";
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'assigned_count' => $result['assigned'],
            'skipped_count' => $result['skipped'],
            'vouchers_count' => $all->count(),
            'total_amount' => (float) $all->sum('payable_total_amount'),
            'month' => $month,
            'payment_mode' => $mode,
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $validated = $request->validate([
            'month' => ['required', 'string'],
            'payment_mode' => ['nullable', 'string'],
            'report_type' => ['required', 'string'],
            'range_id' => ['nullable'],
            'scale' => ['nullable', 'string'],
            'page_size' => ['nullable', 'string'],
        ]);

        $rangeId = ! empty($validated['range_id']) && $validated['range_id'] !== 'Choose...' ? (int) $validated['range_id'] : null;
        $reportType = $validated['report_type'];
        $month = $validated['month'];
        $paymentMode = ($validated['payment_mode'] ?? null) ?: 'IFMS';

        return response()->json([
            'success' => true,
            'report_type' => $reportType,
            'month' => $month,
            'payment_mode' => $this->modeLabel($reportType, $paymentMode),
            'data' => $this->compileReportData($request, $month, $paymentMode, $reportType, $rangeId),
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorizeDivision($request);

        $recent = $this->recentBatches($request);
        $month = $request->query('month', $recent[0]['month'] ?? Carbon::now()->format('M'));
        $paymentMode = $request->query('payment_mode', 'IFMS');
        $reportType = $request->query('report_type', 'form_35');
        $rangeId = $request->query('range_id');
        $rangeId = ! empty($rangeId) && $rangeId !== 'Choose...' ? (int) $rangeId : null;

        return view('admin.finance.final-voucher-cashbook.print', [
            'month' => $month,
            'paymentMode' => $this->modeLabel($reportType, $paymentMode),
            'reportType' => $reportType,
            'scale' => $request->query('scale', 'full_compact'),
            'pageSize' => $request->query('page_size', 'A4'),
            'data' => $this->compileReportData($request, $month, $paymentMode, $reportType, $rangeId),
        ]);
    }

    public function mergeSchemes(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $validated = $request->validate([
            'group_name' => ['required', 'string', 'max:255'],
            'schemes' => ['required', 'array', 'min:1'],
            'schemes.*' => ['string', 'max:500'],
        ]);

        $schemes = array_values(array_unique($validated['schemes']));
        $existing = DB::table('division_scheme_groups')->where('financial_year_id', CurrentFinancialYear::id())->where('name', $validated['group_name'])->first();
        if ($existing) {
            DB::table('division_scheme_groups')->where('id', $existing->id)->update(['schemes' => json_encode($schemes, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        } else {
            DB::table('division_scheme_groups')->insert(['financial_year_id' => CurrentFinancialYear::id(), 'name' => $validated['group_name'], 'schemes' => json_encode($schemes, JSON_UNESCAPED_UNICODE), 'created_at' => now(), 'updated_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => "Scheme group '{$validated['group_name']}' saved for Form-35 and Cashbook.",
            'group' => ['group_name' => $validated['group_name'], 'schemes' => $schemes],
        ]);
    }

    // ------------------------------------------------------------------------------------------------ data
    private function modeLabel(string $reportType, string $paymentMode): string
    {
        return in_array($reportType, self::CASHBOOK_TYPES, true) ? 'All modes' : $paymentMode;
    }

    /** @return array<int, array{name: string, schemes: array<int, string>}> */
    private function groups(): array
    {
        return DB::table('division_scheme_groups')->where('financial_year_id', CurrentFinancialYear::id())->orderBy('id')->get()
            ->map(fn ($g) => ['name' => $g->name, 'schemes' => json_decode($g->schemes, true) ?: []])->all();
    }

    /** Vouchers approved by the treasury in the month (and mode) that the signed-in Division may see. */
    private function approved(Request $request, string $month, ?string $mode = null)
    {
        return Voucher::query()->visibleTo($request->user())->forYear(CurrentFinancialYear::id())
            ->whereRaw(ExpenditureReport::approvedSql('vouchers', CurrentFinancialYear::id(), $month, 'in'))
            ->when($mode, fn ($q) => $q->where('payment_mode', $mode));
    }

    /** The year's month x mode batches (latest month first): how many approved vouchers, how many numbered. */
    private function recentBatches(Request $request): array
    {
        $order = array_flip(ExpenditureReport::MONTHS);
        $rows = Voucher::query()->visibleTo($request->user())->forYear(CurrentFinancialYear::id())->where('treasury_status', 'Approved')->whereNotNull('treasury_approval_month')
            ->selectRaw("treasury_approval_month ym, payment_mode, COUNT(*) c, SUM(voucher_no_final IS NOT NULL) numbered, SUM(payable_total_amount) amt, MAX(treasury_approval_date) last_date")
            ->groupBy('ym', 'payment_mode')->get()
            ->sort(fn ($x, $y) => [$order[$y->ym] ?? -1, (string) $x->payment_mode] <=> [$order[$x->ym] ?? -1, (string) $y->payment_mode])->take(12)->values();

        return $rows->map(fn ($r) => [
            'month' => $r->ym,
            'mode' => $r->payment_mode ?: '-',
            'scheme' => 'All schemes',
            'assigned_count' => (int) $r->numbered,
            'total_amount' => (float) $r->amt,
            'status' => (int) $r->numbered === (int) $r->c ? 'All numbered' : ((int) $r->c - (int) $r->numbered).' not numbered',
            'assigned_at' => $r->last_date ? Carbon::parse($r->last_date)->format('d M Y') : '',
        ])->all();
    }

    private function compileReportData(Request $request, string $month, string $paymentMode, string $reportType, ?int $rangeId): array
    {
        $profile = $request->user()->getOrCreateOfficeProfile();
        $rangeModel = $rangeId ? User::find($rangeId) : null;
        $rangeName = $rangeModel ? $rangeModel->name : 'All Ranges';
        $isCashbook = in_array($reportType, self::CASHBOOK_TYPES, true);

        $compiled = $this->compileEntriesData($request, $month, $isCashbook ? null : $paymentMode, $rangeId, $isCashbook);

        $reportTitles = [
            'form_35' => 'FORM NO. 35 - SCHEDULE OF VOUCHERS (વાઉચર શેડ્યુલ)',
            'vouchers_sorted' => 'FINAL VOUCHERS LIST (SORTED AS PER FORM 35)',
            'cashbook' => 'DIVISION CASH BOOK (કેશબૂક રજીસ્ટર)',
            'cashbook_credit' => 'CASH BOOK - CREDIT PART (આવક/જમા ભાગ)',
            'cashbook_debit' => 'CASH BOOK - DEBIT PART (ખર્ચ/ઉધાર ભાગ)',
            'cashbook_title' => 'CASH BOOK TITLE PAGE (કેશબૂક મુખપૃષ્ઠ)',
            'cashbook_sticker' => 'CASH BOOK STICKER A5 (કેશબૂક સ્ટીકર)',
            'cashbook_certificate' => 'CASH BOOK CERTIFICATE (કેશબૂક પ્રમાણપત્ર)',
            'range_form_35' => "FORM NO. 35 FOR RANGE: {$rangeName}",
            'range_cashbook' => "RANGE CASH BOOK (રેન્જ કેશબૂક): {$rangeName}",
        ];

        return [
            'division_name' => $profile->display_name,
            'division_name_gujarati' => $profile->display_name_gujarati ?? '',
            'officer_name' => $profile->display_officer_name ?? '',
            'officer_designation' => $profile->display_designation ?? 'Deputy Conservator of Forests',
            'officer_designation_gujarati' => $profile->display_designation_gujarati ?? 'નાયબ વન સંરક્ષક',
            'logo_url' => $profile->logo_url ?? asset('images/gujarat-forest-logo.svg'),
            'address' => $profile->formatted_address ?? '',
            'range_name' => $rangeName,
            'month' => $month,
            'payment_mode' => $this->modeLabel($reportType, $paymentMode),
            'report_type' => $reportType,
            'report_title' => $reportTitles[$reportType] ?? 'STATUTORY REPORT',
            'generated_at' => Carbon::now()->format('d M Y, h:i A'),
            'entries' => $compiled['entries'],
            'totals' => $compiled['totals'],
            'cashbook_summary' => $isCashbook ? $this->cashbookSummary($request, $month, $rangeId) : ['opening_balance' => 0.0, 'total_receipts' => 0.0, 'total_payments' => (float) $compiled['totals']['gross_amount'], 'closing_balance' => 0.0],
            'cashbook_note' => $isCashbook ? ($rangeId ? 'L.C. is issued to the Division, not to a range: a range book shows payments only (receipts and balances are not split by range). ' : '').'Receipts = L.C. issued; payments = payable amount of the vouchers approved by the treasury in the month. The opening balance of the year is not recorded in the data and starts from nil.' : '',
        ];
    }

    /** Opening = L.C. issued - vouchers approved, over the months of the year before this one. */
    private function cashbookSummary(Request $request, string $month, ?int $rangeId): array
    {
        $fy = CurrentFinancialYear::id();
        $w = ExpenditureReport::window($fy, $month);
        $lc = fn () => DB::table('lc_entries')->where('financial_year_id', $fy);

        $paidBefore = (float) Voucher::query()->visibleTo($request->user())->forYear($fy)->whereRaw(ExpenditureReport::approvedSql('vouchers', $fy, $month, 'before'))
            ->when($rangeId, fn ($q) => $q->where('range_id', $rangeId))->sum('payable_total_amount');
        $paidNow = (float) $this->approved($request, $month)->when($rangeId, fn ($q) => $q->where('range_id', $rangeId))->sum('payable_total_amount');
        $opening = (float) $lc()->where('entry_date', '<', $w['start'])->sum('amount') - $paidBefore;
        $receipts = (float) $lc()->whereBetween('entry_date', [$w['start'], $w['end']])->sum('amount');

        return [
            'opening_balance' => $rangeId ? 0.0 : $opening,
            'total_receipts' => $rangeId ? 0.0 : $receipts,
            'total_payments' => $paidNow,
            'closing_balance' => $rangeId ? 0.0 : $opening + $receipts - $paidNow,
        ];
    }

    private function compileEntriesData(Request $request, string $month, ?string $paymentMode, ?int $rangeId, bool $cashbook = false): array
    {
        $groupOf = [];
        foreach ($this->groups() as $g) {
            foreach ($g['schemes'] as $s) {
                $groupOf[$s] = $g['name'];
            }
        }
        $ranges = User::query()->where('role', 'range')->pluck('name', 'id');

        $vouchers = $this->approved($request, $month, $paymentMode)->when($rangeId, fn ($q) => $q->where('range_id', $rangeId))
            ->select('vouchers.*')->addSelect(['first_desc' => \App\Models\VoucherItem::query()->select('description')->whereColumn('voucher_items.voucher_id', 'vouchers.id')->orderBy('line_no')->limit(1)])->get();   // no item loading: a month can hold 4,500 vouchers
        $seriesOrder = ['SR' => 1, 'CONT' => 2, 'SNA' => 3];
        $vouchers = $vouchers->sortBy(function (Voucher $v) use ($seriesOrder, $cashbook) {
            [$pre, $no] = array_pad(explode('-', (string) $v->voucher_no_final, 2), 2, '');
            if (ctype_digit((string) $v->voucher_no_final)) {   // 2025-26 numbers are plain 1..n of the month
                [$pre, $no] = ['', (string) $v->voucher_no_final];
            }
            $series = $v->voucher_no_final ? ($pre === '' ? 0 : ($seriesOrder[$pre] ?? 9)) : 99; // not yet numbered: last
            $key = [$series, (int) $no, (int) $v->range_id, (string) $v->entry_type, (int) $v->serial_number];

            return $cashbook ? array_merge([(string) $v->treasury_approval_date], $key) : $key;
        })->values();

        $entries = [];
        foreach ($vouchers as $v) {
            $gross = (float) $v->payable_total_amount;
            $ded = (float) $v->total_deduction ?: $gross - (float) $v->net_amount;
            $entries[] = [
                'voucher_no' => $v->voucher_no_final ?: '(not numbered)',
                'final_voucher_no' => $v->voucher_no_final,
                'original_voucher_no' => $v->entry_type.' '.$v->serial_number,
                'entry_type' => $v->entry_type,
                'date' => ($cashbook ? $v->treasury_approval_date : ($v->bill_date ?? $v->treasury_approval_date)) ? Carbon::parse($cashbook ? $v->treasury_approval_date : ($v->bill_date ?? $v->treasury_approval_date))->format('d/m/Y') : '',
                'range_name' => $ranges[$v->range_id] ?? 'Division Office',
                'party_name' => $v->party_name,
                'party_gst' => $v->gst_no,
                'budget_code' => $v->budget_code,
                'scheme_name' => $groupOf[$v->scheme] ?? $v->scheme,
                'description' => trim((string) $v->first_desc) !== '' ? $v->first_desc : $v->entry_type,
                'gross_amount' => $gross,
                'deductions' => $ded,
                'net_amount' => (float) $v->net_amount,
            ];
        }

        return [
            'entries' => $entries,
            'totals' => [
                'count' => count($entries),
                'gross_amount' => array_sum(array_column($entries, 'gross_amount')),
                'total_deductions' => array_sum(array_column($entries, 'deductions')),
                'net_amount' => array_sum(array_column($entries, 'net_amount')),
            ],
        ];
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()?->isDivision(), 403, 'Unauthorized. Division access required.');
    }
}
