<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherVersion;
use App\Support\CurrentFinancialYear;
use App\Support\FinalVoucherNumbering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Division bill processing on the voucher columns (replaces the legacy JSON bill-advice modules).
 * An advice = all vouchers sharing one advice_no. IFMS advices (Simple Receipt / Contingency) are numbered 1.., SNA advices 5001..
 * Bill register no = "<advice no>/<financial year>". Final voucher nos: SR-n / CONT-n / SNA-n.
 */
class AdviceController extends Controller
{
    public const BILL_TYPES = ['Simple Receipt', 'Contingency', 'SNA'];


    private const FIELDS = ['advice_no' => 'Advice No.', 'advice_date' => 'Advice Date', 'ifms_bill_type' => 'IFMS Bill Type', 'actual_ifms_bill_type' => 'Actual IFMS Bill Type',
        'bill_register_no' => 'Bill Register No.', 'bill_order_outward_no' => 'Bill Order Outward No.', 'gst_challan_no' => 'GST Challan No.', 'treasury_voucher_no' => 'Treasury Voucher No.',
        'treasury_approval_date' => 'Treasury Approval Date', 'treasury_approval_month' => 'Treasury Approval Month', 'treasury_status' => 'Treasury Status', 'epayment_code' => 'ePayment Code',
        'utr_no' => 'UTR No.', 'received_at_division' => 'Received at Division', 'voucher_no_final' => 'Voucher No. Final', 'payment_mode' => 'Payment Mode'];

    /** Advices of the year with totals and status. */
    public function index(Request $request): View
    {
        $this->authorizeDivision($request);
        $q = $this->base($request)->whereNotNull('advice_no')
            ->selectRaw('advice_no, MAX(ifms_bill_type) bill_type, MAX(advice_date) advice_date, MAX(bill_register_no) bill_register_no, COUNT(*) vouchers, SUM(payable_total_amount) payable, SUM(net_amount) net, '
                .'MAX(treasury_status) treasury_status, MAX(treasury_voucher_no) treasury_voucher_no, MAX(treasury_approval_date) treasury_date, MAX(gst_challan_no) gst_challan_no, SUM(voucher_no_final IS NOT NULL) finals')
            ->groupBy('advice_no');
        if ($t = $request->query('bill_type')) {
            $q->where('ifms_bill_type', $t);
        }
        if ($s = $request->query('status')) {
            $s === 'pending' ? $q->havingRaw('MAX(treasury_status) IS NULL') : $q->havingRaw('MAX(treasury_status) = ?', [$s]);
        }
        if ($term = trim((string) $request->query('q'))) {
            $q->where(fn ($w) => $w->where('advice_no', (int) $term)->orWhere('bill_register_no', 'like', "%{$term}%")->orWhere('treasury_voucher_no', 'like', "%{$term}%")->orWhere('epayment_code', 'like', "%{$term}%"));
        }

        return view('admin.finance.advices.index', [
            'advices' => $q->orderByDesc('advice_no')->paginate(40)->withQueryString(), 'filters' => $request->query(), 'billTypes' => self::BILL_TYPES,
            'pendingCount' => $this->pendingQuery($request)->count(),
        ]);
    }

    /** Vouchers received at the division and not yet in an advice. */
    public function pending(Request $request): View
    {
        $this->authorizeDivision($request);
        $receiveMode = $request->query('received') === '0';
        $q = ($receiveMode ? $this->notReceivedQuery($request) : $this->pendingQuery($request))->with('range:id,name');
        if ($r = $request->query('range_id')) {
            $q->where('range_id', $r);
        }
        if ($p = $request->query('payment_mode')) {
            $q->where('payment_mode', $p);
        }
        if ($t = $request->query('entry_type')) {
            $q->where('entry_type', $t);
        }

        return view('admin.finance.advices.pending', [
            'vouchers' => $q->orderBy('range_id')->orderBy('entry_type')->orderBy('serial_number')->paginate(100)->withQueryString(), 'filters' => $request->query(), 'billTypes' => self::BILL_TYPES,
            'ranges' => User::whereIn('id', $this->base($request)->distinct()->pluck('range_id'))->orderBy('name')->get(['id', 'name']),
            'notReceived' => $this->notReceivedQuery($request)->count(), 'receiveMode' => $receiveMode,
        ]);
    }

