<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BudgetCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BudgetCodeController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.budget-codes.index', [
            'budgetCodes' => BudgetCode::query()->latest()->paginate(15),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.budget-codes.form', ['budgetCode' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        BudgetCode::query()->create($this->validated($request));

        return redirect()->route('admin.budget-codes.index')->with('status', 'Budget code created successfully.');
    }

    public function edit(Request $request, BudgetCode $budgetCode): View
    {
        $this->authorizeAdmin($request);

        return view('admin.budget-codes.form', ['budgetCode' => $budgetCode]);
    }

    public function update(Request $request, BudgetCode $budgetCode): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $budgetCode->update($this->validated($request, $budgetCode));

        return redirect()->route('admin.budget-codes.index')->with('status', 'Budget code updated successfully.');
    }

    public function destroy(Request $request, BudgetCode $budgetCode): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $budgetCode->delete();

        return back()->with('status', 'Budget code deleted successfully.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?BudgetCode $budgetCode = null): array
    {
        return $request->validate([
            'budget_code' => ['required', 'string', 'max:255', Rule::unique('budget_codes')->ignore($budgetCode)],
            'operating_head' => ['nullable', 'string', 'max:255'],
            'scheme' => ['nullable', 'string', 'max:255'],
            'object_class' => ['nullable', 'string', 'max:255'],
            'object_code' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'item' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'scheme_year' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
