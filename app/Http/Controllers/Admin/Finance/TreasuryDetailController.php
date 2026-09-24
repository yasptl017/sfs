<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BillAdvice;
use App\Models\GstChallan;
use App\Models\TenderEntry;
use App\Models\TreasuryDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreasuryDetailController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        $divisionId = $request->user()->id;

        $records = TreasuryDetail::query()
            ->where('division_id', $divisionId)
            ->latest()
            ->paginate(15);

        $availableData = $this->getAvailableBillAndAdviceData($request->user());

        return view('admin.finance.treasury-details.index', [
            'records' => $records,
            'billNumbers' => $availableData['billNumbers'],
            'adviceNumbers' => $availableData['adviceNumbers'],
            'billAdviceMap' => $availableData['billAdviceMap'],
            'billTypes' => ['Contingency', 'Simple Receipt', 'SNA', 'IFMS'],
        ]);
    }

    public function info(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $billNo = $request->query('bill_no');
        $adviceNo = $request->query('advice_no');

        if (!$billNo && !$adviceNo) {
            return response()->json([
                'advice_date' => date('Y-m-d'),
                'bill_type' => 'Contingency',
                'bill_amount' => 0.00,
                'treasury_name' => '',
                'token_no' => '',
                'token_date' => '',
                'tv_no' => '',
                'tv_date' => '',
                'status' => 'Submitted',
                'is_existing' => false,
            ]);
        }

        // 1. Check existing saved treasury details
        $existing = TreasuryDetail::query()
            ->where('division_id', $request->user()->id)
            ->when($billNo, fn ($q) => $q->where('bill_no', $billNo))
            ->when($adviceNo, fn ($q) => $q->where('advice_no', $adviceNo))
            ->first();

        if ($existing) {
            return response()->json([
                'advice_date' => $existing->advice_date?->format('Y-m-d') ?? date('Y-m-d'),
                'bill_type' => $existing->bill_type ?? 'Contingency',
                'bill_amount' => (float) $existing->bill_amount,
                'treasury_name' => $existing->treasury_name ?? '',
                'token_no' => $existing->token_no ?? '',
                'token_date' => $existing->token_date?->format('Y-m-d') ?? '',
                'tv_no' => $existing->tv_no ?? '',
                'tv_date' => $existing->tv_date?->format('Y-m-d') ?? '',
                'status' => $existing->status ?? 'Submitted',
                'remarks' => $existing->remarks ?? '',
                'is_existing' => true,
            ]);
        }

        // 2. Lookup from BillAdvice model
        $billAdvice = BillAdvice::query()
            ->where('division_id', $request->user()->id)
            ->when($adviceNo, fn ($q) => $q->where('advice_no', $adviceNo))
            ->first();

        if ($billAdvice) {
            return response()->json([
                'advice_date' => $billAdvice->advice_date->format('Y-m-d'),
                'bill_type' => $billAdvice->bill_type ?? 'Contingency',
                'bill_amount' => (float) $billAdvice->total_amount,
                'treasury_name' => 'District Treasury Office, Gandhinagar',
                'token_no' => 'TOK/' . date('Y') . '/' . str_pad(mt_rand(100, 999), 4, '0', STR_PAD_LEFT),
                'token_date' => date('Y-m-d'),
                'tv_no' => '',
                'tv_date' => '',
                'status' => 'Submitted',
                'is_existing' => false,
            ]);
        }

        return response()->json([
            'advice_date' => date('Y-m-d'),
            'bill_type' => 'Contingency',
            'bill_amount' => 45000.00,
            'treasury_name' => 'District Treasury Office, Gandhinagar',
            'token_no' => 'TOK/' . date('Y') . '/' . str_pad(mt_rand(100, 999), 4, '0', STR_PAD_LEFT),
            'token_date' => date('Y-m-d'),
            'tv_no' => '',
            'tv_date' => '',
            'status' => 'Submitted',
            'is_existing' => false,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeDivision($request);

        $validated = $request->validate([
            'bill_no' => ['required', 'string', 'max:100'],
            'advice_no' => ['required', 'string', 'max:100'],
            'advice_date' => ['nullable', 'date'],
            'bill_type' => ['nullable', 'string', 'max:100'],
            'bill_amount' => ['required', 'numeric', 'min:0'],
            'treasury_name' => ['nullable', 'string', 'max:255'],
            'token_no' => ['nullable', 'string', 'max:100'],
            'token_date' => ['nullable', 'date'],
            'tv_no' => ['nullable', 'string', 'max:100'],
            'tv_date' => ['nullable', 'date'],
            'payment_ref_no' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $detail = TreasuryDetail::updateOrCreate(
            [
                'division_id' => $request->user()->id,
                'bill_no' => $validated['bill_no'],
                'advice_no' => $validated['advice_no'],
            ],
            [
                'advice_date' => $validated['advice_date'] ?? date('Y-m-d'),
                'bill_type' => $validated['bill_type'] ?? 'Contingency',
                'bill_amount' => $validated['bill_amount'],
                'treasury_name' => $validated['treasury_name'] ?? 'District Treasury Office',
                'token_no' => $validated['token_no'] ?? null,
                'token_date' => $validated['token_date'] ?? null,
                'tv_no' => $validated['tv_no'] ?? null,
                'tv_date' => $validated['tv_date'] ?? null,
                'payment_ref_no' => $validated['payment_ref_no'] ?? null,
                'status' => $validated['status'] ?? 'Submitted',
                'remarks' => $validated['remarks'] ?? null,
            ]
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Treasury details saved successfully.',
                'data' => $detail,
            ]);
        }

        return redirect()->route('division.treasury-details.index')
            ->with('status', 'Treasury details saved successfully.');
    }

    public function destroy(Request $request, TreasuryDetail $treasuryDetail): RedirectResponse
    {
        $this->authorizeDivision($request);
        abort_unless($treasuryDetail->division_id === $request->user()->id, 403);

        $treasuryDetail->delete();

        return redirect()->route('division.treasury-details.index')
            ->with('status', 'Treasury detail record deleted successfully.');
    }

    private function getAvailableBillAndAdviceData(User $divisionUser): array
    {
        $billNumbers = [];
        $adviceNumbers = [];
        $billAdviceMap = [];

        $advices = BillAdvice::query()
            ->where('division_id', $divisionUser->id)
            ->latest()
            ->get();

        foreach ($advices as $adv) {
            $adviceNumbers[] = $adv->advice_no;
            $bNo = 'BR/' . $adv->created_at->format('Y-y') . '/' . preg_replace('/[^0-9]/', '', $adv->advice_no);
            $billNumbers[] = $bNo;

            if (!isset($billAdviceMap[$bNo])) {
                $billAdviceMap[$bNo] = [];
            }
            $billAdviceMap[$bNo][] = [
                'advice_no' => $adv->advice_no,
                'advice_date' => $adv->advice_date->format('Y-m-d'),
                'bill_type' => $adv->bill_type,
                'bill_amount' => $adv->total_amount,
            ];
        }

        $gstChallans = GstChallan::query()
            ->where('division_id', $divisionUser->id)
            ->get();

        foreach ($gstChallans as $gc) {
            $billNumbers[] = $gc->bill_register_no;
            $adviceNumbers[] = $gc->advice_no;
        }

        if (empty($billNumbers)) {
            $billNumbers = ['BR/2026-27/001', 'BR/2026-27/002', 'BR/2026-27/042', 'BR/2026-27/043'];
        }
        if (empty($adviceNumbers)) {
            $adviceNumbers = ['ADV/2026/001', 'ADV/2026/002', 'ADV/2026/042', 'ADV/2026/043'];
        }

        return [
            'billNumbers' => array_values(array_unique($billNumbers)),
            'adviceNumbers' => array_values(array_unique($adviceNumbers)),
            'billAdviceMap' => $billAdviceMap,
        ];
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()?->isDivision(), 403);
    }
}
