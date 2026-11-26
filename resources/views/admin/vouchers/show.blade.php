@php use App\Support\Format; @endphp
<x-layouts.admin title="{{ $voucher->entry_type }} {{ $voucher->serial_number }} | Forest Inventory" heading="{{ $voucher->entry_type }} - Sr. {{ $voucher->serial_number }}" subheading="{{ $voucher->range?->name }} - financial year {{ \App\Support\CurrentFinancialYear::model()->code }}">
    <div class="space-y-5">
        <div class="flex flex-wrap items-center gap-3">
            <a class="secondary-button" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('vouchers.index') }}">&larr; Back to list</a>
            <a class="secondary-button" href="{{ route('vouchers.print', $voucher) }}" target="_blank">Print voucher</a>
            @php($typePlural = ['Tender Entry' => 'tender-entries', 'Free Entry' => 'free-entries', 'D.Wagers Salary' => 'd-wager-salary-entries', 'D.Wagers Arrears' => 'd-wager-arrears-entries', 'Bene. Entry' => 'sf-beneficiary-entries', 'WL Bene. Entry' => 'wl-beneficiary-entries'][$voucher->entry_type] ?? null)
            @if(auth()->user()->isRange() && $voucher->range_id === auth()->id() && $voucher->financial_year_id === \App\Support\CurrentFinancialYear::id() && $typePlural)
                @if($voucher->advice_no === null)
                    <a class="primary-button" href="{{ route('finance.'.$typePlural.'.edit', $voucher) }}">Edit</a>
                    <form method="POST" action="{{ route('finance.'.$typePlural.'.destroy', $voucher) }}" onsubmit="return confirm('Delete this voucher? A copy is kept in the audit trail.')">@csrf @method('DELETE')<button class="secondary-button border-red-200 bg-red-50 text-red-700" type="submit">Delete</button></form>
                @else
                    <span class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">Part of advice {{ $voucher->advice_no }} - locked</span>
                @endif
            @endif
            @if($voucher->only_in_older_export)
                <span class="rounded bg-amber-100 px-2 py-1 text-xs font-bold text-amber-800">Only in older export - not present in the latest register. Please review.</span>
            @endif
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            @foreach([['Payable total', $voucher->payable_total_amount], ['Total deduction', $voucher->total_deduction], ['Net amount', $voucher->net_amount]] as [$l, $v])
                <div class="rounded-lg border border-emerald-100 bg-white px-4 py-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $l }}</div>
                    <div class="mt-1 text-xl font-bold text-slate-950">{{ Format::inr($v) }}</div>
                </div>
            @endforeach
        </div>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">Line items ({{ $voucher->items->count() }})</h2></div>
            <div class="overflow-x-auto">
                <table class="data-table compact-data-table min-w-[60rem]">
                    <thead><tr><th>#</th><th>SOR code</th><th>Work description</th><th>Work order</th><th>Start</th><th>End</th><th class="text-right">Qty</th><th>Unit</th><th class="text-right">Rate</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        @foreach($voucher->items as $it)
                            <tr>
                                <td>{{ $it->line_no }}</td>
                                <td>{{ $it->sor_code ?? '-' }}</td>
                                <td class="max-w-[28rem] whitespace-pre-line">{{ $it->description }}</td>
                                <td>{{ $it->work_order_no ?? '-' }}@if($it->work_order_date)<br><span class="text-xs text-slate-500">{{ \Illuminate\Support\Carbon::parse($it->work_order_date)->format('d-m-Y') }}</span>@endif</td>
                                <td>{{ $it->work_start_date ? \Illuminate\Support\Carbon::parse($it->work_start_date)->format('d-m-Y') : '-' }}</td>
                                <td>{{ $it->work_end_date ? \Illuminate\Support\Carbon::parse($it->work_end_date)->format('d-m-Y') : '-' }}</td>
                                <td class="text-right">{{ $it->quantity !== null ? rtrim(rtrim(number_format($it->quantity, 6, '.', ''), '0'), '.') : '-' }}</td>
                                <td>{{ $it->unit ?? '-' }}</td>
                                <td class="text-right">{{ Format::inr($it->rate) }}</td>
                                <td class="text-right">{{ Format::inr($it->amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><th colspan="9" class="text-right">Sum of items</th><th class="text-right">{{ Format::inr($voucher->items->sum('amount')) }}</th></tr></tfoot>
                </table>
            </div>
        </section>

        @foreach($groups as $title => $rows)
            <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">{{ $title }}</h2></div>
                <dl class="grid gap-x-6 gap-y-3 p-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($rows as $label => $value)
                        <div class="min-w-0 @if(mb_strlen($value) > 80) sm:col-span-2 xl:col-span-3 @endif">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                            <dd class="mt-0.5 whitespace-pre-line break-words text-sm text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endforeach

        @if($versions->isNotEmpty())
            <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 px-5 py-3">
                    <h2 class="text-base font-semibold text-slate-950">Earlier versions ({{ $versions->count() }})</h2>
                    <p class="text-xs text-slate-500">Older copies kept from earlier exports. The values above are the live ones.</p>
                </div>
                <div class="divide-y divide-emerald-50">
                    @foreach($versions as $row)
                        @php($v = $row['version'])
                        <details class="px-5 py-3" @if(count($row['changes']) && count($row['changes']) <= 6) open @endif>
                            <summary class="cursor-pointer text-sm font-semibold text-slate-800">
                                {{ $v->kind_label }}
                                @if($v->differs_from_live)<span class="ml-2 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">{{ count($v->diff_fields ?? []) }} field(s) differ</span>
                                @else<span class="ml-2 rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold text-emerald-800">identical</span>@endif
                            </summary>
                            @if($row['changes'])
                                <table class="data-table compact-data-table mt-3">
                                    <thead><tr><th>Field</th><th>Older value</th><th>Live value</th></tr></thead>
                                    <tbody>@foreach($row['changes'] as $c)<tr><td>{{ $c['field'] }}</td><td class="break-words">{{ $c['older'] }}</td><td class="break-words">{{ $c['live'] }}</td></tr>@endforeach</tbody>
                                </table>
                            @endif
                            @if($row['itemsChanged'])
                                <p class="mt-3 text-xs font-semibold text-slate-600">Line items differ. Older line items:</p>
                                <table class="data-table compact-data-table mt-1">
                                    <thead><tr><th>SOR code</th><th>Work description</th><th class="text-right">Qty</th><th>Unit</th><th class="text-right">Rate</th><th class="text-right">Amount</th></tr></thead>
                                    <tbody>@foreach($row['olderItems'] as $it)<tr><td>{{ $it[0] ?? '-' }}</td><td class="whitespace-pre-line">{{ $it[1] }}</td><td class="text-right">{{ $it[2] }}</td><td>{{ $it[3] }}</td><td class="text-right">{{ $it[4] }}</td><td class="text-right">{{ $it[5] }}</td></tr>@endforeach</tbody>
                                </table>
                            @endif
                        </details>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.admin>
