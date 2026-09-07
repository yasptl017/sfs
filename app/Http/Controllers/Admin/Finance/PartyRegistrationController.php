<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\PartyRegistration;
use App\Models\RangeLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class PartyRegistrationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isRange(), 403);

        return view('admin.finance.registration.parties.index', [
            'parties' => PartyRegistration::query()
                ->where('range_id', $request->user()->id)
                ->latest('serial_number')
                ->paginate(10),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->isRange(), 403);

        return view('admin.finance.registration.party', [
            'banks' => $this->banks(),
            'rounds' => $this->rounds($request),
            'nextSerial' => $this->nextSerial($request),
            'party' => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->isRange(), 403);

        $validated = $this->withAttachments($request, $this->validated($request));
        $validated['range_id'] = $request->user()->id;
        $validated['serial_number'] = $this->nextSerial($request);

        $party = PartyRegistration::query()->create($validated);

        return response()->json([
            'message' => 'Your data is submitted.',
            'party' => $party->toFormArray(),
            'next_serial' => $this->nextSerial($request),
        ]);
    }

    public function edit(Request $request, PartyRegistration $party): View
    {
        $this->authorizeRangeParty($request, $party);

        return view('admin.finance.registration.party', [
            'banks' => $this->banks(),
            'rounds' => $this->rounds($request),
            'nextSerial' => $party->serial_number,
            'party' => $party,
        ]);
    }

    public function update(Request $request, PartyRegistration $party): JsonResponse
    {
        $this->authorizeRangeParty($request, $party);

        $party->update($this->withAttachments($request, $this->validated($request), $party));

        return response()->json([
            'message' => 'Party details updated successfully.',
            'redirect_url' => route('finance.registration.parties.index'),
        ]);
    }

    public function toggleStatus(Request $request, PartyRegistration $party): RedirectResponse
    {
        $this->authorizeRangeParty($request, $party);

        $party->update([
            'party_status' => $party->party_status === 'Active' ? 'Deactive' : 'Active',
        ]);

        return back()->with('status', 'Party status updated successfully.');
    }

    public function destroy(Request $request, PartyRegistration $party): RedirectResponse
    {
        $this->authorizeRangeParty($request, $party);

        Storage::disk('public')->delete(array_filter([$party->approval_attachment, $party->contact_attachment]));
        $party->delete();

        return back()->with('status', 'Party deleted successfully.');
    }

    public function copy(Request $request, int $serialNumber): JsonResponse
    {
        abort_unless($request->user()->isRange(), 403);

        $party = PartyRegistration::query()
            ->where('range_id', $request->user()->id)
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
        return ((int) PartyRegistration::query()
            ->where('range_id', $request->user()->id)
            ->max('serial_number')) + 1;
    }

    private function authorizeRangeParty(Request $request, PartyRegistration $party): void
    {
        abort_unless($request->user()->isRange() && $party->range_id === $request->user()->id, 403);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'party_code' => ['required', 'string', 'max:255'],
            'round' => ['required', Rule::in($this->rounds($request))],
            'approved_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'small_description' => ['nullable', 'string', 'max:255'],
            'party_name' => ['required', 'string', 'max:255'],
            'pan_card_no' => ['nullable', 'string', 'max:255'],
            'gst_no' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['required', 'string', Rule::in($this->banks())],
            'account_no' => ['nullable', 'string', 'max:255'],
            'ifsc' => ['nullable', 'string', 'max:255'],
            'branch' => ['nullable', 'string', 'max:255'],
            'deduction_sgst' => ['nullable', 'numeric', 'min:0'],
            'deduction_cgst' => ['nullable', 'numeric', 'min:0'],
            'deduction_igst' => ['nullable', 'numeric', 'min:0'],
            'deduction_labour_cess' => ['nullable', 'numeric', 'min:0'],
            'deposit_deduction' => ['nullable', 'numeric', 'min:0'],
            'tds' => ['nullable', 'numeric', 'min:0'],
            'party_approval_no' => ['nullable', 'string', 'max:255'],
            'approval_attachment' => ['nullable', 'file', 'max:20480'],
            'party_aadhaar_no' => ['nullable', 'string', 'max:255'],
            'party_mobile_no' => ['nullable', 'string', 'max:255'],
            'party_email' => ['nullable', 'email', 'max:255'],
            'contact_attachment' => ['nullable', 'file', 'max:20480'],
            'link_of_doc' => ['nullable', 'url', 'max:255'],
            'party_address' => ['nullable', 'string'],
            'party_status' => ['required', Rule::in(['Active', 'Deactive'])],
        ]);

        return collect($validated)->map(fn ($value) => $value === '' ? null : $value)->all();
    }

    /** @return array<int, string> */
    private function rounds(Request $request): array
    {
        return RangeLocation::query()
            ->where('range_id', $request->user()->id)
            ->orderBy('round')
            ->pluck('round')
            ->unique()
            ->values()
            ->all();
    }

    /** @param array<string, mixed> $validated
     *  @return array<string, mixed>
     */
    private function withAttachments(Request $request, array $validated, ?PartyRegistration $party = null): array
    {
        foreach (['approval_attachment', 'contact_attachment'] as $field) {
            if (!$request->hasFile($field)) {
                unset($validated[$field]);
                continue;
            }

            if ($party?->{$field}) {
                Storage::disk('public')->delete($party->{$field});
            }
            $validated[$field] = $request->file($field)->store('party-attachments', 'public');
        }

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    private function banks(): array
    {
        return [
            'State Bank of India',
            'Bank of Baroda',
            'Punjab National Bank',
            'Saurashtra Gramin Bank',
            'Allahabad Bank',
            'Axis Bank',
            'Bank of India',
            'Canara Bank',
            'HDFC Bank',
            'ICICI Bank',
            'IDBI Bank',
            'Indian Overseas Bank',
            'Kotak Mahindra Bank',
            'The Co operative Bank of Rajkot',
            'Syndicate Bank',
            'The Saraswat Co operative Bank Ltd',
            'Corporation Bank',
            'Rajkot Nagarik Sahakari Bank Ltd',
            'Union Bank',
            'The Surendranagar Co operative Bank',
            'Indian Bank',
            'Adarsh Co operative Bank',
            'Central Bank of India',
            'Ahemdabad Dist Co operative Bank Ltd',
            'The Surendranagar District Co operative Bank Ltd',
            'BMCB',
            'Baroda Gujarat Gramin Bank',
            'Kukarwada Nagrik Sahkari Bank Ltd',
            'Yes Bank',
            'Union Bank of India',
            'DCB Bank',
            'Cosmos Bank',
            'IndusInd Bank',
            'The Kachchh District Central Co operative Bank Ltd',
            'South Indian Bank',
            'Standard Chartered Bank',
            'AU Small Finance Bank',
            'Federal Bank Limited',
            'Bharat Co operative Bank (Mumbai) Ltd',
            'The Mehsana Urban Co operative Bank Ltd',
            'United Bank of India',
            'Bandhan Bank',
            'Associate Co operative Bank Ltd.',
            'RBL Bank',
            'Party Cheque',
            'D.D.',
            'Bank of Maharashtra',
            'Karur Vysya Bank',
            'Gujarat State Co operative Bank',
            'The Mehsana District Central Co operative Bank Ltd.',
            'SBBP CO-OPERATIVE  BANK LTD.',
            'The Sarvodaya Sahakari Bank Ltd Modasa',
            'The S.k District Central Co operative Bank Ltd',
            'Punjab &  Sind Bank',
            'Himmatnagar Nagarik Sahakari Bank Ltd',
            'The Kalupur Commercial Co operative Bank Ltd',
            'Gujarat Naramda Valley Fertilizer and Chemicals Limited',
            'Gujarat Gramin Bank',
            'The Sarvoday Nagrik Sahkari Bank Ltd',
            'Janata Sahakari Bank Ltd',
            'UCO Bank',
        ];
    }
}

