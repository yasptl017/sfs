<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BudgetCode;
use App\Models\RangeLocation;
use App\Models\WlBeneficiary;
use App\Models\WlBeneficiaryEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WlBeneficiaryEntryController extends Controller
{
    public function index(Request $request): View
    {
        $this->auth($request);

        return view('admin.finance.wl-beneficiary-entries.index', [
            'entries' => WlBeneficiaryEntry::where('range_id', $request->user()->id)
                ->latest('serial_number')
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $this->auth($request);

        return view('admin.finance.wl-beneficiary-entries.form', array_merge([
            'entry' => null,
            'next' => $this->nextSerial($request),
        ], $this->sharedData($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->auth($request);

        $validated = $request->validate(['data' => ['required', 'array']]);

        $entry = WlBeneficiaryEntry::create([
            'range_id' => $request->user()->id,
            'serial_number' => $this->nextSerial($request),
            'data' => $validated['data'],
        ]);

        return response()->json([
            'message' => 'Your data is submitted.',
            'entry' => $entry->toFormArray(),
            'next_serial' => $this->nextSerial($request),
        ]);
    }

    public function edit(Request $request, WlBeneficiaryEntry $wlBeneficiaryEntry): View
    {
        $this->auth($request);
        abort_unless($wlBeneficiaryEntry->range_id === $request->user()->id, 403);

        return view('admin.finance.wl-beneficiary-entries.form', array_merge([
            'entry' => $wlBeneficiaryEntry,
            'next' => $wlBeneficiaryEntry->serial_number,
        ], $this->sharedData($request)));
    }

    public function update(Request $request, WlBeneficiaryEntry $wlBeneficiaryEntry): JsonResponse
    {
        $this->auth($request);
        abort_unless($wlBeneficiaryEntry->range_id === $request->user()->id, 403);

        $validated = $request->validate(['data' => ['required', 'array']]);

        $wlBeneficiaryEntry->update(['data' => $validated['data']]);

        return response()->json([
            'message' => 'WL beneficiary entry updated successfully.',
            'redirect_url' => route('finance.wl-beneficiary-entries.index'),
        ]);
    }

    public function destroy(Request $request, WlBeneficiaryEntry $wlBeneficiaryEntry): RedirectResponse
    {
        $this->auth($request);
        abort_unless($wlBeneficiaryEntry->range_id === $request->user()->id, 403);

        $wlBeneficiaryEntry->delete();

        return back()->with('status', 'WL beneficiary entry deleted successfully.');
    }

    public function copy(Request $request, int $serialNumber): JsonResponse
    {
        $this->auth($request);

        $entry = WlBeneficiaryEntry::query()
            ->where('range_id', $request->user()->id)
            ->where('serial_number', $serialNumber)
            ->firstOrFail();

        $data = $entry->toFormArray();
        $data['entry_sr_no'] = $this->nextSerial($request);

        return response()->json([
            'entry' => $data,
            'next_serial' => $data['entry_sr_no'],
        ]);
    }

    private function nextSerial(Request $request): int
    {
        return ((int) WlBeneficiaryEntry::where('range_id', $request->user()->id)->max('serial_number')) + 1;
    }

    /** @return array<string, mixed> */
    private function sharedData(Request $request): array
    {
        $locations = RangeLocation::query()->where('range_id', $request->user()->id)->get();

        $beneficiaries = WlBeneficiary::query()
            ->where('range_id', $request->user()->id)
            ->where('party_status', 'Active')
            ->get();

        return [
            'rounds' => $locations->pluck('round')->unique()->sort()->values()->all(),
            'beatsByRound' => $locations->groupBy('round')
                ->map(fn ($group) => $group->pluck('beat')->unique()->sort()->values()->all())
                ->all(),
            'beneficiariesByRoundBeat' => $beneficiaries->groupBy(fn (WlBeneficiary $b) => $b->round.'|'.$b->beat)
                ->map(fn ($group) => $group->pluck('wl_bene_code')->unique()->sort()->values()->all())
                ->all(),
            'beneficiaryDetails' => $beneficiaries->mapWithKeys(fn (WlBeneficiary $b) => [$b->wl_bene_code => [
                'full_name' => $b->full_name,
                'name_gujarati' => $b->name_gujarati,
                'mobile_no' => $b->mobile_no,
                'gender' => $b->gender,
                'address' => $b->address,
                'pin_code' => $b->pin_code,
                'gps' => $b->gps,
                'id_type' => $b->id_type,
                'id_no' => $b->id_no,
            ]]),
            'budgetCodes' => BudgetCode::query()->orderBy('budget_code')->get(),
            'yojanas' => ['Killing by Wild Animal', 'Open Well', 'Machan'],
        ];
    }

    private function auth(Request $request): void
    {
        abort_unless($request->user()->isRange(), 403);
    }
}
