<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\AllotmentFromCircle;
use App\Models\AllotmentToRange;
use App\Models\BudgetCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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
        $circleEntry = AllotmentFromCircle::query()
            ->where('division_id', $request->user()->id)
            ->where('budget_code_id', $budgetCode->id)
            ->latest()
            ->first();

        abort_unless($circleEntry, 404, 'No allotment from Circle is available for this budget code.');

        return response()->json($this->budgetPayload($request, $budgetCode, $circleEntry));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDivision($request);
        $validated = $request->validate([
            'budget_code_id' => ['required', Rule::exists('budget_codes', 'id')],
            'remark' => ['nullable', 'string', 'max:1000'],
            'allocations' => ['required', 'array'],
            'allocations.*.range_id' => ['required', 'integer'],
            'allocations.*.target' => ['nullable', 'numeric'],
            'allocations.*.allotment' => ['nullable', 'numeric'],
        ]);

        $rangeIds = $request->user()->ranges()->pluck('id');
        $budgetCode = BudgetCode::findOrFail($validated['budget_code_id']);
        $circleEntry = AllotmentFromCircle::query()
            ->where('division_id', $request->user()->id)
            ->where('budget_code_id', $budgetCode->id)
            ->latest()
            ->firstOrFail();

        $allocations = collect($validated['allocations'])
            ->filter(fn (array $allocation) => (float) ($allocation['target'] ?? 0) !== 0.0 || (float) ($allocation['allotment'] ?? 0) !== 0.0)
            ->values();

        abort_if($allocations->isEmpty(), 422, 'Enter a target or allotment for at least one range.');
        abort_unless($allocations->every(fn (array $allocation) => $rangeIds->contains($allocation['range_id'])), 422);

        $existingAllotment = (float) AllotmentToRange::query()
            ->where('division_id', $request->user()->id)
            ->where('budget_code_id', $budgetCode->id)
            ->sum(DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT(data, '$.allotment')) AS DECIMAL(15,2))"));
        $newAllotment = $allocations->sum(fn (array $allocation) => (float) ($allocation['allotment'] ?? 0));
        abort_if($existingAllotment + $newAllotment > (float) $circleEntry->data['allotment'], 422, 'The distribution exceeds the available allotment from Circle.');

        DB::transaction(function () use ($allocations, $request, $budgetCode, $validated): void {
            foreach ($allocations as $allocation) {
                AllotmentToRange::create([
                    'division_id' => $request->user()->id,
                    'range_id' => $allocation['range_id'],
                    'budget_code_id' => $budgetCode->id,
                    'data' => [
                        'budget_code' => $budgetCode->budget_code,
                        'target' => $allocation['target'] ?? 0,
                        'allotment' => $allocation['allotment'] ?? 0,
                        'remark' => $validated['remark'] ?? null,
                    ],
                ]);
            }
        });

        return redirect()->route('division.allotments-to-range.create')
            ->with('status', 'Allotment distributed to the selected ranges successfully.');
    }

    /** @return array<string, mixed> */
    private function budgetPayload(Request $request, BudgetCode $budgetCode, AllotmentFromCircle $circleEntry): array
    {
        $existing = AllotmentToRange::query()
            ->where('division_id', $request->user()->id)
            ->where('budget_code_id', $budgetCode->id)
            ->get()
            ->groupBy('range_id');

        $ranges = $request->user()->ranges()->orderBy('name')->get(['id', 'name'])->map(function ($range) use ($existing) {
            $entries = $existing->get($range->id, collect());
            $allotment = $entries->sum(fn (AllotmentToRange $entry) => (float) ($entry->data['allotment'] ?? 0));
            $target = $entries->sum(fn (AllotmentToRange $entry) => (float) ($entry->data['target'] ?? 0));

            return ['id' => $range->id, 'name' => $range->name, 'existing_target' => $target, 'existing_allotment' => $allotment, 'expenditure' => 0, 'vouchers_entered' => 0, 'remaining_allotment' => $allotment];
        });

        $allocated = $ranges->sum('existing_allotment');
        $circleAllotment = (float) ($circleEntry->data['allotment'] ?? 0);

        return [
            'budget' => [
                'id' => $budgetCode->id,
                'budget_code' => $budgetCode->budget_code,
                'rate' => $circleEntry->data['rate'] ?? '',
                'target' => $circleEntry->data['target'] ?? '',
                'allotment_from_circle' => $circleAllotment,
                'adjusted_allotment' => $circleAllotment - $allocated,
                'pending_target' => $circleEntry->data['target'] ?? '',
                'scheme' => $budgetCode->scheme,
                'model' => $budgetCode->model,
            ],
            'ranges' => $ranges,
        ];
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()->isDivision(), 403);
    }
}
