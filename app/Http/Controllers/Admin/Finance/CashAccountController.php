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
                ->orderBy('range_name')->orderBy('serial_number')->orderBy('id')
                ->paginate(25),
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
        $d = $this->validatedData($request);

        CashAccount::create([
            'division_id' => $request->user()->id, 'range_id' => $request->user()->id, 'range_name' => 'Division',
            'serial_number' => ((int) CashAccount::where('division_id', $request->user()->id)->where('range_id', $request->user()->id)->max('serial_number')) + 1,
            'cash_account_month' => CashAccount::monthToText($d['cash_account_month']), 'designation' => $d['designation'], 'round_range' => $d['round_range'],
            'details_of_recovery' => $d['recovery_details'] ?? null, 'designation_of_recoverer' => $d['recoverer_designation'],
            'recovery_officer_name' => $d['recovery_officer_name'], 'recovery_type' => $d['recovery_type'], 'recovery_date' => $d['recovery_date'],
            'bank_deposit_date' => $d['bank_deposit_date'], 'bank_name_branch' => $d['bank_name_branch'], 'challan_no' => $d['challan_no'], 'amount' => $d['amount'],
        ]);

        return redirect()->route('division.cash-accounts.index')->with('status', 'Cash account entry submitted successfully.');
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
