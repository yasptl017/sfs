@php use App\Support\Format; @endphp
<x-layouts.admin title="Advices | Forest Inventory" heading="Bill advices" subheading="Financial year {{ \App\Support\CurrentFinancialYear::model()->code }} - {{ number_format($advices->total()) }} advices">
    <div class="space-y-5">
        <div class="flex flex-wrap items-center gap-3">
            <a class="primary-button" href="{{ route('division.advices.pending') }}">Process bill - {{ number_format($pendingCount) }} voucher(s) waiting</a>
        </div>
        <form method="GET" class="grid gap-3 rounded-lg border border-emerald-100 bg-white p-4 md:grid-cols-4">
            <div><label class="form-label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-input" placeholder="Advice no., bill register, treasury voucher, ePayment code"></div>
            <div><label class="form-label" for="bill_type">Bill type</label><select id="bill_type" name="bill_type" class="form-select"><option value="">All</option>@foreach($billTypes as $t)<option @selected(($filters['bill_type'] ?? '') === $t)>{{ $t }}</option>@endforeach</select></div>
            <div><label class="form-label" for="status">Treasury status</label><select id="status" name="status" class="form-select"><option value="">All</option><option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Not yet at treasury</option>@foreach(['Approved', 'Returned', 'Pending'] as $s)<option @selected(($filters['status'] ?? '') === $s)>{{ $s }}</option>@endforeach</select></div>
            <div class="flex items-end gap-3"><button class="primary-button" type="submit">Apply</button><a class="secondary-button" href="{{ route('division.advices.index') }}">Reset</a></div>
        </form>
        <div class="overflow-x-auto rounded-lg border border-emerald-100 bg-white">
            <table class="data-table compact-data-table min-w-[70rem]">
                <thead><tr><th>Advice</th><th>Bill type</th><th>Date</th><th>Bill register</th><th class="text-right">Vouchers</th><th class="text-right">Payable</th><th class="text-right">Net</th><th>Treasury</th><th>Final nos.</th><th></th></tr></thead>
                <tbody>
                    @forelse($advices as $a)
                        <tr>
                            <td class="font-semibold">{{ $a->advice_no }}</td><td>{{ $a->bill_type }}</td>
                            <td>{{ $a->advice_date ? \Illuminate\Support\Carbon::parse($a->advice_date)->format('d-m-Y') : '-' }}</td><td>{{ $a->bill_register_no }}</td>
                            <td class="text-right">{{ $a->vouchers }}</td><td class="text-right">{{ Format::inr($a->payable) }}</td><td class="text-right">{{ Format::inr($a->net) }}</td>
                            <td>{{ $a->treasury_status ?? 'not yet' }}@if($a->treasury_voucher_no) <span class="text-xs text-slate-500">(T.V. {{ $a->treasury_voucher_no }})</span>@endif</td>
                            <td>{{ $a->finals }}/{{ $a->vouchers }}</td>
                            <td><a class="secondary-button min-h-7 px-2 py-0.5 text-[11px]" href="{{ route('division.advices.show', $a->advice_no) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center">No advices match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($advices->hasPages())<div class="px-1 py-2">{{ $advices->links() }}</div>@endif
    </div>
</x-layouts.admin>
