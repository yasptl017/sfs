@php use App\Support\Format; @endphp
<x-layouts.admin title="Vouchers | Forest Inventory" heading="Vouchers" subheading="All voucher entries - financial year {{ \App\Support\CurrentFinancialYear::model()->code }}">
    <div class="space-y-5">
        @if(auth()->user()->isRange())
            <div class="flex flex-wrap gap-2">
                @foreach(['tender-entries' => 'Tender Entry', 'free-entries' => 'Free Entry', 'd-wager-salary-entries' => 'D.Wagers Salary', 'd-wager-arrears-entries' => 'D.Wagers Arrears', 'sf-beneficiary-entries' => 'SF Bene. Entry', 'wl-beneficiary-entries' => 'WL Bene. Entry'] as $pl => $lab)
                    <a class="primary-button min-h-8 px-3 py-1 text-xs" href="{{ route('finance.'.$pl.'.create') }}">+ New {{ $lab }}</a>
                @endforeach
            </div>
        @endif

        <form method="GET" class="grid gap-3 rounded-lg border border-emerald-100 bg-white p-4 md:grid-cols-3 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label class="form-label" for="q">Search</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-input" placeholder="Party, bill no., description, advice no., voucher no...">
            </div>
            <div>
                <label class="form-label" for="entry_type">Entry type</label>
                <select id="entry_type" name="entry_type" class="form-select">
                    <option value="">All</option>
                    @foreach($entryTypes as $t)<option value="{{ $t }}" @selected(($filters['entry_type'] ?? '') === $t)>{{ $t }}</option>@endforeach
                </select>
            </div>
            @if($ranges->isNotEmpty())
                <div>
                    <label class="form-label" for="range_id">Range / office</label>
                    <select id="range_id" name="range_id" class="form-select">
                        <option value="">All</option>
                        @foreach($ranges as $r)<option value="{{ $r->id }}" @selected(($filters['range_id'] ?? '') == $r->id)>{{ $r->name }}</option>@endforeach
                    </select>
                </div>
            @endif
            <div>
                <label class="form-label" for="month">Entry month</label>
                <select id="month" name="month" class="form-select">
                    <option value="">All</option>
                    @foreach($months as $m)<option @selected(($filters['month'] ?? '') === $m)>{{ $m }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="form-label" for="budget_code">Budget code</label>
                <input id="budget_code" name="budget_code" value="{{ $filters['budget_code'] ?? '' }}" class="form-input" placeholder="e.g. A3">
            </div>
            <div>
                <label class="form-label" for="treasury_status">Treasury status</label>
                <select id="treasury_status" name="treasury_status" class="form-select">
                    <option value="">All</option>
                    <option value="__none" @selected(($filters['treasury_status'] ?? '') === '__none')>Not yet at treasury</option>
                    @foreach($statuses as $s)<option @selected(($filters['treasury_status'] ?? '') === $s)>{{ $s }}</option>@endforeach
                </select>
            </div>
            <div class="flex items-end gap-3 md:col-span-3 xl:col-span-2">
                <button class="primary-button" type="submit">Apply</button>
                <a class="secondary-button" href="{{ route('vouchers.index') }}">Reset</a>
                @if($olderCount)
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" name="only_older" value="1" @checked(!empty($filters['only_older']))> Only in older export ({{ $olderCount }})
                    </label>
                @endif
            </div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach([['Vouchers', number_format($totals->c)], ['Sub total', Format::inr($totals->sub)], ['Payable total', Format::inr($totals->pay)], ['Total deduction', Format::inr($totals->ded)], ['Net amount', Format::inr($totals->net)]] as [$label, $val])
                <div class="rounded-lg border border-emerald-100 bg-white px-4 py-3">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</div>
                    <div class="mt-1 text-lg font-bold text-slate-950">{{ $val }}</div>
                </div>
            @endforeach
        </div>

        <div class="overflow-x-auto rounded-lg border border-emerald-100 bg-white">
            <table class="data-table compact-data-table min-w-[90rem]">
                <thead>
                    <tr>
                        @if($ranges->isNotEmpty())<th>Office</th>@endif
                        <th>Type</th><th>Sr.</th><th>Month</th><th>Budget</th><th>Bill no.</th><th>Bill date</th><th>Party</th>
                        <th class="text-right">Payable</th><th class="text-right">Net</th><th>Advice</th><th>Treasury</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vouchers as $v)
                        <tr>
                            @if($ranges->isNotEmpty())<td>{{ $v->range?->name }}</td>@endif
                            <td>{{ $v->entry_type }}@if($v->only_in_older_export) <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800" title="Present in an older export, not in the latest register">older export only</span>@endif</td>
                            <td>{{ $v->serial_number }}</td>
                            <td>{{ $v->entry_month ?? '-' }}</td>
                            <td>{{ $v->budget_code }}</td>
                            <td>{{ $v->bill_no ?? '-' }}</td>
                            <td>{{ $v->bill_date ? \Illuminate\Support\Carbon::parse($v->bill_date)->format('d-m-Y') : '-' }}</td>
                            <td class="max-w-[16rem] truncate" title="{{ $v->party_name }}">{{ $v->party_name }}</td>
                            <td class="text-right">{{ Format::inr($v->payable_total_amount) }}</td>
                            <td class="text-right">{{ Format::inr($v->net_amount) }}</td>
                            <td>{{ $v->advice_no ?? '-' }}</td>
                            <td>{{ $v->treasury_status ?? '-' }}</td>
                            <td><a class="secondary-button min-h-7 px-2 py-0.5 text-[11px]" href="{{ route('vouchers.show', $v) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="text-center">No vouchers match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vouchers->hasPages())
            <div class="px-1 py-2">{{ $vouchers->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
