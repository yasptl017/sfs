<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\CashAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashAccountController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        return view('admin.finance.cash-accounts.index', [
            'entries' => CashAccount::query()
                ->where('division_id', $request->user()->id)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeDivision($request);

        return view('admin.finance.cash-accounts.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDivision($request);

        CashAccount::create([
            'division_id' => $request->user()->id,
            'data' => $this->validatedData($request),
        ]);

        return redirect()->route('division.cash-accounts.index')
            ->with('status', 'Cash account entry submitted successfully.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'cash_account_month' => ['required', 'date_format:Y-m'],
            'designation' => ['required', 'string', 'max:50'],
            'round_range' => ['required', 'string', 'max:100'],
            'recovery_details' => ['nullable', 'string', 'max:1000'],
            'recoverer_designation' => ['required', 'string', 'max:100'],
            'recovery_officer_name' => ['required', 'string', 'max:150'],
            'recovery_type' => ['required', 'string', 'max:255'],
            'recovery_date' => ['required', 'date'],
            'bank_deposit_date' => ['required', 'date'],
            'bank_name_branch' => ['required', 'string', 'max:255'],
            'challan_no' => ['required', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()->isDivision(), 403);
    }
}