    /** Marks vouchers as received at the division (a voucher must be received before it can go into an advice). */
    public function receive(Request $request): RedirectResponse
    {
        $this->authorizeDivision($request);
        $d = $request->validate(['voucher_ids' => ['required', 'array', 'min:1'], 'voucher_ids.*' => ['integer']], ['voucher_ids.required' => 'Select at least one voucher.']);
        $n = DB::transaction(function () use ($request, $d) {
            $vouchers = $this->notReceivedQuery($request)->whereIn('id', $d['voucher_ids'])->lockForUpdate()->get();
            foreach ($vouchers as $v) {
                $this->audit($v, 'received', ['received_at_division' => 'Received']);
            }

            return $vouchers->count();
        });

        return redirect()->route('division.advices.pending', ['received' => '0'])->with('status', "$n voucher(s) marked as received. They can now be processed into an advice.");
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDivision($request);
        $d = $request->validate(['voucher_ids' => ['required', 'array', 'min:1'], 'voucher_ids.*' => ['integer'], 'bill_type' => ['required', Rule::in(self::BILL_TYPES)],
            'advice_date' => ['required', 'date'], 'bill_order_outward_no' => ['nullable', 'string', 'max:500']], ['voucher_ids.required' => 'Select at least one voucher.']);

        $no = DB::transaction(function () use ($request, $d) {
            $vouchers = $this->pendingQuery($request)->whereIn('id', $d['voucher_ids'])->lockForUpdate()->get();
            abort_if($vouchers->count() !== count(array_unique($d['voucher_ids'])), 422, 'Some selected vouchers are no longer pending.');
            $sna = $d['bill_type'] === 'SNA';
            abort_if($vouchers->contains(fn ($v) => ($v->payment_mode === 'SNA') !== $sna), 422, 'SNA vouchers must go in an SNA advice and IFMS vouchers in a Simple Receipt / Contingency advice.');
            $no = $sna ? max(5000, (int) $this->allAdvices()->where('advice_no', '>=', 5001)->max('advice_no')) + 1 : ((int) $this->allAdvices()->where('advice_no', '<', 5001)->max('advice_no')) + 1;
            $fyCode = CurrentFinancialYear::model()->code;
            foreach ($vouchers as $v) {
                $this->audit($v, 'advice_created', ['advice_no' => $no, 'advice_date' => $d['advice_date'], 'ifms_bill_type' => $d['bill_type'], 'actual_ifms_bill_type' => $d['bill_type'],
                    'bill_register_no' => "$no/$fyCode", 'bill_order_outward_no' => $d['bill_order_outward_no'] ?? null]);
            }

            return $no;
        });

        return redirect()->route('division.advices.show', $no)->with('status', "Advice $no created.");
    }

    public function show(Request $request, int $adviceNo): View
    {
        $this->authorizeDivision($request);
        $vouchers = $this->base($request)->where('advice_no', $adviceNo)->with('range:id,name')->orderBy('range_id')->orderBy('entry_type')->orderBy('serial_number')->get();
        abort_if($vouchers->isEmpty(), 404);
        $first = $vouchers->first();

        return view('admin.finance.advices.show', ['adviceNo' => $adviceNo, 'vouchers' => $vouchers, 'first' => $first, 'billTypes' => self::BILL_TYPES]);
    }

    public function treasury(Request $request, int $adviceNo): RedirectResponse
    {
        $this->authorizeDivision($request);
        $d = $request->validate(['treasury_voucher_no' => ['required', 'string', 'max:50'], 'treasury_approval_date' => ['required', 'date'], 'treasury_status' => ['required', Rule::in(['Approved', 'Returned', 'Pending'])],
            'epayment_code' => ['nullable', 'string', 'max:100'], 'utr_no' => ['nullable', 'string', 'max:100']]);
        $d['treasury_approval_month'] = Carbon::parse($d['treasury_approval_date'])->format('M');
        $this->apply($request, $adviceNo, 'treasury_details', $d);

        return back()->with('status', 'Treasury details saved for the whole advice.');
    }

    public function gst(Request $request, int $adviceNo): RedirectResponse
    {
        $this->authorizeDivision($request);
        $d = $request->validate(['gst_challan_no' => ['nullable', 'string', 'max:100']]);
        $this->apply($request, $adviceNo, 'gst_challan', ['gst_challan_no' => $d['gst_challan_no'] ?: null]);

        return back()->with('status', 'GST challan number saved for the whole advice.');
    }

