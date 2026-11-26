<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Browse every migrated voucher (all six entry types), scoped by role and financial year. */
class VoucherBrowseController extends Controller
{
    /** Field groups on the detail page (spreadsheet headers). Anything not listed falls into "Other". */
    private const GROUPS = [
        'Voucher' => ['Entry Type', 'Sr. No.', 'Docket No.', 'Entry Month', 'Unique Code of Data Entry'],
        'Budget & scheme' => ['Tender Code', 'Budget Code', 'Scheme', 'Class', 'Model', 'Scheme Year', 'Year From Plot'],
        'Location' => ['Round', 'Beat', 'Place', 'Area (Ha.)', 'Description'],
        'Bill' => ['Bill No.', 'Bill Date', 'Register Name', 'Register Page No.', 'Remarks By RFO If Any'],
        'Party & bank' => ['Party Code', 'Party Name', 'Pan Card No.', 'GST No.', 'Account No.', 'Bank Name', 'IFSC', 'Branch', 'Payment Mode', 'EDP Code'],
        'Amounts' => ['Sub Total', 'Approved %', 'Amount as per Approved Rate', 'SGST', 'CGST', 'IGST', 'Labour Cess', 'Adjustment (+ or -)', 'Payable Total Amount'],
        'Deductions' => ['Deduction SGST', 'Deduction CGST', 'Deduction IGST', 'Deduction Labour Cess', 'GPF', 'CPF', 'P.Tax', 'Group Insurance', 'Deposit Deduction Amount from Bill', 'TDS', 'Other Deductions', 'Total Deduction', 'Net Amount'],
        'Division processing' => ['Received at Division', 'Approved By ACF', 'Rejected Reason', 'Rejection Date', 'Remark by Division'],
        'Advice & treasury' => ['Advice No.', 'Advice Date', 'IFMS Bill Type', 'Actual IFMS Bill Type', 'Bill Register No.', 'Bill Order Outward No.', 'GST Challan No.', 'Treasury Voucher No.', 'Treasury Approval Date', 'Treasury Approval Month', 'Treasury Status', 'ePayment Code', 'UTR No.', 'Treasury Return Date', 'Reason of Return by Treasury', 'Bill Transit Reg. Sr. No.', 'Voucher No. Final', 'Other Details of Advice'],
        'Beneficiary' => ['Bene. Code', 'Bene. Full Name', 'Name of Yojana', 'Bene. Name In Gujarati', 'Mobile No.', 'Gender', 'Fathers Name in case of Female Bene.', 'Birth Date', 'Address', 'Pin Code', 'Category', 'District', 'Taluka', 'Village', 'Survey No. / Block No.', 'ID Type', 'ID No.', 'GPS-N', 'GPS-E', 'GPS', 'Bene. Selection Year', 'Year / Instal. No.', 'Bene. Application Date', 'Bene. Application Sanctioned Date', 'Ha / DCP Plants', 'Total No. of Plants', 'Survival % Data Date', 'Survival %', 'SF Model + Year', 'Deduction of Ropa Amount for VruxKheti', 'Range Case Number', 'Division Case Number'],
        'Audit' => ['Time Stamp', 'Entered By', 'No. of times Edited', 'Status', 'Program Remark', 'Join 1 to 9', 'Extra 1', 'Extra 2', 'Extra 3', 'Extra 4'],
    ];

    private const NOT_MONEY = ['Approved %', 'Area (Ha.)', 'Ha / DCP Plants', 'Total No. of Plants', 'GPS-N', 'GPS-E'];

    public function index(Request $request): View
    {
        $user = $request->user();
        $yearId = CurrentFinancialYear::id();
        $base = Voucher::query()->forYear($yearId)->visibleTo($user);

        $q = (clone $base)->with('range:id,name');
        if ($type = $request->query('entry_type')) {
            $q->where('entry_type', $type);
        }
        if (($rangeId = $request->query('range_id')) && ! $user->isRange()) {
            $q->where('range_id', $rangeId);
        }
        if ($month = $request->query('month')) {
            $q->where('entry_month', $month);
        }
        if ($budget = trim((string) $request->query('budget_code'))) {
            $q->where('budget_code', $budget);
        }
        if ($status = $request->query('treasury_status')) {
            $status === '__none' ? $q->whereNull('treasury_status') : $q->where('treasury_status', $status);
        }
        if ($request->boolean('only_older')) {
            $q->where('only_in_older_export', true);
        }
        if ($term = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($term) {
                foreach (['party_name', 'party_code', 'bill_no', 'description', 'bill_register_no', 'voucher_no_final', 'treasury_voucher_no', 'unique_code', 'bene_full_name', 'place', 'remarks_by_rfo'] as $col) {
                    $w->orWhere($col, 'like', "%{$term}%");
                }
                if (ctype_digit($term)) {
                    $w->orWhere('serial_number', (int) $term)->orWhere('advice_no', (int) $term);
                }
            });
        }

