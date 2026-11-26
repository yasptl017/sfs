<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\PartyRegistration;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use App\Support\ExpenditureReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Voucher print (Form No. 35) for a range's own vouchers, read from the typed voucher tables.
 * Only real vouchers are printed: serial numbers that do not exist are reported as "not found", never invented.
 */
class VoucherPrintController extends Controller
{
    /** label shown in the screen => entry type stored on the voucher */
    protected array $entryTypeMap = [
        'Tender Entry' => 'Tender Entry',
        'Free Entry' => 'Free Entry',
        'D.Wagers Salary' => 'D.Wagers Salary',
        'D.Wagers Arrears' => 'D.Wagers Arrears',
        'WL Bene. Entry' => 'WL Bene. Entry',
        'Bene. Entry' => 'Bene. Entry',
        'SF Bene. Entry' => 'Bene. Entry',
    ];

    public function index(Request $request): View
    {
        $this->authorizeRange($request);

        $selectedType = $request->query('entry_type', 'Tender Entry');
        if (! array_key_exists($selectedType, $this->entryTypeMap)) {
            $selectedType = 'Tender Entry';
        }

        $availableEntries = $this->getEntriesForType($request->user(), $selectedType);

        return view('admin.finance.voucher-print.index', [
            'entryTypes' => array_keys($this->entryTypeMap),
            'selectedType' => $selectedType,
            'availableEntries' => $availableEntries,
            'suggestedRange' => $this->suggestedRange($availableEntries),
        ]);
    }

