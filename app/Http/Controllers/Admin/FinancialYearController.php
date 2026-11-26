<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialYear;
use App\Support\CurrentFinancialYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FinancialYearController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $data = $request->validate(['financial_year_id' => ['required', 'integer', 'exists:financial_years,id']]);
        $request->session()->put('financial_year_id', (int) $data['financial_year_id']);
        CurrentFinancialYear::forget();

        return back()->with('status', 'Working in financial year '.FinancialYear::find($data['financial_year_id'])->code.'.');
    }
}