        $totals = (clone $q)->reorder()->selectRaw('COUNT(*) c, SUM(sub_total) sub, SUM(payable_total_amount) pay, SUM(total_deduction) ded, SUM(net_amount) net')->first();
        $vouchers = $q->orderBy('range_id')->orderBy('entry_type')->orderBy('serial_number')->paginate(50)->withQueryString();

        return view('admin.vouchers.index', [
            'vouchers' => $vouchers,
            'totals' => $totals,
            'entryTypes' => (clone $base)->distinct()->orderBy('entry_type')->pluck('entry_type'),
            'months' => (clone $base)->whereNotNull('entry_month')->distinct()->pluck('entry_month'),
            'statuses' => (clone $base)->whereNotNull('treasury_status')->selectRaw('CAST(treasury_status AS CHAR(60)) AS s')->groupBy('s')->orderBy('s')->pluck('s'),   // a TEXT column: grouping a short cast avoids an on-disk temporary table
            'ranges' => $user->isRange() ? collect() : User::whereIn('id', (clone $base)->distinct()->pluck('range_id'))->orderBy('name')->get(['id', 'name']),
            'filters' => $request->query(),
            'olderCount' => (clone $base)->where('only_in_older_export', true)->count(),
        ]);
    }

    public function show(Request $request, Voucher $voucher): View
    {
        abort_unless(Voucher::visibleTo($request->user())->whereKey($voucher->id)->exists(), 403);

        $columns = config('voucher_columns');
        $groups = [];
        $placed = [];
        foreach (self::GROUPS as $title => $headers) {
            $rows = [];
            foreach ($headers as $h) {
                $placed[$h] = true;
                if (! isset($columns[$h])) {
                    continue;
                }
                $value = $this->display($h, $columns[$h], $voucher->{$columns[$h][0]});
                if ($value !== null) {
                    $rows[$h] = $value;
                }
            }
            if ($rows) {
                $groups[$title] = $rows;
            }
        }
        $other = [];
        foreach ($columns as $h => $def) {
            if (! isset($placed[$h]) && $h !== 'Item Details' && $h !== 'Range') {
                $value = $this->display($h, $def, $voucher->{$def[0]});
                if ($value !== null) {
                    $other[$h] = $value;
                }
            }
        }
        if ($other) {
            $groups['Other'] = $other;
        }

        $versions = $voucher->versions()->get()->map(function ($v) use ($columns, $voucher) {
            $changes = [];
            foreach (($v->diff_fields ?? []) as $h) {
                if ($h === 'Item Details' || ! isset($columns[$h])) {
                    continue;
                }
                $changes[] = [
                    'field' => $h,
                    'older' => $this->display($h, $columns[$h], $this->rawOlder($v->olderValue($h), $columns[$h][1])) ?? '-',
                    'live' => $this->display($h, $columns[$h], $voucher->{$columns[$h][0]}) ?? '-',
                ];
            }

            return ['version' => $v, 'changes' => $changes, 'itemsChanged' => in_array('Item Details', $v->diff_fields ?? [], true), 'olderItems' => in_array('Item Details', $v->diff_fields ?? [], true) ? $v->olderItems() : []];
        });

        return view('admin.vouchers.show', [
            'voucher' => $voucher->load('range:id,name', 'items'),
            'groups' => $groups,
            'versions' => $versions,
        ]);
    }

    public function print(Request $request, Voucher $voucher): View
    {
        abort_unless(Voucher::visibleTo($request->user())->whereKey($voucher->id)->exists(), 403);

        return view('admin.vouchers.print', ['voucher' => $voucher->load('range:id,name', 'items')]);
    }

    private function rawOlder(mixed $value, string $kind): mixed
    {
        return $value;
    }

    /** Printable value or null when empty. */
    private function display(string $header, array $def, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        [, $kind] = $def;
        if ($kind === 'dec') {
            return in_array($header, self::NOT_MONEY, true) ? rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.') : Format::inr($value);
        }
        if ($kind === 'date' || $kind === 'datetime') {
            $s = substr((string) $value, 0, $kind === 'date' ? 10 : 19);

            return preg_match('/^\d{4}-\d{2}-\d{2}/', $s) ? \Illuminate\Support\Carbon::parse($s)->format($kind === 'date' ? 'd-m-Y' : 'd-m-Y H:i') : $s;
        }
        if (is_float($value) || (is_numeric($value) && ! is_string($value))) {
            return rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.');
        }

        return (string) $value;
    }
}
