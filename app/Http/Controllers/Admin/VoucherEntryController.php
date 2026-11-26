<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetCode;
use App\Models\PartyRegistration;
use App\Models\RangeLocation;
use App\Models\SfBeneficiary;
use App\Models\Voucher;
use App\Models\VoucherVersion;
use App\Models\WlBeneficiary;
use App\Support\CurrentFinancialYear;
use App\Support\VoucherCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Create / edit / delete vouchers of all six entry types on the typed tables (range logins, own vouchers only). */
class VoucherEntryController extends Controller
{
    public const TYPES = [
        'tender' => 'Tender Entry', 'free' => 'Free Entry', 'dw-salary' => 'D.Wagers Salary',
        'dw-arrears' => 'D.Wagers Arrears', 'sf-bene' => 'Bene. Entry', 'wl-bene' => 'WL Bene. Entry',
    ];

    /** Columns a range may type (everything else - advice, treasury, payment, audit - is set by the system / Division). */
    private const INPUTS = ['entry_month', 'docket_no', 'tender_code', 'budget_code', 'round', 'beat', 'place', 'area_ha', 'description', 'bill_no', 'bill_date',
        'register_name', 'register_page_no', 'remarks_by_rfo', 'party_code', 'party_name', 'pan_card_no', 'gst_no', 'account_no', 'bank_name', 'ifsc', 'branch',
        'approved_percent', 'sgst', 'cgst', 'igst', 'labour_cess', 'adjustment', 'deduction_sgst', 'deduction_cgst', 'deduction_igst', 'deduction_labour_cess',
        'gpf', 'cpf', 'p_tax', 'group_insurance', 'deposit_deduction_amount', 'tds', 'other_deductions',
        'bene_code', 'bene_full_name', 'year_installment_no', 'bene_application_date', 'bene_application_sanctioned_date', 'name_of_yojana', 'bene_name_gujarati',
        'mobile_no', 'gender', 'father_name', 'birth_date', 'address', 'pin_code', 'category', 'district', 'taluka', 'village', 'survey_block_no', 'id_type', 'id_no',
        'gps_n', 'gps_e', 'gps', 'bene_selection_year', 'ha_dcp_plants', 'total_no_of_plants', 'survival_data_date', 'survival_percent', 'sf_model_year',
        'range_case_number', 'division_case_number'];

