<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\AllotmentToRange;
use App\Models\BudgetCode;
use App\Support\BudgetPosition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Allotments to ranges are increments (may be negative corrections): a range's allotment is the SUM of its entries. */
class AllotmentToRangeController extends Controller
{
    public function create(Request $request): View
    {
        $this->authorizeDivision($request);

        return view('admin.finance.allotments-to-range.form', [
            'budgetCodes' => BudgetCode::query()->orderBy('budget_code')->get(),
            'ranges' => $request->user()->ranges()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function budgetDetails(Request $request, BudgetCode $budgetCode): JsonResponse
    {
        $this->authorizeDivision($request);
        $position = BudgetPosition::for($request->user()->id, $budgetCode->id);
        abort_unless($position['circle_entry'], 404, 'No allotment from Circle is available for this budget code.');

        return response()->json($this->budgetPayload($request, $budgetCode, $position));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDivision($request);
        $validated = $request->validate([
            'budget_code_id' => ['required', Rule::exists('budget_codes', 'id')->where('financial_year_id', \App\Support\CurrentFinancialYear::id())],
            'remark' => ['nullable', 'string', 'max:1000'],
            'allocations' => ['required', 'array'],
            'allocations.*.range_id' => ['required', 'integer'],
            'allocations.*.target' => ['nullable', 'numeric'],
            'allocations.*.allotment' => ['nullable', 'numeric'],
        ]);

        $rangeIds = $request->user()->ranges()->pluck('id');
        $budgetCode = BudgetCode::findOrFail($validated['budget_code_id']);
        $position = BudgetPosition::for($request->user()->id, $budgetCode->id);
        abort_unless($position['circle_entry'], 422, 'No allotment from Circle is available for this budget code.');

        $allocations = collect($validated['allocations'])
            ->filter(fn (array $a) => (float) ($a['target'] ?? 0) !== 0.0 || (float) ($a['allotment'] ?? 0) !== 0.0)->values();
        abort_if($allocations->isEmpty(), 422, 'Enter a target or allotment for at least one range.');
        abort_unless($allocations->every(fn (array $a) => $rangeIds->contains($a['range_id'])), 422);

        $newAllotment = $allocations->sum(fn (array $a) => (float) ($a['allotment'] ?? 0));
        abort_if($newAllotment > $position['pending'] + 0.005, 422, 'The distribution exceeds the pending allotment ('.number_format($position['pending'], 2).') of this budget code.');

        $next = (int) AllotmentToRange::where('division_id', $request->user()->id)->max('ledger_sr');
        DB::transaction(function () use ($allocations, $request, $budgetCode, $validated, $next): void {
            foreach ($allocations as $a) {
                AllotmentToRange::create([
                    'division_id' => $request->user()->id, 'range_id' => $a['range_id'], 'budget_code_id' => $budgetCode->id,
                    'ledger_sr' => ++$next, 'target' => $a['target'] ?? 0, 'amount' => $a['allotment'] ?? 0, 'remark' => $validated['remark'] ?? null,
                    'source_time_stamp' => now(), 'entered_by' => $request->user()->email, 'times_edited' => 0,
                ]);
            }
        });

        return redirect()->route('division.allotments-to-range.create')->with('status', 'Allotment distributed to the selected ranges successfully.');
    }

    /** @return array<string, mixed> */
    private function budgetPayload(Request $request, BudgetCode $budgetCode, array $position): array
    {
        $existing = AllotmentToRange::query()->where('division_id', $request->user()->id)->where('budget_code_id', $budgetCode->id)->get()->groupBy('range_id');

        $ranges = $request->user()->ranges()->orderBy('name')->get(['id', 'name'])->map(function ($range) use ($existing) {
            $entries = $existing->get($range->id, collect());
            $allotment = (float) $entries->sum('amount');

            return ['id' => $range->id, 'name' => $range->name, 'existing_target' => (float) $entries->sum('target'), 'existing_allotment' => $allotment,
                'expenditure' => 0, 'vouchers_entered' => 0, 'remaining_allotment' => $allotment];
        });

        $circle = $position['circle_entry'];

        return [
            'budget' => [
                'id' => $budgetCode->id, 'budget_code' => $budgetCode->budget_code,
                'rate' => $circle->rate ?? '', 'target' => $circle->target ?? '',
                'allotment_from_circle' => $position['circle'], 'adjusted_allotment' => $position['adjusted'],
                'pending_allotment' => $position['pending'], 'pending_target' => $circle->target ?? '',
                'scheme' => $budgetCode->scheme, 'model' => $budgetCode->model,
            ],
            'ranges' => $ranges,
        ];
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()->isDivision(), 403);
    }
}
