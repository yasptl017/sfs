<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\AllotmentAdjustment;
use App\Models\BudgetCode;
use App\Support\BudgetPosition;
use App\Support\CurrentFinancialYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Moves allotment from one budget code to another. The giving code must have enough pending (undistributed) allotment. */
class AllotmentAdjustmentController extends Controller
{
    public function index(Request $r): View
    {
        $this->auth($r);

        return view('admin.finance.allotment-adjustments.index', [
            'entries' => AllotmentAdjustment::with(['fromBudget', 'toBudget'])->where('division_id', $r->user()->id)
                ->orderByDesc('source_time_stamp')->orderByDesc('id')->paginate(25),
        ]);
    }

    public function create(Request $r): View
    {
        $this->auth($r);

        return view('admin.finance.allotment-adjustments.form', ['adjustment' => null, 'budgetCodes' => BudgetCode::orderBy('budget_code')->get()]);
    }

    public function edit(Request $r, AllotmentAdjustment $allotmentAdjustment): View
    {
        $this->owner($r, $allotmentAdjustment);

        return view('admin.finance.allotment-adjustments.form', ['adjustment' => $allotmentAdjustment, 'budgetCodes' => BudgetCode::orderBy('budget_code')->get()]);
    }

    public function details(Request $r, BudgetCode $budgetCode): JsonResponse
    {
        $this->auth($r);

        return response()->json($this->detailsFor($r, $budgetCode, $r->integer('exclude') ?: null));
    }

    public function store(Request $r): RedirectResponse
    {
        $this->auth($r);
        $d = $this->valid($r);
        $this->available($r, $d);
        AllotmentAdjustment::create($d + [
            'division_id' => $r->user()->id, 'ledger_sr' => ((int) AllotmentAdjustment::where('division_id', $r->user()->id)->max('ledger_sr')) + 1,
            'source_time_stamp' => now(), 'entered_by' => $r->user()->email, 'times_edited' => 0,
        ]);

        return redirect()->route('division.allotment-adjustments.index')->with('status', 'Allotment adjustment saved successfully.');
    }

    public function update(Request $r, AllotmentAdjustment $allotmentAdjustment): RedirectResponse
    {
        $this->owner($r, $allotmentAdjustment);
        $d = $this->valid($r);
        $this->available($r, $d, $allotmentAdjustment->id);
        $allotmentAdjustment->update($d + ['times_edited' => ((int) $allotmentAdjustment->times_edited) + 1]);

        return redirect()->route('division.allotment-adjustments.index')->with('status', 'Allotment adjustment updated successfully.');
    }

    public function destroy(Request $r, AllotmentAdjustment $allotmentAdjustment): RedirectResponse
    {
        $this->owner($r, $allotmentAdjustment);
        $allotmentAdjustment->delete();

        return back()->with('status', 'Allotment adjustment deleted successfully.');
    }

    private function valid(Request $r): array
    {
        $exists = Rule::exists('budget_codes', 'id')->where('financial_year_id', CurrentFinancialYear::id());

        return $r->validate([
            'from_budget_code_id' => ['required', 'integer', $exists],
            'to_budget_code_id' => ['required', 'integer', 'different:from_budget_code_id', $exists],
            'amount' => ['required', 'numeric', 'gt:0'],
            'remark' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function available(Request $r, array $d, ?int $exclude = null): void
    {
        $pending = $this->detailsFor($r, BudgetCode::findOrFail($d['from_budget_code_id']), $exclude)['pending_allotment'];
        abort_if((float) $d['amount'] > (float) $pending + 0.005, 422, 'Transfer amount must be smaller than or equal to the pending allotment.');
    }

    private function detailsFor(Request $r, BudgetCode $b, ?int $exclude = null): array
    {
        $p = BudgetPosition::for($r->user()->id, $b->id, $exclude);
        abort_unless($p['circle_entry'], 404, 'No allotment from Circle is available for this budget code.');

        return ['id' => $b->id, 'budget_code' => $b->budget_code, 'scheme' => $b->scheme, 'class' => $b->object_class, 'model' => $b->model,
            'allotment_from_circle' => $p['circle'], 'adjusted_allotment' => $p['adjusted'], 'pending_allotment' => $p['pending']];
    }

    private function auth(Request $r): void
    {
        abort_unless($r->user()->isDivision(), 403);
    }

    private function owner(Request $r, AllotmentAdjustment $a): void
    {
        $this->auth($r);
        abort_unless($a->division_id === $r->user()->id, 403);
    }
}
