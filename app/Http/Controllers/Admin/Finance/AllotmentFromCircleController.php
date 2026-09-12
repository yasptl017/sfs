<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\AllotmentFromCircle;
use App\Models\BudgetCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AllotmentFromCircleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        return view('admin.finance.allotments-from-circle.index', [
            'entries' => AllotmentFromCircle::query()
                ->where('division_id', $request->user()->id)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeDivision($request);

        return view('admin.finance.allotments-from-circle.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDivision($request);
        $data = $this->validatedData($request);

        AllotmentFromCircle::create([
            'division_id' => $request->user()->id,
            'budget_code_id' => BudgetCode::where('budget_code', $data['budget_code'])->value('id'),
            'data' => $data,
        ]);

        return redirect()->route('division.allotments-from-circle.index')
            ->with('status', 'Allotment from Circle entry created successfully.');
    }

    public function edit(Request $request, AllotmentFromCircle $allotmentFromCircle): View
    {
        $this->authorizeOwner($request, $allotmentFromCircle);

        return view('admin.finance.allotments-from-circle.form', array_merge($this->formData(), [
            'entry' => $allotmentFromCircle,
        ]));
    }

    public function update(Request $request, AllotmentFromCircle $allotmentFromCircle): RedirectResponse
    {
        $this->authorizeOwner($request, $allotmentFromCircle);
        $data = $this->validatedData($request);

        $allotmentFromCircle->update([
            'budget_code_id' => BudgetCode::where('budget_code', $data['budget_code'])->value('id'),
            'data' => $data,
        ]);

        return redirect()->route('division.allotments-from-circle.index')
            ->with('status', 'Allotment from Circle entry updated successfully.');
    }

    public function destroy(Request $request, AllotmentFromCircle $allotmentFromCircle): RedirectResponse
    {
        $this->authorizeOwner($request, $allotmentFromCircle);
        $allotmentFromCircle->delete();

        return back()->with('status', 'Allotment from Circle entry deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        $budgetCodes = BudgetCode::query()->orderBy('budget_code')->get();

        return [
            'entry' => null,
            'budgetCodes' => $budgetCodes,
            'budgetCodeDetails' => $budgetCodes->mapWithKeys(fn (BudgetCode $code) => [$code->budget_code => [
                'operating_head' => $code->operating_head,
                'scheme' => $code->scheme,
                'object_class' => $code->object_class,
                'object_code' => $code->object_code,
                'description' => $code->description,
                'item' => $code->item,
                'model' => $code->model,
                'scheme_year' => $code->scheme_year,
            ]]),
        ];
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'budget_code' => ['required', 'string', Rule::exists('budget_codes', 'budget_code')],
            'allotment_date' => ['required', 'date'],
            'rate' => ['required', 'numeric', 'min:0'],
            'target' => ['required', 'numeric', 'min:0'],
            'allotment' => ['required', 'numeric', 'min:0'],
        ]);

        $budgetCode = BudgetCode::where('budget_code', $validated['budget_code'])->firstOrFail();

        return array_merge($validated, [
            'operating_head' => $budgetCode->operating_head,
            'scheme' => $budgetCode->scheme,
            'object_class' => $budgetCode->object_class,
            'object_code' => $budgetCode->object_code,
            'description' => $budgetCode->description,
            'item' => $budgetCode->item,
            'model' => $budgetCode->model,
            'scheme_year' => $budgetCode->scheme_year,
        ]);
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()->isDivision(), 403);
    }

    private function authorizeOwner(Request $request, AllotmentFromCircle $entry): void
    {
        $this->authorizeDivision($request);
        abort_unless($entry->division_id === $request->user()->id, 403);
    }
}
