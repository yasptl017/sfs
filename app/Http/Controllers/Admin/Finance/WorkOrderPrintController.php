<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\PartyRegistration;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkOrderPrintController extends Controller
{
    protected array $months = [
        'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep',
        'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'
    ];

    public function index(Request $request): View
    {
        $this->authorizeRange($request);

        $rangeUser = $request->user();
        $recentWorkOrders = $this->getRecentWorkOrdersSummary($rangeUser);
        $selectedMonth = $request->query('month', $recentWorkOrders[0]['month'] ?? 'Aug');
        $attachmentMode = $request->query('attachment_mode', 'Separate');
        $extendDays = (int) $request->query('extend_days', 2);
        $printAllDockets = (bool) $request->query('print_all_dockets', false);
        $docketNo = $request->query('docket_no', '');

        $dockets = $this->getAvailableDockets($rangeUser, $selectedMonth);

        return view('admin.finance.work-order-print.index', [
            'months' => $this->months,
            'selectedMonth' => $selectedMonth,
            'attachmentMode' => $attachmentMode,
            'extendDays' => $extendDays,
            'printAllDockets' => $printAllDockets,
            'docketNo' => $docketNo,
            'dockets' => $dockets,
            'recentWorkOrders' => $recentWorkOrders,
        ]);
    }

    public function dockets(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $month = $request->query('month', 'Aug');
        $dockets = $this->getAvailableDockets($request->user(), $month);

        return response()->json([
            'success' => true,
            'month' => $month,
            'dockets' => $dockets,
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $validated = $request->validate([
            'month' => ['required', 'string', 'max:50'],
            'attachment_mode' => ['nullable', 'string', 'in:Separate,Combined'],
            'extend_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'print_all_dockets' => ['nullable', 'boolean'],
            'docket_no' => ['nullable', 'string', 'max:100'],
        ]);

        $month = $validated['month'];
        $attachmentMode = $validated['attachment_mode'] ?? 'Separate';
        $extendDays = (int) ($validated['extend_days'] ?? 2);
        $printAllDockets = (bool) ($validated['print_all_dockets'] ?? false);
        $docketNo = $printAllDockets ? '' : ($validated['docket_no'] ?? '');

        $data = $this->compileWorkOrdersData($request->user(), $month, $docketNo, $attachmentMode, $extendDays, $printAllDockets);

        return response()->json([
            'success' => true,
            'month' => $month,
            'attachment_mode' => $attachmentMode,
            'extend_days' => $extendDays,
            'print_all_dockets' => $printAllDockets,
            'docket_no' => $docketNo,
            'data' => $data,
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorizeRange($request);

        $month = $request->query('month', 'Aug');
        $attachmentMode = $request->query('attachment_mode', 'Separate');
        $extendDays = (int) $request->query('extend_days', 2);
        $printAllDockets = $request->has('print_all_dockets') && $request->query('print_all_dockets') !== '0' && $request->query('print_all_dockets') !== 'false';
        $docketNo = $printAllDockets ? '' : (string) $request->query('docket_no', '');

        $data = $this->compileWorkOrdersData($request->user(), $month, $docketNo, $attachmentMode, $extendDays, $printAllDockets);
        $profile = $request->user()->getOrCreateOfficeProfile();

        return view('admin.finance.work-order-print.print', [
            'month' => $month,
            'attachmentMode' => $attachmentMode,
            'extendDays' => $extendDays,
            'printAllDockets' => $printAllDockets,
            'docketNo' => $docketNo,
            'data' => $data,
            'rangeUser' => $request->user(),
            'profile' => $profile,
        ]);
    }

    // ------------------------------------------------------------------------------------------------ data
    private function query(User $rangeUser)
    {
        return Voucher::query()->forYear(CurrentFinancialYear::id())->where('range_id', $rangeUser->id);
    }

    /** A work order exists for a voucher only when one of its items records a work order number. */
    private function withWorkOrder($q)
    {
        return $q->whereHas('items', fn ($i) => $i->whereNotNull('work_order_no')->where('work_order_no', '<>', ''));
    }

    /** Docket numbers really used by the range's vouchers (with a work order) of that month. */
    private function getAvailableDockets(User $rangeUser, string $month): array
    {
        return $this->withWorkOrder($this->query($rangeUser))->where('entry_month', $month)->whereNotNull('docket_no')
            ->distinct()->orderBy('docket_no')->pluck('docket_no')->map(fn ($d) => (string) $d)->all();
    }

    private function date(mixed $value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    private function compileWorkOrdersData(
        User $rangeUser,
        string $month,
        ?string $docketNo,
        string $attachmentMode,
        int $extendDays,
        bool $printAllDockets
    ): array {
        $base = $this->query($rangeUser);
        if ($month !== '') {
            $base->where('entry_month', $month);
        }
        if (! $printAllDockets && ! blank($docketNo)) {
            is_numeric($docketNo) ? $base->where('docket_no', (int) $docketNo) : $base->whereRaw('1 = 0');
        }
        $withoutWorkOrder = (clone $base)->whereDoesntHave('items', fn ($i) => $i->whereNotNull('work_order_no')->where('work_order_no', '<>', ''))->count();
        $vouchers = $this->withWorkOrder($base)->with('items')->orderBy('entry_type')->orderBy('serial_number')->get();
        $parties = PartyRegistration::query()->where('range_id', $rangeUser->id)->get()->keyBy('party_code');

        $workOrders = [];
        foreach ($vouchers as $v) {
            $items = $v->items->values();
            $orderNos = $items->pluck('work_order_no')->filter(fn ($x) => filled($x))->unique()->values();
            $start = $items->pluck('work_start_date')->filter()->min() ?? $items->pluck('work_order_date')->filter()->min();
            $end = $items->pluck('work_end_date')->filter()->max();
            $extended = $end ? Carbon::parse($end)->addDays($extendDays)->format('d/m/Y') : '';
            $lines = $items->map(fn ($i) => [
                'sr_no' => (int) $i->line_no,
                'description' => $i->description,
                'plot_area' => $v->place ?: '',
                'quantity' => $i->quantity !== null ? (float) $i->quantity : null,
                'unit' => $i->unit,
                'rate' => $i->rate !== null ? (float) $i->rate : null,
                'amount' => (float) $i->amount,
            ])->all();
            $total = array_sum(array_column($lines, 'amount')) ?: (float) $v->sub_total;
            $party = $parties->get($v->party_code);

            $workOrders[] = [
                'id' => $v->id,
                'serial_number' => (int) $v->serial_number,
                'entry_type' => $v->entry_type,
                'work_order_no' => $orderNos->implode(', '),
                'outward_no' => (string) $v->bill_order_outward_no,
                'voucher_no' => $v->voucher_no_final ?: 'Sr. '.$v->serial_number,
                'docket_no' => $v->docket_no === null ? '' : (string) $v->docket_no,
                'party_name' => $v->party_name,
                'party_address' => $party?->party_address ?? $v->address ?? '',
                'budget_code' => $v->budget_code,
                'head' => $v->operating_head ?? '',
                'scheme' => $v->scheme,
                'round' => $v->round,
                'beat' => $v->beat,
                'place' => $v->place,
                'start_date' => $this->date($start),
                'original_end_date' => $this->date($end),
                'extended_end_date' => $extended,
                'extend_days' => $extendDays,
                'items' => $lines,
                'total_amount' => $total,
                'total_amount_words' => $this->numberToWords((int) round($total)).' Rupees Only',
                'date' => $this->date($items->pluck('work_order_date')->filter()->min() ?? $start),
            ];
        }

        return [
            'month' => $month,
            'attachment_mode' => $attachmentMode,
            'extend_days' => $extendDays,
            'print_all_dockets' => $printAllDockets,
            'docket_no' => $printAllDockets || blank($docketNo) ? 'All Dockets ('.$month.')' : $docketNo,
            'range_name' => $rangeUser->name,
            'division_name' => ($rangeUser->division ?? $rangeUser)->getOrCreateOfficeProfile()->display_name,
            'work_orders' => $workOrders,
            'without_work_order' => $withoutWorkOrder,
            'totals' => ['count' => count($workOrders), 'total_amount' => array_sum(array_column($workOrders, 'total_amount'))],
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    /** The range's real month x docket batches that have work orders (latest month first). */
    private function getRecentWorkOrdersSummary(User $rangeUser): array
    {
        $vouchers = $this->withWorkOrder($this->query($rangeUser))->whereNotNull('entry_month')->with('items')->get(['id', 'entry_month', 'docket_no', 'sub_total']);
        $order = array_flip($this->months);
        $groups = [];
        foreach ($vouchers as $v) {
            $k = $v->entry_month.'|'.$v->docket_no;
            $groups[$k] ??= ['month' => $v->entry_month, 'docket_no' => $v->docket_no === null ? '' : (string) $v->docket_no, 'count' => 0, 'total_amount' => 0.0, 'mode' => 'Separate'];
            $groups[$k]['count']++;
            $groups[$k]['total_amount'] += (float) $v->items->sum('amount') ?: (float) $v->sub_total;
        }
        usort($groups, fn ($x, $y) => [$order[$y['month']] ?? -1, (int) $y['docket_no']] <=> [$order[$x['month']] ?? -1, (int) $x['docket_no']]);

        return array_slice($groups, 0, 12);
    }

    private function authorizeRange(Request $request): void
    {
        abort_unless($request->user()?->isRange(), 403);
    }

    private function numberToWords(int $num): string
    {
        $ones = [
            0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
            6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
            11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
            15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen'
        ];
        $tens = [
            2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
            6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
        ];

        if ($num < 20) {
            return $ones[$num] ?? '';
        }
        if ($num < 100) {
            return $tens[(int)($num / 10)] . ($num % 10 ? ' ' . $ones[$num % 10] : '');
        }
        if ($num < 1000) {
            return $ones[(int)($num / 100)] . ' Hundred' . ($num % 100 ? ' and ' . $this->numberToWords($num % 100) : '');
        }
        if ($num < 100000) {
            return $this->numberToWords((int)($num / 1000)) . ' Thousand' . ($num % 1000 ? ' ' . $this->numberToWords($num % 1000) : '');
        }
        if ($num < 10000000) {
            return $this->numberToWords((int)($num / 100000)) . ' Lakh' . ($num % 100000 ? ' ' . $this->numberToWords($num % 100000) : '');
        }

        return $this->numberToWords((int)($num / 10000000)) . ' Crore' . ($num % 10000000 ? ' ' . $this->numberToWords($num % 10000000) : '');
    }
}
