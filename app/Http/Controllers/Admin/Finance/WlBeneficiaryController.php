<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\RangeLocation;
use App\Models\WlBeneficiary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class WlBeneficiaryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeRange($request);

        return view('admin.finance.registration.wl-beneficiaries.index', [
            'beneficiaries' => WlBeneficiary::query()->where('range_id', $request->user()->id)->latest('serial_number')->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeRange($request);
        $copy = $request->filled('copy') ? WlBeneficiary::query()->where('range_id', $request->user()->id)->where('serial_number', $request->integer('copy'))->first() : null;

        return view('admin.finance.registration.wl-beneficiaries.form', [
            'beneficiary' => $copy,
            'isCopy' => (bool) $copy,
            'nextSerial' => $this->nextSerial($request),
            'rounds' => $this->rounds($request),
            'selectionYears' => $this->selectionYears(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRange($request);
        $data = $this->withAttachments($request, $this->validated($request));
        $data['range_id'] = $request->user()->id;
        $data['serial_number'] = $this->nextSerial($request);
        WlBeneficiary::query()->create($data);

        return redirect()->route('finance.registration.wl-beneficiaries.index')->with('status', 'Your data is submitted.');
    }

    public function edit(Request $request, WlBeneficiary $beneficiary): View
    {
        $this->authorizeBeneficiary($request, $beneficiary);

        return view('admin.finance.registration.wl-beneficiaries.form', [
            'beneficiary' => $beneficiary,
            'isCopy' => false,
            'nextSerial' => $beneficiary->serial_number,
            'rounds' => $this->rounds($request),
            'selectionYears' => $this->selectionYears(),
        ]);
    }

    public function update(Request $request, WlBeneficiary $beneficiary): RedirectResponse
    {
        $this->authorizeBeneficiary($request, $beneficiary);
        $beneficiary->update($this->withAttachments($request, $this->validated($request), $beneficiary));

        return redirect()->route('finance.registration.wl-beneficiaries.index')->with('status', 'WL beneficiary updated successfully.');
    }

    public function toggleStatus(Request $request, WlBeneficiary $beneficiary): RedirectResponse
    {
        $this->authorizeBeneficiary($request, $beneficiary);
        $beneficiary->update(['party_status' => $beneficiary->party_status === 'Active' ? 'Deactive' : 'Active']);

        return back()->with('status', 'Beneficiary status updated successfully.');
    }

    public function destroy(Request $request, WlBeneficiary $beneficiary): RedirectResponse
    {
        $this->authorizeBeneficiary($request, $beneficiary);
        Storage::disk('public')->delete($beneficiary->attachments ?? []);
        $beneficiary->delete();

        return back()->with('status', 'WL beneficiary deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'wl_bene_code' => ['required', 'string', 'max:255'],
            'round' => ['required', Rule::in(array_keys($this->rounds($request)))],
            'beat' => ['required', 'string', 'max:255'],
            'yojana_name' => ['required', Rule::in(['Killing by Wild Animal', 'Open Well', 'Machan'])],
            'full_name' => ['required', 'string', 'max:255'],
            'name_gujarati' => ['nullable', 'string', 'max:255'],
            'mobile_no' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', Rule::in(['Male', 'Female'])],
            'address' => ['nullable', 'string'],
            'pin_code' => ['nullable', 'string', 'max:10'],
            'category' => ['nullable', Rule::in(['Open', 'SEBC', 'SC', 'ST'])],
            'taluka' => ['nullable', 'string', 'max:255'],
            'district' => ['nullable', 'string', 'max:255'],
            'village' => ['nullable', 'string', 'max:255'],
            'survey_block_no' => ['nullable', 'string', 'max:255'],
            'id_type' => ['nullable', Rule::in(['Aadhar Card', 'Pan Card', 'Voter ID', 'Licence'])],
            'id_no' => ['nullable', 'string', 'max:255'],
            'gps_n' => ['nullable', 'string', 'max:255'],
            'gps_e' => ['nullable', 'string', 'max:255'],
            'gps' => ['nullable', 'string', 'max:255'],
            'selection_year' => ['nullable', Rule::in($this->selectionYears())],
            'remark' => ['nullable', 'string'],
            'link_of_doc' => ['nullable', 'url', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
            'party_status' => ['required', Rule::in(['Active', 'Deactive'])],
        ]);
    }
    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private function withAttachments(Request $request, array $data, ?WlBeneficiary $beneficiary = null): array
    {
        if (!$request->hasFile('attachments')) {
            unset($data['attachments']);
            return $data;
        }
        $existing = $beneficiary?->attachments ?? [];
        $newFiles = collect($request->file('attachments'))->map(fn ($file) => $file->store('wl-beneficiary-attachments', 'public'))->all();
        $data['attachments'] = array_values(array_merge($existing, $newFiles));
        return $data;
    }


    /** @return array<string, array<int, string>> */
    private function rounds(Request $request): array
    {
        return RangeLocation::query()->where('range_id', $request->user()->id)->orderBy('round')->get()->groupBy('round')->map(fn ($locations) => $locations->pluck('beat')->unique()->values()->all())->all();
    }

    /** @return array<int, string> */
    private function selectionYears(): array
    {
        $year = (int) now()->format('Y');
        $start = (int) now()->format('n') < 4 ? $year - 1 : $year;
        return collect(range($start - 30, $start + 30))->map(fn (int $value) => sprintf('%d-%02d', $value, ($value + 1) % 100))->sortDesc()->values()->all();
    }

    private function nextSerial(Request $request): int
    {
        return ((int) WlBeneficiary::query()->where('range_id', $request->user()->id)->max('serial_number')) + 1;
    }

    private function authorizeRange(Request $request): void
    {
        abort_unless($request->user()->isRange(), 403);
    }

    private function authorizeBeneficiary(Request $request, WlBeneficiary $beneficiary): void
    {
        abort_unless($request->user()->isRange() && $beneficiary->range_id === $request->user()->id, 403);
    }
}