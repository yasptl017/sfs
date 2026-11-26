@php
    use App\Support\Format;
    $d = fn ($v) => $v ? substr((string) $v, 0, 10) : '';
    $locked = $vouchers->contains(fn ($v) => $v->treasury_status === 'Approved');
@endphp
<x-layouts.admin title="Advice {{ $adviceNo }} | Forest Inventory" heading="Advice {{ $adviceNo }}" subheading="{{ $first->ifms_bill_type }} - bill register {{ $first->bill_register_no }}">
    <div class="space-y-5">
        <a class="secondary-button" href="{{ route('division.advices.index') }}">&larr; Advices</a>
        @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

        <div class="grid gap-3 sm:grid-cols-4">
            @foreach([['Vouchers', $vouchers->count()], ['Payable', Format::inr($vouchers->sum('payable_total_amount'))], ['Deduction', Format::inr($vouchers->sum('total_deduction'))], ['Net', Format::inr($vouchers->sum('net_amount'))]] as [$l, $v])
                <div class="rounded-lg border border-emerald-100 bg-white px-4 py-3"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $l }}</div><div class="mt-1 text-lg font-bold text-slate-950">{{ $v }}</div></div>
            @endforeach
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <form method="POST" action="{{ route('division.advices.treasury', $adviceNo) }}" class="space-y-3 rounded-lg border border-emerald-100 bg-white p-5">
                @csrf @method('PUT')
                <h2 class="text-base font-semibold text-slate-950">Treasury details (applies to all vouchers)</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div><label class="form-label">Treasury voucher no.</label><input name="treasury_voucher_no" class="form-input" value="{{ old('treasury_voucher_no', $first->treasury_voucher_no) }}" required></div>
                    <div><label class="form-label">Approval date</label><input type="date" name="treasury_approval_date" class="form-input" value="{{ old('treasury_approval_date', $d($first->treasury_approval_date)) }}" required></div>
                    <div><label class="form-label">Status</label><select name="treasury_status" class="form-select">@foreach(['Approved', 'Returned', 'Pending'] as $s)<option @selected(old('treasury_status', $first->treasury_status) === $s)>{{ $s }}</option>@endforeach</select></div>
                    <div><label class="form-label">ePayment code</label><input name="epayment_code" class="form-input" value="{{ old('epayment_code', $first->epayment_code) }}"></div>
                    <div class="sm:col-span-2"><label class="form-label">UTR no.</label><input name="utr_no" class="form-input" value="{{ old('utr_no', $first->utr_no) }}"></div>
                </div>
                <button class="primary-button" type="submit">Save treasury details</button>
            </form>

            <div class="space-y-5">
                <form method="POST" action="{{ route('division.advices.gst', $adviceNo) }}" class="space-y-3 rounded-lg border border-emerald-100 bg-white p-5">
                    @csrf @method('PUT')
                    <h2 class="text-base font-semibold text-slate-950">GST challan no.</h2>
                    <input name="gst_challan_no" class="form-input" value="{{ old('gst_challan_no', $first->gst_challan_no) }}"><button class="primary-button" type="submit">Save</button>
                </form>
                <form method="POST" action="{{ route('division.advices.numbers', $adviceNo) }}" class="space-y-3 rounded-lg border border-emerald-100 bg-white p-5">
                    @csrf @method('PUT')
                    <h2 class="text-base font-semibold text-slate-950">Change date / bill register / order no.</h2>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div><label class="form-label">Advice date</label><input type="date" name="advice_date" class="form-input" value="{{ $d($first->advice_date) }}" required></div>
                        <div><label class="form-label">Bill register no.</label><input name="bill_register_no" class="form-input" value="{{ $first->bill_register_no }}" required></div>
                        <div><label class="form-label">Order outward no.</label><input name="bill_order_outward_no" class="form-input" value="{{ $first->bill_order_outward_no }}"></div>
                    </div>
                    <button class="primary-button" type="submit">Save</button>
                </form>
                <div class="flex flex-wrap gap-3 rounded-lg border border-emerald-100 bg-white p-5">
                    <form method="POST" action="{{ route('division.advices.final', $adviceNo) }}">@csrf<button class="secondary-button" type="submit">Assign final voucher nos. ({{ $vouchers->whereNull('voucher_no_final')->count() }} missing)</button></form>
                    @unless($locked)
                        <form method="POST" action="{{ route('division.advices.destroy', $adviceNo) }}" onsubmit="return confirm('Delete advice {{ $adviceNo }}? Its vouchers become pending again.')">@csrf @method('DELETE')<button class="secondary-button border-red-200 bg-red-50 text-red-700" type="submit">Delete advice</button></form>
                    @else
                        <span class="self-center text-xs text-slate-500">Approved by the treasury - cannot be deleted.</span>
                    @endunless
                </div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-emerald-100 bg-white">
            <table class="data-table compact-data-table min-w-[60rem]">
                <thead><tr><th>Range</th><th>Type</th><th>Sr.</th><th>Budget</th><th>Party</th><th>Final no.</th><th class="text-right">Payable</th><th class="text-right">Net</th><th></th></tr></thead>
                <tbody>@foreach($vouchers as $v)<tr><td>{{ $v->range?->name }}</td><td>{{ $v->entry_type }}</td><td>{{ $v->serial_number }}</td><td>{{ $v->budget_code }}</td><td class="max-w-[18rem] truncate">{{ $v->party_name }}</td><td>{{ $v->voucher_no_final ?? '-' }}</td><td class="text-right">{{ Format::inr($v->payable_total_amount) }}</td><td class="text-right">{{ Format::inr($v->net_amount) }}</td><td><a class="secondary-button min-h-7 px-2 py-0.5 text-[11px]" href="{{ route('vouchers.show', $v) }}">View</a></td></tr>@endforeach</tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
