<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Monthly voucher abstract of a range, read from the typed vouchers of the selected financial year.
 * Only real vouchers are listed; nothing is invented when a month/docket has none.
 */
class AbstractPrintController extends Controller
{
    protected array $months = [
        'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
        'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar',
    ];

    /** Entry types in the order they print. */
    private array $typeOrder = ['Tender Entry', 'Free Entry', 'D.Wagers Salary', 'D.Wagers Arrears', 'Bene. Entry', 'WL Bene. Entry'];

    public function index(Request $request): View
    {
        $this->authorizeRange($request);

        $rangeUser = $request->user();
        $recentAbstracts = $this->getRecentAbstractSummaries($rangeUser);
        $selectedMonth = $request->query('month', $recentAbstracts[0]['month'] ?? 'Aug');
        $docketNo = $request->query('docket_no', '');

        return view('admin.finance.abstract-print.index', [
            'months' => $this->months,
            'selectedMonth' => $selectedMonth,
            'docketNo' => $docketNo,
            'dockets' => $this->getAvailableDockets($rangeUser, $selectedMonth),
            'recentAbstracts' => $recentAbstracts,
        ]);
    }

    public function dockets(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $month = $request->query('month', 'Aug');

        return response()->json([
            'success' => true,
            'month' => $month,
            'dockets' => $this->getAvailableDockets($request->user(), $month),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $validated = $request->validate([
            'month' => ['required', 'string', 'max:50'],
            'docket_no' => ['nullable', 'string', 'max:100'],
            'mode' => ['nullable', 'string', 'in:detailed,summary'],
        ]);

        $month = $validated['month'];
        $docketNo = $validated['docket_no'] ?? '';
        $mode = $validated['mode'] ?? 'detailed';

        return response()->json([
            'success' => true,
            'month' => $month,
            'docket_no' => $docketNo,
            'mode' => $mode,
            'data' => $this->compileAbstractData($request->user(), $month, $docketNo, $mode),
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorizeRange($request);

        $month = $request->query('month', 'Aug');
        $docketNo = $request->query('docket_no', '');
        $mode = $request->query('mode', 'detailed'); // 'detailed' = with Rate and Quantity, 'summary' = standard print

        return view('admin.finance.abstract-print.print', [
            'month' => $month,
            'docketNo' => $docketNo,
            'mode' => $mode,
            'data' => $this->compileAbstractData($request->user(), $month, $docketNo, $mode),
            'rangeUser' => $request->user(),
            'profile' => $request->user()->getOrCreateOfficeProfile(),
        ]);
    }

    // ------------------------------------------------------------------------------------------------ data
    private function query(User $rangeUser)
    {
        return Voucher::query()->forYear(CurrentFinancialYear::id())->where('range_id', $rangeUser->id);
    }

    /** Docket numbers really used by the range's vouchers of that month. */
    private function getAvailableDockets(User $rangeUser, string $month): array
    {
        return $this->query($rangeUser)->where('entry_month', $month)->whereNotNull('docket_no')
            ->distinct()->orderBy('docket_no')->pluck('docket_no')->map(fn ($d) => (string) $d)->all();
    }

    /** The range's real month x docket batches (latest month first). */
    private function getRecentAbstractSummaries(User $rangeUser): array
    {
        $rows = $this->query($rangeUser)->whereNotNull('entry_month')
            ->selectRaw('entry_month, docket_no, COUNT(*) as c, SUM(payable_total_amount) as gross, SUM(net_amount) as net')
            ->groupBy('entry_month', 'docket_no')->get();
        $order = array_flip($this->months);

        return $rows->sort(fn ($x, $y) => [$order[$y->entry_month] ?? -1, (int) $y->docket_no] <=> [$order[$x->entry_month] ?? -1, (int) $x->docket_no])
            ->take(12)->map(fn ($r) => [
                'month' => $r->entry_month,
                'docket_no' => $r->docket_no === null ? '' : (string) $r->docket_no,
                'count' => (int) $r->c,
                'gross' => (float) $r->gross,
                'net' => (float) $r->net,
            ])->values()->all();
    }

    private function compileAbstractData(User $rangeUser, string $month, ?string $docketNo, string $mode): array
    {
        $q = $this->query($rangeUser)->with('items');
        if ($month !== '') {
            $q->where('entry_month', $month);
        }
        if (! blank($docketNo)) {
            is_numeric($docketNo) ? $q->where('docket_no', (int) $docketNo) : $q->whereRaw('1 = 0'); // a non-numeric docket cannot exist
        }
        $vouchers = $q->get()->sortBy(fn (Voucher $v) => [array_search($v->entry_type, $this->typeOrder), (int) $v->serial_number])->values();

        $f = fn ($x) => (float) $x;
        $entries = [];
        foreach ($vouchers as $v) {
            $items = $v->items->map(fn ($i) => [
                'sr_no' => (int) $i->line_no,
                'description' => $i->description,
                'plot_area' => $v->place ?: '',
                'work_dates' => $i->work_order_date ? Carbon::parse($i->work_order_date)->format('d/m/Y') : '-',
                'length' => 0.0, 'breadth' => 0.0, 'depth' => 0.0, // not recorded on the vouchers
                'quantity' => $i->quantity !== null ? (float) $i->quantity : null,
                'unit' => $i->unit,
                'rate' => $i->rate !== null ? (float) $i->rate : null,
                'amount' => (float) $i->amount,
            ])->all();
            $gross = $f($v->payable_total_amount);
            $net = $f($v->net_amount);
            $entries[] = [
                'id' => $v->id,
                'serial_number' => (int) $v->serial_number,
                'entry_type' => $v->entry_type,
                'voucher_no' => $v->voucher_no_final ?: 'Sr. '.$v->serial_number,
                'docket_no' => $v->docket_no === null ? '' : (string) $v->docket_no,
                'party_name' => $v->party_name,
                'budget_code' => $v->budget_code,
                'head' => $v->operating_head ?? '',
                'scheme' => $v->scheme,
                'round' => $v->round,
                'beat' => $v->beat,
                'place' => $v->place,
                'plot_area' => $v->place,
                'work_dates' => '',
                'items' => $items,
                'gross_amount' => $gross,
                'sgst' => $f($v->deduction_sgst),
                'cgst' => $f($v->deduction_cgst),
                'labour_cess' => $f($v->deduction_labour_cess),
                'deposit' => $f($v->deposit_deduction_amount),
                'tds' => $f($v->tds),
                'other' => $f($v->gpf) + $f($v->cpf) + $f($v->p_tax) + $f($v->group_insurance) + $f($v->other_deductions),
                'total_deductions' => $f($v->total_deduction) ?: $gross - $net,
                'net_amount' => $net,
                'date' => $v->bill_date ? Carbon::parse($v->bill_date)->format('d/m/Y') : '',
            ];
        }

        $sum = fn (string $k) => array_sum(array_column($entries, $k));
        $totals = [
            'gross_amount' => $sum('gross_amount'), 'sgst' => $sum('sgst'), 'cgst' => $sum('cgst'), 'labour_cess' => $sum('labour_cess'),
            'deposit' => $sum('deposit'), 'tds' => $sum('tds'), 'other' => $sum('other'), 'total_deductions' => $sum('total_deductions'),
            'net_amount' => $sum('net_amount'), 'count' => count($entries),
        ];

        $breakdown = [];
        foreach ($entries as $e) {
            $bc = (string) $e['budget_code'];
            $breakdown[$bc] ??= ['budget_code' => $bc, 'scheme' => $e['scheme'], 'vouchers_count' => 0, 'gross_amount' => 0.0, 'total_deductions' => 0.0, 'net_amount' => 0.0];
            $breakdown[$bc]['vouchers_count']++;
            $breakdown[$bc]['gross_amount'] += $e['gross_amount'];
            $breakdown[$bc]['total_deductions'] += $e['total_deductions'];
            $breakdown[$bc]['net_amount'] += $e['net_amount'];
        }
        ksort($breakdown);

        return [
            'month' => $month,
            'docket_no' => blank($docketNo) ? 'All Dockets ('.$month.')' : $docketNo,
            'mode' => $mode,
            'range_name' => $rangeUser->name,
            'division_name' => ($rangeUser->division ?? $rangeUser)->getOrCreateOfficeProfile()->display_name,
            'entries' => $entries,
            'totals' => $totals,
            'budget_breakdown' => array_values($breakdown),
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    private function authorizeRange(Request $request): void
    {
        abort_unless($request->user()?->isRange(), 403);
    }
}
