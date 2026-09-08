<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BudgetCode;
use App\Models\RangeLocation;
use App\Models\SfBeneficiary;
use App\Models\SfBeneficiaryEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SfBeneficiaryEntryController extends Controller
{
    public function index(Request $request): View
    {
        $this->auth($request);

        return view('admin.finance.sf-beneficiary-entries.index', [
            'entries' => SfBeneficiaryEntry::where('range_id', $request->user()->id)
                ->latest('serial_number')
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $this->auth($request);

        return view('admin.finance.sf-beneficiary-entries.form', array_merge([
            'entry' => null,
            'next' => $this->nextSerial($request),
        ], $this->sharedData($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->auth($request);

        $validated = $request->validate(['data' => ['required', 'array']]);

        $entry = SfBeneficiaryEntry::create([
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

    public function edit(Request $request, SfBeneficiaryEntry $sfBeneficiaryEntry): View
    {
        $this->auth($request);
        abort_unless($sfBeneficiaryEntry->range_id === $request->user()->id, 403);

        return view('admin.finance.sf-beneficiary-entries.form', array_merge([
            'entry' => $sfBeneficiaryEntry,
            'next' => $sfBeneficiaryEntry->serial_number,
        ], $this->sharedData($request)));
    }

    public function update(Request $request, SfBeneficiaryEntry $sfBeneficiaryEntry): JsonResponse
    {
        $this->auth($request);
        abort_unless($sfBeneficiaryEntry->range_id === $request->user()->id, 403);

        $validated = $request->validate(['data' => ['required', 'array']]);

        $sfBeneficiaryEntry->update(['data' => $validated['data']]);

        return response()->json([
            'message' => 'SF beneficiary entry updated successfully.',
            'redirect_url' => route('finance.sf-beneficiary-entries.index'),
        ]);
    }

    public function destroy(Request $request, SfBeneficiaryEntry $sfBeneficiaryEntry): RedirectResponse
    {
        $this->auth($request);
        abort_unless($sfBeneficiaryEntry->range_id === $request->user()->id, 403);

        $sfBeneficiaryEntry->delete();

        return back()->with('status', 'SF beneficiary entry deleted successfully.');
    }

    public function copy(Request $request, int $serialNumber): JsonResponse
    {
        $this->auth($request);

        $entry = SfBeneficiaryEntry::query()
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
        return ((int) SfBeneficiaryEntry::where('range_id', $request->user()->id)->max('serial_number')) + 1;
    }

    /** @return array<string, mixed> */
    private function sharedData(Request $request): array
    {
        $locations = RangeLocation::query()->where('range_id', $request->user()->id)->get();

        $beneficiaries = SfBeneficiary::query()
            ->where('range_id', $request->user()->id)
            ->where('party_status', 'Active')
            ->get();

        return [
            'rounds' => $locations->pluck('round')->unique()->sort()->values()->all(),
            'beatsByRound' => $locations->groupBy('round')
                ->map(fn ($group) => $group->pluck('beat')->unique()->sort()->values()->all())
                ->all(),
            'beneficiariesByRoundBeat' => $beneficiaries->groupBy(fn (SfBeneficiary $b) => $b->round.'|'.$b->beat)
                ->map(fn ($group) => $group->pluck('sf_bene_code')->unique()->sort()->values()->all())
                ->all(),
            'beneficiaryDetails' => $beneficiaries->mapWithKeys(fn (SfBeneficiary $b) => [$b->sf_bene_code => [
                'full_name' => $b->full_name,
                'name_gujarati' => $b->name_gujarati,
                'mobile_no' => $b->mobile_no,
                'gender' => $b->gender,
                'father_name' => $b->father_name,
                'birth_date' => optional($b->birth_date)->format('Y-m-d'),
                'address' => $b->address,
                'pin_code' => $b->pin_code,
                'account_no' => $b->account_no,
                'bank_name' => $b->bank_name,
                'ifsc' => $b->ifsc,
                'branch' => $b->branch,
                'gps' => $b->gps,
                'id_type' => $b->id_type,
                'id_no' => $b->id_no,
                'hactor_dcp_plants' => $b->hactor_dcp_plants,
            ]]),
            'budgetCodes' => BudgetCode::query()->orderBy('budget_code')->get(),
            'yojanas' => $this->yojanas(),
            'years' => $this->years(),
        ];
    }

    /** @return array<int, string> */
    private function yojanas(): array
    {
        return [
            'DCP Nursery- SCP (SHG)', 'Agro Forestry Yojana', 'Agro Forestry Yojana (SCP)', 'Agro Forestry Yojana (Trible)',
            'DCP Nursery', 'DCP Nursery (SCP)', 'DCP Nursery (Trible)', 'RDFL', 'RDFL (SCP)', 'RDFL (Trible)',
            'VruxKheti', 'VruxKheti (SCP)', 'VruxKheti (Trible)', 'National Bamboo Mission',
            'National Bamboo Mission (SCP)', 'National Bamboo Mission (Trible)',
        ];
    }

    /** @return array<int, string> */
    private function years(): array
    {
        $year = (int) now()->format('Y');
        $start = (int) now()->format('n') < 4 ? $year - 1 : $year;

        return collect(range($start - 30, $start + 30))
            ->map(fn (int $v) => sprintf('%d-%02d', $v, ($v + 1) % 100))
            ->sortDesc()
            ->values()
            ->all();
    }

    private function auth(Request $request): void
    {
        abort_unless($request->user()->isRange(), 403);
    }
}
