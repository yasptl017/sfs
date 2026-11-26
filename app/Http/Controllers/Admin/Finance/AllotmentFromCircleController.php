<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\AllotmentFromCircle;
use App\Models\BudgetCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Circle allotments are cumulative restatements: the newest entry of a budget code is its current allotment. */
class AllotmentFromCircleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        return view('admin.finance.allotments-from-circle.index', [
            'entries' => AllotmentFromCircle::query()->with('budgetCode')
                ->where('division_id', $request->user()->id)
                ->orderByDesc('source_time_stamp')->orderByDesc('id')
                ->paginate(25),
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
            'ledger_sr' => ((int) AllotmentFromCircle::where('division_id', $request->user()->id)->max('ledger_sr')) + 1,
            'rate' => $data['rate'], 'target' => $data['target'], 'amount' => $data['allotment'], 'allotment_date' => $data['allotment_date'],
            'source_time_stamp' => now(), 'entered_by' => $request->user()->email, 'times_edited' => 0,
        ]);

        return redirect()->route('division.allotments-from-circle.index')
            ->with('status', 'Allotment from Circle entry created successfully. It is now the current (cumulative) allotment of this budget code.');
    }

    public function edit(Request $request, AllotmentFromCircle $allotmentFromCircle): View
    {
        $this->authorizeOwner($request, $allotmentFromCircle);

        return view('admin.finance.allotments-from-circle.form', array_merge($this->formData(), ['entry' => $allotmentFromCircle->load('budgetCode')]));
    }

    public function update(Request $request, AllotmentFromCircle $allotmentFromCircle): RedirectResponse
    {
        $this->authorizeOwner($request, $allotmentFromCircle);
        $data = $this->validatedData($request);

        $allotmentFromCircle->update([
            'budget_code_id' => BudgetCode::where('budget_code', $data['budget_code'])->value('id'),
            'rate' => $data['rate'], 'target' => $data['target'], 'amount' => $data['allotment'], 'allotment_date' => $data['allotment_date'],
            'times_edited' => ((int) $allotmentFromCircle->times_edited) + 1,
        ]);

        return redirect()->route('division.allotments-from-circle.index')->with('status', 'Allotment from Circle entry updated successfully.');
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
            'budgetCodeDetails' => $budgetCodes->mapWithKeys(fn (BudgetCode $code) => [$code->budget_code => $code->only(['operating_head', 'scheme', 'object_class', 'object_code', 'description', 'item', 'model', 'scheme_year'])]),
        ];
    }

    /** @return array<string, mixed> */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'budget_code' => ['required', 'string', Rule::exists('budget_codes', 'budget_code')->where('financial_year_id', \App\Support\CurrentFinancialYear::id())],
            'allotment_date' => ['required', 'date'],
            'rate' => ['required', 'numeric', 'min:0'],
            'target' => ['required', 'numeric', 'min:0'],
            'allotment' => ['required', 'numeric', 'min:0'],
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