    public const MONTHS = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];

    public function create(Request $request, string $type): View
    {
        $this->authorizeRange($request);
        $label = $this->label($type);
        $copy = null;
        if ($serial = $request->query('copy')) {
            $copy = Voucher::where('range_id', $request->user()->id)->where('entry_type', $label)->where('serial_number', $serial)->with('items')->first();
        }

        return view('admin.vouchers.form', $this->formData($request, $type, $copy, copy: true, voucher: null));
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->authorizeRange($request);
        $label = $this->label($type);
        [$data, $items] = $this->validated($request);
        $user = $request->user();

        $voucher = DB::transaction(function () use ($data, $items, $label, $user) {
            $serial = ((int) Voucher::where('financial_year_id', CurrentFinancialYear::id())->where('range_id', $user->id)->where('entry_type', $label)->lockForUpdate()->max('serial_number')) + 1;
            $v = Voucher::create($data + $this->totalsFor($data, $items) + [
                'financial_year_id' => CurrentFinancialYear::id(), 'range_id' => $user->id, 'range_name' => $user->name, 'entry_type' => $label, 'serial_number' => $serial,
                'entered_by' => $user->email, 'source_time_stamp' => now(), 'times_edited' => 0,
                'only_in_older_export' => false, 'source_kind' => 'entry_form',
            ] + $this->fromBudgetCode($data['budget_code'] ?? null) + ['item_details_json' => $this->itemsJson($items)]);
            $this->saveItems($v, $items);

            return $v;
        });

        return redirect()->route('vouchers.show', $voucher)->with('status', "{$label} Sr. {$voucher->serial_number} saved.");
    }

    public function edit(Request $request, Voucher $voucher): View
    {
        $this->authorizeOwn($request, $voucher);
        $voucher->load('items');

        return view('admin.vouchers.form', $this->formData($request, array_search($voucher->entry_type, self::TYPES, true), $voucher, copy: false, voucher: $voucher));
    }

    public function update(Request $request, Voucher $voucher): RedirectResponse
    {
        $this->authorizeOwn($request, $voucher);
        [$data, $items] = $this->validated($request);
        $voucher->load('items');

        DB::transaction(function () use ($voucher, $data, $items, $request) {
            $before = $this->snapshot($voucher);
            $oldInputs = $this->inputsOf($voucher);
            $sameInputs = VoucherCalculator::signature($oldInputs['v'], $oldInputs['items']) === VoucherCalculator::signature($data, $items);
            // nothing that feeds the totals changed -> keep the stored (migrated) amounts exactly as they are
            $totals = $sameInputs ? [] : $this->totalsFor($data, $items);

            $voucher->fill($data + $totals + $this->fromBudgetCode($data['budget_code'] ?? null) + ['item_details_json' => $this->itemsJson($items)]);
            $voucher->times_edited = ((int) $voucher->times_edited) + 1;
            $voucher->save();
            $voucher->items()->delete();
            $this->saveItems($voucher, $items);

            $this->storeVersion($voucher, $before, 'before_edit', $this->changedHeaders($before, $voucher->fresh('items'), $oldInputs['items'], $items));
        });

        return redirect()->route('vouchers.show', $voucher)->with('status', 'Voucher updated. The earlier version is kept under "Earlier versions".');
    }

    public function destroy(Request $request, Voucher $voucher): RedirectResponse
    {
        $this->authorizeOwn($request, $voucher);
        $voucher->load('items');
        $label = "{$voucher->entry_type} Sr. {$voucher->serial_number}";

        DB::transaction(function () use ($voucher) {
            $this->storeVersion($voucher, $this->snapshot($voucher), 'deleted', ['(deleted)'], detach: true);
            $voucher->delete();
        });

        return redirect()->route('vouchers.index', ['entry_type' => $voucher->entry_type])->with('status', "$label deleted (a copy is kept in the audit trail).");
    }

    // --------------------------------------------------------------------------------------------- helpers
    private function authorizeRange(Request $request): void
    {
        abort_unless($request->user()->isRange(), 403, 'Only range logins enter vouchers.');
    }

    private function authorizeOwn(Request $request, Voucher $voucher): void
    {
        $this->authorizeRange($request);
        abort_unless($voucher->range_id === $request->user()->id && $voucher->financial_year_id === CurrentFinancialYear::id(), 403);
        abort_if($voucher->advice_no !== null, 403, 'This voucher is already part of an advice sent to the Division/treasury and can no longer be edited.');
    }

    private function label(string $type): string
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    /** @return array{0: array<string, mixed>, 1: array<int, array<string, mixed>>} */
    private function validated(Request $request): array
    {
        $kinds = collect(config('voucher_columns'))->mapWithKeys(fn ($d) => [$d[0] => $d[1]]);
        $rules = [
            'v.entry_month' => ['required', Rule::in(self::MONTHS)], 'v.budget_code' => ['required', Rule::exists('budget_codes', 'budget_code')->where('financial_year_id', CurrentFinancialYear::id())],
            'v.party_name' => ['required', 'string', 'max:255'], 'v.party_code' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'], 'items.*.description' => ['required', 'string', 'max:5000'],
            'items.*.quantity' => ['nullable', 'numeric'], 'items.*.rate' => ['nullable', 'numeric'], 'items.*.amount' => ['nullable', 'numeric'],
            'items.*.sor_code' => ['nullable', 'string', 'max:255'], 'items.*.unit' => ['nullable', 'string', 'max:255'], 'items.*.work_order_no' => ['nullable', 'string', 'max:500'],
            'items.*.work_order_date' => ['nullable', 'date'], 'items.*.work_start_date' => ['nullable', 'date'], 'items.*.work_end_date' => ['nullable', 'date'],
        ];
        foreach (self::INPUTS as $col) {
            $rules["v.$col"] ??= match ($kinds[$col] ?? 'str') {
                'dec', 'int' => ['nullable', 'numeric'], 'date' => ['nullable', 'date'], 'datetime' => ['nullable', 'date'], default => ['nullable', 'string', 'max:5000'],
            };
        }
        $valid = $request->validate($rules, ['items.required' => 'Add at least one line item.', 'items.*.description.required' => 'Every line item needs a description.']);

        $data = [];
        foreach (self::INPUTS as $col) {
            if (! isset($kinds[$col])) {
                continue;
            }
            $value = $valid['v'][$col] ?? null;
            $data[$col] = $value === '' ? null : $value;
        }
        $items = array_values(array_map(fn ($it) => [
            'sor_code' => $it['sor_code'] ?? null, 'description' => $it['description'], 'work_order_no' => $it['work_order_no'] ?? null,
            'work_order_date' => $it['work_order_date'] ?? null, 'work_start_date' => $it['work_start_date'] ?? null, 'work_end_date' => $it['work_end_date'] ?? null,
            'quantity' => ($it['quantity'] ?? '') === '' ? null : $it['quantity'], 'unit' => $it['unit'] ?? null, 'rate' => ($it['rate'] ?? '') === '' ? null : $it['rate'],
            'amount' => ($it['amount'] ?? '') === '' ? null : $it['amount'],
        ], $valid['items']));

        return [$data, $items];
    }

    /** @return array<string, mixed> */
    private function totalsFor(array $data, array $items): array
    {
        return VoucherCalculator::totals($data, $items);
    }

    /** Scheme / class / model / scheme year / payment mode come from the chosen budget code. */
    private function fromBudgetCode(?string $code): array
    {
        $b = $code ? BudgetCode::where('budget_code', $code)->first() : null;

        return $b ? ['scheme' => $b->scheme, 'class' => $b->object_class, 'model' => $b->model, 'scheme_year' => $b->scheme_year, 'payment_mode' => $b->payment_mode] : [];
    }

    private function itemsJson(array $items): string
    {
        return json_encode(array_map(fn ($i) => $this->rawLine($i), $items), JSON_UNESCAPED_UNICODE);
    }

    private function rawLine(array $i): array
    {
        $s = fn ($v) => $v === null ? '' : (is_float($v) || is_int($v) || is_numeric($v) ? (string) (0 + $v) : (string) $v);
        $d = fn ($v) => $v ? Carbon::parse($v)->format('Y-m-d') : '';

        return [(string) ($i['sor_code'] ?? ''), (string) $i['description'], (string) ($i['work_order_no'] ?? ''), $d($i['work_order_date'] ?? null), $d($i['work_start_date'] ?? null),
            $d($i['work_end_date'] ?? null), $s($i['quantity'] ?? null), (string) ($i['unit'] ?? ''), $s($i['rate'] ?? null), $s(VoucherCalculator::itemAmount($i))];
    }

    private function saveItems(Voucher $voucher, array $items): void
    {
        foreach ($items as $n => $i) {
            $voucher->items()->create([
                'line_no' => $n + 1, 'sor_code' => $i['sor_code'] ?: null, 'description' => $i['description'], 'work_order_no' => $i['work_order_no'] ?: null,
                'work_order_date' => $i['work_order_date'] ?: null, 'work_start_date' => $i['work_start_date'] ?: null, 'work_end_date' => $i['work_end_date'] ?: null,
                'quantity' => $i['quantity'], 'unit' => $i['unit'] ?: null, 'rate' => $i['rate'], 'amount' => VoucherCalculator::itemAmount($i), 'raw_line' => json_encode($this->rawLine($i), JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /** @return array{v: array<string, mixed>, items: array<int, array<string, mixed>>} */
    private function inputsOf(Voucher $voucher): array
    {
        $v = [];
        foreach (array_merge(['approved_percent'], VoucherCalculator::ADDITIONS, VoucherCalculator::DEDUCTIONS) as $k) {
            $v[$k] = $voucher->{$k};
        }

        return ['v' => $v, 'items' => $voucher->items->map(fn ($i) => ['quantity' => $i->quantity, 'rate' => $i->rate, 'amount' => $i->amount])->all()];
    }

    /** Header-keyed snapshot in the same shape as the migrated History00 copies (voucher fields on the first line, line fields on every line). */
    private function snapshot(Voucher $voucher): array
    {
        $map = config('voucher_columns');
        $fields = [];
        foreach ($map as $header => [$col]) {
            $val = $voucher->{$col};
            if ($val !== null && $header !== 'Item Details') {
                $fields[$header] = $val instanceof \DateTimeInterface ? $val->format('Y-m-d H:i:s') : $val;
            }
        }
        $rows = [];
        foreach ($voucher->items as $n => $i) {
            $line = ['SOR Code' => $i->sor_code, 'Work Description' => $i->description, 'Work Order No.' => $i->work_order_no, 'Work Order Date' => $i->work_order_date,
                'Work Start Date' => $i->work_start_date, 'Work End Date' => $i->work_end_date, 'No. of Unit' => $i->quantity, 'Unit' => $i->unit, 'SOR Rate' => $i->rate, 'Amount' => $i->amount];
            $rows[] = ['r' => null, 'c' => ($n === 0 ? $fields : []) + array_filter($line, fn ($x) => $x !== null)];
        }

        return ['rows' => $rows ?: [['r' => null, 'c' => $fields]]];
    }

    private function changedHeaders(array $before, Voucher $after, array $oldItems, array $newItems): array
    {
        $map = config('voucher_columns');
        $old = $before['rows'][0]['c'] ?? [];
        $changed = [];
        foreach ($map as $header => [$col, $kind]) {
            if ($header === 'Item Details') {
                continue;
            }
            $a = $old[$header] ?? null;
            $b = $after->{$col};
            $b = $b instanceof \DateTimeInterface ? $b->format('Y-m-d H:i:s') : $b;
            $norm = fn ($x) => $x === null || $x === '' ? null : (is_numeric($x) ? round((float) $x, 4) : preg_replace('/ 00:00:00$/', '', (string) $x));
            if ($norm($a) !== $norm($b) && ! in_array($header, ['No. of times Edited'], true)) {
                $changed[] = $header;
            }
        }
        if (json_encode(array_map(fn ($i) => [round((float) ($i['quantity'] ?? 0), 4), round((float) ($i['rate'] ?? 0), 4), round((float) VoucherCalculator::itemAmount($i), 2)], $oldItems))
            !== json_encode(array_map(fn ($i) => [round((float) ($i['quantity'] ?? 0), 4), round((float) ($i['rate'] ?? 0), 4), round((float) VoucherCalculator::itemAmount($i), 2)], $newItems))) {
            $changed[] = 'Item Details';
        }

        return $changed;
    }

    private function storeVersion(Voucher $voucher, array $snapshot, string $kind, array $changed, bool $detach = false): void
    {
        VoucherVersion::create([
            'financial_year_id' => $voucher->financial_year_id, 'range_id' => $voucher->range_id, 'entry_type' => $voucher->entry_type, 'serial_number' => $voucher->serial_number,
            'voucher_id' => $detach ? null : $voucher->id, 'version_kind' => $kind, 'source_file' => 'entry form', 'staging_sheet_id' => null, 'source_rows' => [],
            'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE), 'differs_from_live' => $changed !== [], 'diff_fields' => $changed ?: null,
        ]);
    }

    /** @return array<string, mixed> */
    private function formData(Request $request, string $type, ?Voucher $source, bool $copy, ?Voucher $voucher): array
    {
        $user = $request->user();
        $label = $this->label($type);
        $locations = RangeLocation::query()->where('range_id', $user->id)->get();
        $value = fn (string $col, mixed $default = '') => old("v.$col", $source?->{$col} ?? $default);

        return [
            'type' => $type, 'label' => $label, 'voucher' => $voucher, 'source' => $source, 'isCopy' => $copy && $source !== null,
            'next' => $voucher ? $voucher->serial_number : ((int) Voucher::where('range_id', $user->id)->where('entry_type', $label)->max('serial_number')) + 1,
            'months' => self::MONTHS, 'value' => $value,
            'budgetCodes' => BudgetCode::orderBy('budget_code')->get(['budget_code', 'scheme', 'object_class', 'model', 'scheme_year', 'item'])->keyBy('budget_code'),
            'beatsByRound' => $locations->groupBy('round')->map(fn ($g) => $g->pluck('beat')->unique()->sort()->values()->all())->all(),
            'placesByBeat' => $locations->groupBy('beat')->map(fn ($g) => $g->pluck('place')->filter()->unique()->sort()->values()->all())->all(),
            'rounds' => $locations->pluck('round')->filter()->unique()->sort()->values()->all(),
            'parties' => PartyRegistration::where('range_id', $user->id)->orderBy('party_code')->get(['party_code', 'party_name', 'pan_card_no', 'gst_no', 'account_no', 'bank_name', 'ifsc', 'branch', 'approved_percent', 'small_description'])->keyBy('party_code'),
            'dwagers' => \Illuminate\Support\Facades\DB::table('d_wagers')->where('financial_year_id', CurrentFinancialYear::id())->where('range_id', $user->id)->orderBy('d_wager_code')->get(['d_wager_code', 'd_wager_name', 'pan_card_no', 'account_no', 'bank_name', 'ifsc', 'branch'])->keyBy('d_wager_code'),
            'sfBenes' => SfBeneficiary::where('range_id', $user->id)->orderBy('sf_bene_code')->get()->keyBy('sf_bene_code'),
            'wlBenes' => WlBeneficiary::where('range_id', $user->id)->orderBy('wl_bene_code')->get()->keyBy('wl_bene_code'),
            'items' => old('items', $source ? $source->items->map(fn ($i) => [
                'sor_code' => $i->sor_code, 'description' => $i->description, 'work_order_no' => $i->work_order_no, 'work_order_date' => $i->work_order_date, 'work_start_date' => $i->work_start_date,
                'work_end_date' => $i->work_end_date, 'quantity' => $i->quantity, 'unit' => $i->unit, 'rate' => $i->rate, 'amount' => $i->amount,
            ])->all() : [[]]),
        ];
    }
}