    public function entries(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $entryType = $request->query('entry_type', 'Tender Entry');
        $entries = $this->getEntriesForType($request->user(), $entryType);

        return response()->json([
            'success' => true,
            'entry_type' => $entryType,
            'entries' => $entries,
            'suggested_range' => $this->suggestedRange($entries),
            'count' => count($entries),
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $validated = $request->validate([
            'entry_type' => ['required', 'string'],
            'serial_numbers' => ['nullable', 'string'],
            'with_work_order' => ['nullable', 'boolean'],
            'fit_to_page' => ['nullable', 'boolean'],
        ]);

        $entryType = $validated['entry_type'];
        $withWorkOrder = (bool) ($validated['with_work_order'] ?? false);
        $fitToPage = (bool) ($validated['fit_to_page'] ?? false);
        [$vouchers, $missing] = $this->compileVouchers($request->user(), $entryType, $this->parseSerialNumbers($validated['serial_numbers'] ?? ''), $withWorkOrder, $fitToPage);

        return response()->json([
            'success' => true,
            'entry_type' => $entryType,
            'count' => count($vouchers),
            'vouchers' => $vouchers,
            'missing' => $missing,
            'fit_to_page' => $fitToPage,
            'with_work_order' => $withWorkOrder,
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorizeRange($request);

        $entryType = $request->query('entry_type', 'Tender Entry');
        $withWorkOrder = $request->boolean('with_work_order', false);
        $fitToPage = $request->boolean('fit_to_page', false);
        [$vouchers, $missing] = $this->compileVouchers($request->user(), $entryType, $this->parseSerialNumbers($request->query('serial_numbers', '')), $withWorkOrder, $fitToPage);

        return view('admin.finance.voucher-print.print', [
            'entryType' => $entryType,
            'vouchers' => $vouchers,
            'missing' => $missing,
            'withWorkOrder' => $withWorkOrder,
            'fitToPage' => $fitToPage,
            'rangeUser' => $request->user(),
            'profile' => $request->user()->getOrCreateOfficeProfile(),
        ]);
    }

    /** "1-4, 19, 23-25" => [1,2,3,4,19,23,24,25] (a descending range such as 5-3 prints 5,4,3). */
    public function parseSerialNumbers(?string $input): array
    {
        if (blank($input)) {
            return [];
        }

        $numbers = [];
        foreach (array_filter(array_map('trim', explode(',', $input))) as $part) {
            if (str_contains($part, '-')) {
                $range = explode('-', $part);
                if (count($range) === 2 && is_numeric(trim($range[0])) && is_numeric(trim($range[1]))) {
                    $start = (int) trim($range[0]);
                    $end = (int) trim($range[1]);
                    foreach ($start <= $end ? range($start, $end) : range($start, $end, -1) as $i) {
                        $numbers[] = $i;
                    }
                }
            } elseif (is_numeric($part)) {
                $numbers[] = (int) $part;
            }
        }

        return array_values(array_unique(array_filter($numbers, fn ($n) => $n > 0)));
    }

    // ------------------------------------------------------------------------------------------------ data
    private function storedType(string $entryType): string
    {
        return $this->entryTypeMap[$entryType] ?? 'Tender Entry';
    }

    private function query(User $rangeUser, string $entryType)
    {
        return Voucher::query()->forYear(CurrentFinancialYear::id())->where('range_id', $rangeUser->id)->where('entry_type', $this->storedType($entryType));
    }

    /** The range's vouchers of one type, for the picker on the print screen. */
    private function getEntriesForType(User $rangeUser, string $entryType): array
    {
        return $this->query($rangeUser, $entryType)->orderBy('serial_number')->get()->map(fn (Voucher $v) => [
            'id' => $v->id,
            'serial_number' => (int) $v->serial_number,
            'entry_type' => $entryType,
            'party_name' => $v->party_name,
            'budget_code' => $v->budget_code,
            'docket_no' => $v->docket_no ?? '-',
            'amount' => (float) $v->payable_total_amount,
            'date' => $this->date($v->bill_date),
        ])->all();
    }

    private function suggestedRange(array $entries): string
    {
        $serials = array_column($entries, 'serial_number');

        return $serials ? min($serials).'-'.max($serials) : '';
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: array<int, int>} printable vouchers in the requested order, and the serial numbers that do not exist */
    private function compileVouchers(User $rangeUser, string $entryType, array $serialNumbers, bool $withWorkOrder, bool $fitToPage): array
    {
        $found = $this->query($rangeUser, $entryType)->whereIn('serial_number', $serialNumbers)->with('items')->get()->keyBy('serial_number');
        $parties = PartyRegistration::query()->where('range_id', $rangeUser->id)->get()->keyBy('party_code');
        $division = $rangeUser->division ?? $rangeUser;
        $divisionName = $division->getOrCreateOfficeProfile()->display_name;

        $vouchers = [];
        $missing = [];
        foreach ($serialNumbers as $serial) {
            $v = $found->get($serial);
            if (! $v) {
                $missing[] = $serial;

                continue;
            }
            $vouchers[] = $this->format($v, $entryType, $rangeUser, $divisionName, $parties->get($v->party_code), $withWorkOrder, $fitToPage);
        }

        return [$vouchers, $missing];
    }

    /** @return array<string, mixed> */
    private function format(Voucher $v, string $entryType, User $rangeUser, string $divisionName, ?PartyRegistration $party, bool $withWorkOrder, bool $fitToPage): array
    {
        $f = fn ($x) => (float) $x;
        $items = $v->items->map(fn ($i) => [
            'sr_no' => (int) $i->line_no, 'item_code' => $i->sor_code, 'description' => $i->description,
            'length' => 0.0, 'breadth' => 0.0, 'depth' => 0.0, // not recorded on the vouchers
            'quantity' => $i->quantity !== null ? (float) $i->quantity : null, 'unit' => $i->unit, 'rate' => $i->rate !== null ? (float) $i->rate : null, 'amount' => (float) $i->amount,
        ])->all();
        $withWo = $v->items->first(fn ($i) => $i->work_order_no);

        $other = $f($v->gpf) + $f($v->cpf) + $f($v->p_tax) + $f($v->group_insurance) + $f($v->other_deductions);
        $net = $f($v->net_amount);

        return [
            'id' => $v->id,
            'serial_number' => (int) $v->serial_number,
            'voucher_no' => $v->voucher_no_final ?: 'Sr. '.$v->serial_number,
            'entry_type' => $entryType,
            'date' => $this->date($v->bill_date),
            'docket_no' => $v->docket_no ?? '-',
            'month' => $this->monthLabel($v),
            'range_name' => $rangeUser->name,
            'division_name' => $divisionName,
            'budget_code' => $v->budget_code,
            'head' => $v->operating_head ?? '',
            'scheme' => $v->scheme,
            'model' => $v->model,
            'scheme_year' => $v->scheme_year,
            'round' => $v->round,
            'beat' => $v->beat,
            'place' => $v->place,
            'party_name' => $v->party_name,
            'party_address' => $party?->party_address ?? $v->address ?? '',
            'pan_card_no' => $v->pan_card_no,
            'gst_no' => $v->gst_no,
            'bank_name' => $v->bank_name,
            'account_no' => $v->account_no,
            'ifsc' => $v->ifsc,
            'branch' => $v->branch,
            'approval_no' => $party?->party_approval_no ?? '',
            'with_work_order' => $withWorkOrder,
            'work_order_no' => $withWo?->work_order_no ?? '',
            'work_order_date' => $this->date($withWo?->work_order_date),
            'fit_to_page' => $fitToPage,
            'items' => $items,
            'gross_amount' => $f($v->payable_total_amount),
            'deductions' => [
                'sgst' => $f($v->deduction_sgst), 'cgst' => $f($v->deduction_cgst), 'igst' => $f($v->deduction_igst), 'labour_cess' => $f($v->deduction_labour_cess),
                'deposit' => $f($v->deposit_deduction_amount), 'tds' => $f($v->tds), 'other' => $other, 'total' => $f($v->total_deduction) ?: $f($v->payable_total_amount) - $net,
            ],
            'net_amount' => $net,
            'net_amount_in_words' => $this->numberToWords((int) round($net)).' Rupees Only',
        ];
    }

    private function date(mixed $value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    private function monthLabel(Voucher $v): string
    {
        if (! $v->entry_month) {
            return '';
        }

        return Carbon::parse(ExpenditureReport::window($v->financial_year_id, $v->entry_month)['start'])->format('F Y');
    }

    private function numberToWords(int $num): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($num == 0) {
            return 'Zero';
        }

        $words = '';
        foreach ([[10000000, 'Crore'], [100000, 'Lakh'], [1000, 'Thousand'], [100, 'Hundred']] as [$unit, $name]) {
            if ($num >= $unit) {
                $words .= $this->numberToWords((int) ($num / $unit)).' '.$name.' ';
                $num %= $unit;
            }
        }
        if ($num > 0) {
            $words .= $num < 20 ? $ones[$num] : $tens[(int) ($num / 10)].($num % 10 ? ' '.$ones[$num % 10] : '');
        }

        return trim($words);
    }

    private function authorizeRange(Request $request): void
    {
        abort_unless($request->user()?->isRange(), 403);
    }
}
