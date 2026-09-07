<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\DivisionTenderParty;
use App\Models\RangeLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DivisionTenderPartyController extends Controller
{
    public function index(Request $request): View
    {
        $this->auth($request);

        return view('admin.finance.division-parties.index', [
            'parties' => DivisionTenderParty::where('division_id', $request->user()->id)
                ->latest('serial_number')
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $this->auth($request);

        return view('admin.finance.division-parties.form', [
            'party' => null,
            'next' => $this->nextSerial($request),
            'ranges' => $this->ranges($request),
            'roundsByRange' => $this->roundsByRange($request),
            'existingTenderNos' => $this->existingTenderNos($request),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->auth($request);

        $validated = $request->validate([
            'data' => ['required', 'array'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
            'party_status' => ['required'],
        ]);

        $files = collect($request->file('attachments', []))
            ->map(fn ($file) => $file->store('division-party-attachments', 'public'))
            ->all();

        $party = DivisionTenderParty::create([
            'division_id' => $request->user()->id,
            'serial_number' => $this->nextSerial($request),
            'data' => $validated['data'],
            'attachments' => $files,
            'party_status' => $validated['party_status'],
        ]);

        return response()->json([
            'message' => 'Your data is submitted.',
            'party' => $party->toFormArray(),
            'next_serial' => $this->nextSerial($request),
        ]);
    }

    public function edit(Request $request, DivisionTenderParty $party): View
    {
        $this->auth($request);
        abort_unless($party->division_id === $request->user()->id, 403);

        return view('admin.finance.division-parties.form', [
            'party' => $party,
            'next' => $party->serial_number,
            'ranges' => $this->ranges($request),
            'roundsByRange' => $this->roundsByRange($request),
            'existingTenderNos' => $this->existingTenderNos($request),
        ]);
    }

    public function update(Request $request, DivisionTenderParty $party): JsonResponse
    {
        $this->auth($request);
        abort_unless($party->division_id === $request->user()->id, 403);

        $validated = $request->validate([
            'data' => ['required', 'array'],
            'party_status' => ['required'],
        ]);

        $party->update([
            'data' => $validated['data'],
            'party_status' => $validated['party_status'],
        ]);

        return response()->json([
            'message' => 'Party details updated successfully.',
            'redirect_url' => route('division.parties.index'),
        ]);
    }

    public function toggleStatus(Request $request, DivisionTenderParty $party): RedirectResponse
    {
        $this->auth($request);
        abort_unless($party->division_id === $request->user()->id, 403);

        $party->update([
            'party_status' => $party->party_status === 'Active' ? 'Deactive' : 'Active',
        ]);

        return back()->with('status', 'Party status updated successfully.');
    }

    public function destroy(Request $request, DivisionTenderParty $party): RedirectResponse
    {
        $this->auth($request);
        abort_unless($party->division_id === $request->user()->id, 403);

        Storage::disk('public')->delete($party->attachments ?? []);
        $party->delete();

        return back()->with('status', 'Party deleted successfully.');
    }

    public function copy(Request $request, int $serialNumber): JsonResponse
    {
        $this->auth($request);

        $party = DivisionTenderParty::query()
            ->where('division_id', $request->user()->id)
            ->where('serial_number', $serialNumber)
            ->firstOrFail();

        $data = $party->toFormArray();
        $data['party_sr_no'] = $this->nextSerial($request);

        return response()->json([
            'party' => $data,
            'next_serial' => $data['party_sr_no'],
        ]);
    }

    private function nextSerial(Request $request): int
    {
        return ((int) DivisionTenderParty::where('division_id', $request->user()->id)->max('serial_number')) + 1;
    }

    /** @return array<int, string> */
    private function ranges(Request $request): array
    {
        return $request->user()->ranges()->orderBy('name')->pluck('name')->all();
    }

    /** @return array<string, array<int, string>> */
    private function roundsByRange(Request $request): array
    {
        $rangeIds = $request->user()->ranges()->pluck('id', 'name');

        return RangeLocation::query()
            ->whereIn('range_id', $rangeIds->values())
            ->get()
            ->groupBy(fn (RangeLocation $location) => $rangeIds->search($location->range_id))
            ->map(fn ($locations) => $locations->pluck('round')->unique()->sort()->values()->all())
            ->all();
    }

    /** @return array<int, string> */
    private function existingTenderNos(Request $request): array
    {
        return DivisionTenderParty::query()
            ->where('division_id', $request->user()->id)
            ->get()
            ->pluck('data.tender_no')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function auth(Request $request): void
    {
        abort_unless($request->user()->isDivision(), 403);
    }
}
