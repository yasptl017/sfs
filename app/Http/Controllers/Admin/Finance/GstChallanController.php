<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\FreeEntry;
use App\Models\GstChallan;
use App\Models\TenderEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GstChallanController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        $divisionId = $request->user()->id;

        // Retrieve existing records for this division
        $records = GstChallan::query()
            ->where('division_id', $divisionId)
            ->latest()
            ->paginate(15);

        $availableData = $this->getAvailableBillAndAdviceData($request->user());

        return view('admin.finance.gst-challan.index', [
            'records' => $records,
            'billRegisterNumbers' => $availableData['billRegisterNumbers'],
            'adviceNumbers' => $availableData['adviceNumbers'],
            'billAdviceMap' => $availableData['billAdviceMap'],
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeDivision($request);

        $validated = $request->validate([
            'bill_register_no' => ['required', 'string', 'max:100'],
            'advice_no' => ['required', 'string', 'max:100'],
            'gst_amount' => ['required', 'numeric', 'min:0'],
            'gst_challan_no' => ['required', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $challan = GstChallan::updateOrCreate(
            [
                'division_id' => $request->user()->id,
                'bill_register_no' => $validated['bill_register_no'],
                'advice_no' => $validated['advice_no'],
            ],
            [
                'gst_amount' => $validated['gst_amount'],
                'gst_challan_no' => $validated['gst_challan_no'],
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'GST Challan No. updated successfully.',
                'data' => $challan,
            ]);
        }

        return redirect()->route('division.gst-challan.index')
            ->with('status', 'GST Challan No. updated successfully.');
    }

    public function details(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $billRegisterNo = $request->query('bill_register_no');
        $adviceNo = $request->query('advice_no');

        if (!$billRegisterNo && !$adviceNo) {
            return response()->json([
                'gst_amount' => 0.00,
                'gst_challan_no' => '',
            ]);
        }

        $existing = GstChallan::query()
            ->where('division_id', $request->user()->id)
            ->when($billRegisterNo, fn ($q) => $q->where('bill_register_no', $billRegisterNo))
            ->when($adviceNo, fn ($q) => $q->where('advice_no', $adviceNo))
            ->first();

        if ($existing) {
            return response()->json([
                'gst_amount' => (float) $existing->gst_amount,
                'gst_challan_no' => $existing->gst_challan_no,
                'is_existing' => true,
            ]);
        }

        // Calculate GST amount from voucher entries if available
        $rangeIds = User::query()
            ->where('role', 'range')
            ->pluck('id')
            ->all();

        $calculatedGst = 0.00;

        $tenderEntries = TenderEntry::query()
            ->whereIn('range_id', $rangeIds)
            ->get();

        foreach ($tenderEntries as $entry) {
            $data = $entry->data ?? [];
            $entryBillNo = $data['bill_no'] ?? null;
            $entryDocket = $data['docket_no'] ?? null;

            if (($billRegisterNo && $entryBillNo == $billRegisterNo) || ($adviceNo && $entryDocket == $adviceNo)) {
                $sgst = (float) ($data['deduction_sgst'] ?? $data['additional_sgst'] ?? 0);
                $cgst = (float) ($data['deduction_cgst'] ?? $data['additional_cgst'] ?? 0);
                $igst = (float) ($data['deduction_igst'] ?? $data['additional_igst'] ?? 0);
                $calculatedGst += ($sgst + $cgst + $igst);
            }
        }

        return response()->json([
            'gst_amount' => round($calculatedGst, 2),
            'gst_challan_no' => '',
            'is_existing' => false,
        ]);
    }

    public function destroy(Request $request, GstChallan $gstChallan): RedirectResponse
    {
        $this->authorizeDivision($request);
        abort_unless($gstChallan->division_id === $request->user()->id, 403);

        $gstChallan->delete();

        return redirect()->route('division.gst-challan.index')
            ->with('status', 'GST Challan record deleted successfully.');
    }

    private function getAvailableBillAndAdviceData(User $divisionUser): array
    {
        $rangeIds = User::query()
            ->where('role', 'range')
            ->pluck('id')
            ->all();

        $billNos = collect();
        $adviceNos = collect();
        $billAdviceMap = [];

        // Fetch from existing GstChallan entries
        $saved = GstChallan::query()
            ->where('division_id', $divisionUser->id)
            ->get();

        foreach ($saved as $record) {
            if ($record->bill_register_no) {
                $billNos->push($record->bill_register_no);
            }
            if ($record->advice_no) {
                $adviceNos->push($record->advice_no);
            }
            $billAdviceMap[$record->bill_register_no][] = [
                'advice_no' => $record->advice_no,
                'gst_amount' => (float) $record->gst_amount,
                'gst_challan_no' => $record->gst_challan_no,
            ];
        }

        // Fetch from TenderEntry & FreeEntry
        $tenderEntries = TenderEntry::query()->whereIn('range_id', $rangeIds)->get();
        foreach ($tenderEntries as $entry) {
            $data = $entry->data ?? [];
            $bill = $data['bill_no'] ?? null;
            $advice = $data['docket_no'] ?? ('ADV-' . $entry->serial_number);
            $gst = (float) ($data['deduction_sgst'] ?? 0) + (float) ($data['deduction_cgst'] ?? 0) + (float) ($data['deduction_igst'] ?? 0);

            if ($bill) {
                $billNos->push((string) $bill);
                $adviceNos->push((string) $advice);
                $billAdviceMap[$bill][] = [
                    'advice_no' => (string) $advice,
                    'gst_amount' => $gst,
                    'gst_challan_no' => '',
                ];
            }
        }

        $freeEntries = FreeEntry::query()->whereIn('range_id', $rangeIds)->get();
        foreach ($freeEntries as $entry) {
            $data = $entry->data ?? [];
            $bill = $data['bill_no'] ?? null;
            $advice = $data['docket_no'] ?? ('ADV-F-' . $entry->serial_number);
            $gst = (float) ($data['deduction_sgst'] ?? 0) + (float) ($data['deduction_cgst'] ?? 0) + (float) ($data['deduction_igst'] ?? 0);

            if ($bill) {
                $billNos->push((string) $bill);
                $adviceNos->push((string) $advice);
                $billAdviceMap[$bill][] = [
                    'advice_no' => (string) $advice,
                    'gst_amount' => $gst,
                    'gst_challan_no' => '',
                ];
            }
        }

        // Add standard sample/starter bill and advice numbers if list is empty
        if ($billNos->isEmpty()) {
            $billNos = collect(['BR/2026-27/001', 'BR/2026-27/002', 'BR/2026-27/003', 'BR/2026-27/004', 'BR/2026-27/005']);
            $adviceNos = collect(['ADV/2026/101', 'ADV/2026/102', 'ADV/2026/103', 'ADV/2026/104', 'ADV/2026/105']);
            
            $billAdviceMap['BR/2026-27/001'] = [['advice_no' => 'ADV/2026/101', 'gst_amount' => 1850.00, 'gst_challan_no' => '']];
            $billAdviceMap['BR/2026-27/002'] = [['advice_no' => 'ADV/2026/102', 'gst_amount' => 3420.50, 'gst_challan_no' => '']];
            $billAdviceMap['BR/2026-27/003'] = [['advice_no' => 'ADV/2026/103', 'gst_amount' => 5200.00, 'gst_challan_no' => '']];
            $billAdviceMap['BR/2026-27/004'] = [['advice_no' => 'ADV/2026/104', 'gst_amount' => 970.00, 'gst_challan_no' => '']];
            $billAdviceMap['BR/2026-27/005'] = [['advice_no' => 'ADV/2026/105', 'gst_amount' => 4630.00, 'gst_challan_no' => '']];
        }

        return [
            'billRegisterNumbers' => $billNos->unique()->filter()->values()->all(),
            'adviceNumbers' => $adviceNos->unique()->filter()->values()->all(),
            'billAdviceMap' => $billAdviceMap,
        ];
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()->isDivision(), 403);
    }
}