    public function numbers(Request $request, int $adviceNo): RedirectResponse
    {
        $this->authorizeDivision($request);
        $d = $request->validate(['advice_date' => ['required', 'date'], 'bill_register_no' => ['required', 'string', 'max:100'], 'bill_order_outward_no' => ['nullable', 'string', 'max:500']]);
        $this->apply($request, $adviceNo, 'numbers_changed', $d);

        return back()->with('status', 'Advice date / bill register no. / order no. updated.');
    }

    /** Gives the vouchers of an advice their final voucher numbers (SR-/CONT-/SNA- series of the treasury-approval month), in range / type / serial order. */
    public function finalNumbers(Request $request, int $adviceNo): RedirectResponse
    {
        $this->authorizeDivision($request);
        DB::transaction(function () use ($request, $adviceNo) {
            $vouchers = $this->base($request)->where('advice_no', $adviceNo)->whereNull('voucher_no_final')->orderBy('range_id')->orderBy('entry_type')->orderBy('serial_number')->lockForUpdate()->get();
            abort_if($vouchers->isEmpty(), 422, 'All vouchers of this advice already have final numbers.');
            FinalVoucherNumbering::assign($vouchers, CurrentFinancialYear::id());
        });

        return back()->with('status', 'Final voucher numbers assigned.');
    }

    /** Removes the advice from its vouchers (they become pending again). Not allowed once treasury has approved it. */
    public function destroy(Request $request, int $adviceNo): RedirectResponse
    {
        $this->authorizeDivision($request);
        $blocked = $this->base($request)->where('advice_no', $adviceNo)->where('treasury_status', 'Approved')->exists();
        abort_if($blocked, 422, 'This advice is already approved by the treasury and cannot be deleted.');
        $this->apply($request, $adviceNo, 'advice_deleted', array_fill_keys(array_keys(self::FIELDS), null), except: ['payment_mode', 'received_at_division']);

        return redirect()->route('division.advices.index')->with('status', "Advice $adviceNo deleted; its vouchers are pending again.");
    }

    // ------------------------------------------------------------------------------------------------ helpers
    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()->isDivision(), 403);
    }

    private function base(Request $request)
    {
        return Voucher::query()->visibleTo($request->user())->forYear(CurrentFinancialYear::id());
    }

    private function notReceivedQuery(Request $request)
    {
        return $this->base($request)->whereNull('advice_no')->whereNull('rejected_reason')->where(fn ($q) => $q->whereNull('received_at_division')->orWhere('received_at_division', '!=', 'Received'));
    }

    private function allAdvices()
    {
        return Voucher::query()->forYear(CurrentFinancialYear::id());
    }

    private function pendingQuery(Request $request)
    {
        return $this->base($request)->whereNull('advice_no')->whereNull('rejected_reason')->where('received_at_division', 'Received');
    }

    private function apply(Request $request, int $adviceNo, string $kind, array $values, array $except = []): void
    {
        DB::transaction(function () use ($request, $adviceNo, $kind, $values, $except) {
            $vouchers = $this->base($request)->where('advice_no', $adviceNo)->lockForUpdate()->get();
            abort_if($vouchers->isEmpty(), 404);
            foreach ($vouchers as $v) {
                $this->audit($v, $kind, array_diff_key($values, array_flip($except)));
            }
        });
    }

    /** Update the given columns of a voucher and keep the old values as an audit version. */
    private function audit(Voucher $v, string $kind, array $values): void
    {
        $old = $new = [];
        foreach ($values as $col => $val) {
            $before = $v->{$col};
            $before = $before instanceof \DateTimeInterface ? $before->format('Y-m-d') : $before;
            if ((string) $before !== (string) ($val ?? '')) {
                $old[self::FIELDS[$col] ?? $col] = $before;
                $new[$col] = $val;
            }
        }
        if (! $new) {
            return;
        }
        VoucherVersion::create(['financial_year_id' => $v->financial_year_id, 'range_id' => $v->range_id, 'entry_type' => $v->entry_type, 'serial_number' => $v->serial_number, 'voucher_id' => $v->id,
            'version_kind' => 'advice:'.$kind, 'source_file' => 'division processing', 'source_rows' => [], 'snapshot' => json_encode(['rows' => [['r' => null, 'c' => array_filter($old, fn ($x) => $x !== null)]]], JSON_UNESCAPED_UNICODE),
            'differs_from_live' => true, 'diff_fields' => array_keys($old)]);
        $v->forceFill($new)->save();
    }
}
