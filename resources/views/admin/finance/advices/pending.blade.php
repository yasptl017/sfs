@php use App\Support\Format; @endphp
<x-layouts.admin title="Process bill | Forest Inventory" heading="Process bill (create advice)" subheading="Vouchers received at the division and not yet in an advice">
    <div class="space-y-5">
        <a class="secondary-button" href="{{ route('division.advices.index') }}">&larr; Advices</a>
        @if($errors->any())<div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
        <div class="flex flex-wrap gap-2">
            <a class="{{ $receiveMode ? 'secondary-button' : 'primary-button' }} min-h-8 px-3 py-1 text-xs" href="{{ route('division.advices.pending') }}">Ready to process</a>
            <a class="{{ $receiveMode ? 'primary-button' : 'secondary-button' }} min-h-8 px-3 py-1 text-xs" href="{{ route('division.advices.pending', ['received' => 0]) }}">Not yet received at division ({{ number_format($notReceived) }})</a>
        </div>
        @if($receiveMode)<div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">These vouchers are marked "Not Received" at the division. Mark them received to make them available for an advice.</div>@endif

        <form method="GET" class="grid gap-3 rounded-lg border border-emerald-100 bg-white p-4 md:grid-cols-4">
            <div><label class="form-label">Range</label><select name="range_id" class="form-select"><option value="">All</option>@foreach($ranges as $r)<option value="{{ $r->id }}" @selected(($filters['range_id'] ?? '') == $r->id)>{{ $r->name }}</option>@endforeach</select></div>
            <div><label class="form-label">Payment mode</label><select name="payment_mode" class="form-select"><option value="">All</option>@foreach(['IFMS', 'SNA'] as $p)<option @selected(($filters['payment_mode'] ?? '') === $p)>{{ $p }}</option>@endforeach</select></div>
            <div><label class="form-label">Entry type</label><select name="entry_type" class="form-select"><option value="">All</option>@foreach(['Tender Entry', 'Free Entry', 'D.Wagers Salary', 'D.Wagers Arrears', 'Bene. Entry', 'WL Bene. Entry'] as $t)<option @selected(($filters['entry_type'] ?? '') === $t)>{{ $t }}</option>@endforeach</select></div>
            <div class="flex items-end gap-3"><button class="primary-button" type="submit">Filter</button><a class="secondary-button" href="{{ route('division.advices.pending') }}">Reset</a></div>
        </form>

        <form method="POST" action="{{ $receiveMode ? route('division.advices.receive') : route('division.advices.store') }}" class="space-y-4">
            @csrf
            @if($receiveMode)
            <div class="flex items-center gap-4 rounded-lg border border-emerald-100 bg-white p-4"><span class="text-sm text-slate-600" id="selInfo">0 selected</span><button class="primary-button" type="submit">Mark selected as received</button></div>
            @else
            <div class="grid gap-4 rounded-lg border border-emerald-100 bg-white p-4 md:grid-cols-4">
                <div><label class="form-label" for="bill_type">Bill type</label><select id="bill_type" name="bill_type" class="form-select" required>@foreach($billTypes as $t)<option>{{ $t }}</option>@endforeach</select></div>
                <div><label class="form-label" for="advice_date">Advice date</label><input id="advice_date" name="advice_date" type="date" class="form-input" value="{{ now()->toDateString() }}" required></div>
                <div class="md:col-span-2"><label class="form-label" for="oo">Bill order outward no. (optional)</label><input id="oo" name="bill_order_outward_no" class="form-input"></div>
                <div class="md:col-span-4 flex items-center gap-4"><span class="text-sm text-slate-600" id="selInfo">0 selected</span><button class="primary-button" type="submit">Create advice from selected vouchers</button></div>
            </div>
            @endif
            <div class="overflow-x-auto rounded-lg border border-emerald-100 bg-white">
                <table class="data-table compact-data-table min-w-[70rem]">
                    <thead><tr><th><input type="checkbox" id="all"></th><th>Range</th><th>Type</th><th>Sr.</th><th>Month</th><th>Budget</th><th>Party</th><th>Mode</th><th class="text-right">Payable</th><th class="text-right">Net</th><th></th></tr></thead>
                    <tbody>
                        @forelse($vouchers as $v)
                            <tr><td><input type="checkbox" name="voucher_ids[]" value="{{ $v->id }}" data-pick data-amt="{{ $v->payable_total_amount }}"></td><td>{{ $v->range?->name }}</td><td>{{ $v->entry_type }}</td><td>{{ $v->serial_number }}</td><td>{{ $v->entry_month }}</td><td>{{ $v->budget_code }}</td>
                                <td class="max-w-[16rem] truncate">{{ $v->party_name }}</td><td>{{ $v->payment_mode }}</td><td class="text-right">{{ Format::inr($v->payable_total_amount) }}</td><td class="text-right">{{ Format::inr($v->net_amount) }}</td>
                                <td><a class="secondary-button min-h-7 px-2 py-0.5 text-[11px]" href="{{ route('vouchers.show', $v) }}" target="_blank">View</a></td></tr>
                        @empty
                            <tr><td colspan="11" class="text-center">{{ $receiveMode ? 'No vouchers are waiting to be received.' : 'Nothing is ready to be processed.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($vouchers->hasPages())<div class="px-1 py-2">{{ $vouchers->links() }}</div>@endif
        </form>
    </div>
    <script>
        const picks = () => [...document.querySelectorAll('[data-pick]')];
        const info = () => { const c = picks().filter((x) => x.checked); document.getElementById('selInfo').textContent = c.length + ' selected - payable ' + c.reduce((s, x) => s + parseFloat(x.dataset.amt || 0), 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }); };
        document.getElementById('all').addEventListener('change', (e) => { picks().forEach((x) => x.checked = e.target.checked); info(); });
        document.addEventListener('change', (e) => { if (e.target.matches('[data-pick]')) info(); });
    </script>
</x-layouts.admin>
