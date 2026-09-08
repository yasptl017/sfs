<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BudgetCode;
use App\Models\RangeLocation;
use App\Models\TenderEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenderEntryController extends Controller
{
    public function index(Request $request): View
    {
        $this->auth($request);

        return view('admin.finance.tender-entries.index', [
            'entries' => TenderEntry::where('range_id', $request->user()->id)
                ->latest('serial_number')
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $this->auth($request);

        return view('admin.finance.tender-entries.form', array_merge([
            'entry' => null,
            'next' => $this->nextSerial($request),
        ], $this->sharedData($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->auth($request);

        $validated = $request->validate(['data' => ['required', 'array']]);

        $entry = TenderEntry::create([
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

    public function edit(Request $request, TenderEntry $tenderEntry): View
    {
        $this->auth($request);
        abort_unless($tenderEntry->range_id === $request->user()->id, 403);

        return view('admin.finance.tender-entries.form', array_merge([
            'entry' => $tenderEntry,
            'next' => $tenderEntry->serial_number,
        ], $this->sharedData($request)));
    }

    public function update(Request $request, TenderEntry $tenderEntry): JsonResponse
    {
        $this->auth($request);
        abort_unless($tenderEntry->range_id === $request->user()->id, 403);

        $validated = $request->validate(['data' => ['required', 'array']]);

        $tenderEntry->update(['data' => $validated['data']]);

        return response()->json([
            'message' => 'Tender entry updated successfully.',
            'redirect_url' => route('finance.tender-entries.index'),
        ]);
    }

    public function destroy(Request $request, TenderEntry $tenderEntry): RedirectResponse
    {
        $this->auth($request);
        abort_unless($tenderEntry->range_id === $request->user()->id, 403);

        $tenderEntry->delete();

        return back()->with('status', 'Tender entry deleted successfully.');
    }

    public function copy(Request $request, int $serialNumber): JsonResponse
    {
        $this->auth($request);

        $entry = TenderEntry::query()
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
        return ((int) TenderEntry::where('range_id', $request->user()->id)->max('serial_number')) + 1;
    }

    /** @return array<string, mixed> */
    private function sharedData(Request $request): array
    {
        $locations = RangeLocation::query()->where('range_id', $request->user()->id)->get();

        return [
            'rounds' => $locations->pluck('round')->unique()->sort()->values()->all(),
            'beatsByRound' => $locations->groupBy('round')
                ->map(fn ($group) => $group->pluck('beat')->unique()->sort()->values()->all())
                ->all(),
            'placesByBeat' => $locations->groupBy('beat')
                ->map(fn ($group) => $group->pluck('place')->unique()->sort()->values()->all())
                ->all(),
            'budgetCodes' => BudgetCode::query()->orderBy('budget_code')->get(),
        ];
    }

    private function auth(Request $request): void
    {
        abort_unless($request->user()->isRange(), 403);
    }
}
