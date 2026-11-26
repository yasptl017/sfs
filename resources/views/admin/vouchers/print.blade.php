@php
    use App\Support\Format;
    $d = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('d-m-Y') : '';
    $profile = auth()->user()->getOrCreateOfficeProfile();
@endphp
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><title>{{ $voucher->entry_type }} {{ $voucher->serial_number }}</title>
<style>
    body { font-family: "Noto Sans Gujarati", "Shruti", Arial, sans-serif; font-size: 12px; color: #000; margin: 0; }
    .page { width: 190mm; margin: 8mm auto; }
    h1 { font-size: 15px; text-align: center; margin: 0 0 2px; } h2 { font-size: 12px; text-align: center; margin: 0 0 8px; font-weight: normal; }
    table { width: 100%; border-collapse: collapse; margin: 6px 0; } td, th { border: 1px solid #000; padding: 3px 5px; vertical-align: top; } th { background: #eee; text-align: left; }
    .r { text-align: right; } .nb td { border: 0; padding: 1px 4px; } .sign { display: flex; justify-content: space-between; margin-top: 40px; }
    .noprint { text-align: center; margin: 10px; } @media print { .noprint { display: none; } .page { margin: 0 auto; } }
</style></head>
<body>
<div class="noprint"><button onclick="window.print()">Print</button> <a href="{{ route('vouchers.show', $voucher) }}">Back</a></div>
<div class="page">
    <h1>{{ $profile->display_name_gujarati ?: $profile->display_name }}</h1>
    <h2>{{ $voucher->entry_type }} - Voucher / Sr. No. {{ $voucher->serial_number }} - {{ $voucher->range?->name }} - FY {{ \App\Support\CurrentFinancialYear::model()->code }}</h2>
    <table class="nb">
        <tr><td>Budget code: <b>{{ $voucher->budget_code }}</b></td><td>Scheme: {{ $voucher->scheme }}</td><td>Docket no.: {{ $voucher->docket_no }}</td><td>Month: {{ $voucher->entry_month }}</td></tr>
        <tr><td colspan="2">Model: {{ $voucher->model }}</td><td>Bill no.: {{ $voucher->bill_no }}</td><td>Bill date: {{ $d($voucher->bill_date) }}</td></tr>
        <tr><td colspan="4">Place: {{ implode(' / ', array_filter([$voucher->round, $voucher->beat, $voucher->place])) }}@if($voucher->area_ha) ({{ $voucher->area_ha + 0 }} Ha.)@endif</td></tr>
        <tr><td colspan="4">{!! nl2br(e($voucher->description)) !!}</td></tr>
        <tr><td colspan="2">Party: <b>{{ $voucher->party_name }}</b> ({{ $voucher->party_code }})</td><td>PAN: {{ $voucher->pan_card_no }}</td><td>GST: {{ $voucher->gst_no }}</td></tr>
        <tr><td colspan="2">Bank: {{ $voucher->bank_name }}, {{ $voucher->branch }}</td><td>A/c: {{ $voucher->account_no }}</td><td>IFSC: {{ $voucher->ifsc }}</td></tr>
    </table>
    <table>
        <thead><tr><th>#</th><th>SOR</th><th>Work description</th><th>W.O.</th><th class="r">Qty</th><th>Unit</th><th class="r">Rate</th><th class="r">Amount</th></tr></thead>
        <tbody>@foreach($voucher->items as $i)<tr><td>{{ $i->line_no }}</td><td>{{ $i->sor_code }}</td><td>{!! nl2br(e($i->description)) !!}</td><td>{{ $i->work_order_no }}@if($i->work_order_date) <br>{{ $d($i->work_order_date) }}@endif</td><td class="r">{{ $i->quantity !== null ? rtrim(rtrim(number_format($i->quantity, 4, '.', ''), '0'), '.') : '' }}</td><td>{{ $i->unit }}</td><td class="r">{{ Format::inr($i->rate) }}</td><td class="r">{{ Format::inr($i->amount) }}</td></tr>@endforeach</tbody>
        <tfoot><tr><th colspan="7" class="r">Sub total</th><th class="r">{{ Format::inr($voucher->sub_total) }}</th></tr></tfoot>
    </table>
    <table>
        @php($adds = ['Amount as per approved rate (' . ($voucher->approved_percent + 0) . '%)' => $voucher->amount_as_per_approved_rate, 'SGST' => $voucher->sgst, 'CGST' => $voucher->cgst, 'IGST' => $voucher->igst, 'Labour cess' => $voucher->labour_cess, 'Adjustment' => $voucher->adjustment])
        @foreach($adds as $l => $v)@if($v)<tr><td>{{ $l }}</td><td class="r">{{ Format::inr($v) }}</td></tr>@endif @endforeach
        <tr><th>Payable total</th><th class="r">{{ Format::inr($voucher->payable_total_amount) }}</th></tr>
        @foreach(['Deduction SGST' => $voucher->deduction_sgst, 'Deduction CGST' => $voucher->deduction_cgst, 'Deduction IGST' => $voucher->deduction_igst, 'Deduction labour cess' => $voucher->deduction_labour_cess, 'GPF' => $voucher->gpf, 'CPF' => $voucher->cpf, 'Professional tax' => $voucher->p_tax, 'Group insurance' => $voucher->group_insurance, 'Deposit deduction' => $voucher->deposit_deduction_amount, 'TDS' => $voucher->tds, 'Other' => $voucher->other_deductions] as $l => $v)@if($v)<tr><td>{{ $l }}</td><td class="r">{{ Format::inr($v) }}</td></tr>@endif @endforeach
        <tr><td>Total deduction</td><td class="r">{{ Format::inr($voucher->total_deduction) }}</td></tr>
        <tr><th>Net amount payable</th><th class="r">{{ Format::inr($voucher->net_amount) }}</th></tr>
    </table>
    @if($voucher->advice_no)<p>Advice no. {{ $voucher->advice_no }} dated {{ $d($voucher->advice_date) }} - bill register {{ $voucher->bill_register_no }}@if($voucher->voucher_no_final) - voucher no. {{ $voucher->voucher_no_final }}@endif</p>@endif
    <div class="sign"><span>Prepared by</span><span>Range Forest Officer</span><span>Deputy Conservator of Forests</span></div>
</div>
</body></html>
