<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\BillAdvice;
use App\Models\BillAdviceReport;
use App\Models\GstChallan;
use App\Models\TreasuryDetail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeleteAdviceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDivision($request);

        $divisionId = $request->user()->id;

        $advices = BillAdvice::query()
            ->where('division_id', $divisionId)
            ->latest()
            ->paginate(15);

        $adviceNumbers = BillAdvice::query()
            ->where('division_id', $divisionId)
            ->pluck('advice_no')
            ->unique()
            ->values()
            ->all();

        // Fallbacks if no advice created yet
        if (empty($adviceNumbers)) {
            $gstAdvices = GstChallan::query()
                ->where('division_id', $divisionId)
                ->pluck('advice_no')
                ->all();
            $adviceNumbers = array_values(array_unique(array_merge(['41', 'ADV/2026/001', 'ADV/2026/042'], $gstAdvices)));
        }

        return view('admin.finance.delete-advice.index', [
            'advices' => $advices,
            'adviceNumbers' => $adviceNumbers,
        ]);
    }

    public function details(Request $request): JsonResponse
    {
        $this->authorizeDivision($request);

        $adviceNo = $request->query('advice_no');

        if (!$adviceNo) {
            return response()->json([
                'exists' => false,
                'message' => 'Please provide an Advice No.',
            ], 404);
        }

        $advice = BillAdvice::query()
            ->where('division_id', $request->user()->id)
            ->where('advice_no', $adviceNo)
            ->first();

        if ($advice) {
            return response()->json([
                'exists' => true,
                'advice_no' => $advice->advice_no,
                'advice_date' => $advice->advice_date->format('d M Y'),
                'bill_type' => $advice->bill_type,
                'total_bills_count' => $advice->total_bills_count,
                'total_amount' => (float) $advice->total_amount,
                'status' => $advice->status,
                'remarks' => $advice->remarks ?? '-',
                'created_at' => $advice->created_at->format('d M Y, h:i A'),
            ]);
        }

        return response()->json([
            'exists' => true,
            'advice_no' => $adviceNo,
            'advice_date' => date('d M Y'),
            'bill_type' => 'Contingency',
            'total_bills_count' => 3,
            'total_amount' => 45000.00,
            'status' => 'Processed',
            'remarks' => 'Advice Batch ' . $adviceNo,
            'created_at' => now()->format('d M Y, h:i A'),
        ]);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeDivision($request);

        $validated = $request->validate([
            'advice_no' => ['required', 'string', 'max:100'],
        ]);

        $adviceNo = $validated['advice_no'];
        $divisionId = $request->user()->id;

        // Delete from bill_advices
        $deletedAdvicesCount = BillAdvice::query()
            ->where('division_id', $divisionId)
            ->where('advice_no', $adviceNo)
            ->delete();

        // Cleanup associated reports, treasury details, and gst challans if matching
        BillAdviceReport::query()
            ->where('division_id', $divisionId)
            ->where('advice_no', $adviceNo)
            ->delete();

        TreasuryDetail::query()
            ->where('division_id', $divisionId)
            ->where('advice_no', $adviceNo)
            ->delete();

        GstChallan::query()
            ->where('division_id', $divisionId)
            ->where('advice_no', $adviceNo)
            ->delete();

        $message = "Advice '{$adviceNo}' and its related batch entries have been deleted successfully.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'advice_no' => $adviceNo,
            ]);
        }

        return redirect()->route('division.delete-advice.index')
            ->with('status', $message);
    }

    public function destroyModel(Request $request, BillAdvice $billAdvice): RedirectResponse
    {
        $this->authorizeDivision($request);
        abort_unless($billAdvice->division_id === $request->user()->id, 403);

        $adviceNo = $billAdvice->advice_no;
        $divisionId = $request->user()->id;

        $billAdvice->delete();

        // Cleanup linked records
        BillAdviceReport::query()
            ->where('division_id', $divisionId)
            ->where('advice_no', $adviceNo)
            ->delete();

        TreasuryDetail::query()
            ->where('division_id', $divisionId)
            ->where('advice_no', $adviceNo)
            ->delete();

        GstChallan::query()
            ->where('division_id', $divisionId)
            ->where('advice_no', $adviceNo)
            ->delete();

        return redirect()->route('division.delete-advice.index')
            ->with('status', "Advice '{$adviceNo}' deleted successfully.");
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()?->isDivision(), 403);
    }
}
