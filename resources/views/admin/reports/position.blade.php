@php use App\Support\Format; @endphp
<x-layouts.admin title="Allotment vs expenditure | Forest Inventory" heading="Allotment vs expenditure" subheading="Financial year {{ \App\Support\CurrentFinancialYear::model()->code }} - per budget code{{ auth()->user()->isRange() ? '' : ' and office' }}">
    <div class="space-y-5">
        <form method="GET" class="grid gap-3 rounded-lg border border-emerald-100 bg-white p-4 md:grid-cols-4">
            <div><label class="form-label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-input" placeholder="Budget code, scheme, item, model"></div>
            @if($ranges->isNotEmpty())<div><label class="form-label" for="range_id">Office</label><select id="range_id" name="range_id" class="form-select"><option value="">All</option>@foreach($ranges as $r)<option value="{{ $r->id }}" @selected(($filters['range_id'] ?? '') == $r->id)>{{ $r->name }}</option>@endforeach</select></div>@endif
            <div class="flex items-end"><label class="flex items-center gap-2 text-sm text-slate-700"><input type="checkbox" name="overspent" value="1" @checked(!empty($filters['overspent']))> Only where entered exceeds allotment</label></div>
            <div class="flex items-end gap-3"><button class="primary-button" type="submit">Apply</button><a class="secondary-button" href="{{ route('reports.position') }}">Reset</a></div>
        </form>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([['Allotment to office', $totals['allotment']], ['Data entered (not rejected)', $totals['entered']], ['Expenditure booked (in advice)', $totals['booked']], ['Remaining allotment', $totals['remaining']]] as [$l, $v])
                <div class="rounded-lg border border-emerald-100 bg-white px-4 py-3"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $l }}</div><div class="mt-1 text-lg font-bold {{ $v < 0 ? 'text-red-700' : 'text-slate-950' }}">{{ Format::inr($v) }}</div></div>
            @endforeach
        </div>
        @if($olderAmount > 0)
            <p class="text-xs text-slate-600">"Data entered" includes {{ Format::inr($olderAmount) }} from vouchers flagged "only in older export". The spreadsheet's own figure does not include them.</p>
        @endif

        <div class="overflow-x-auto rounded-lg border border-emerald-100 bg-white">
            <table class="data-table compact-data-table min-w-[70rem]">
                <thead><tr>@unless(auth()->user()->isRange())<th>Office</th>@endunless<th>Code</th><th>Scheme</th><th>Item</th><th>Model</th><th class="text-right">Allotment</th><th class="text-right">Entered</th><th class="text-right">Booked</th><th class="text-right">Remaining</th></tr></thead>
                <tbody>
                    @forelse($rows as $r)
                        <tr>@unless(auth()->user()->isRange())<td>{{ $r->range_name }}</td>@endunless<td class="font-semibold">{{ $r->budget_code }}</td><td class="max-w-[14rem] truncate" title="{{ $r->scheme }}">{{ $r->scheme }}</td><td class="max-w-[12rem] truncate" title="{{ $r->item }}">{{ $r->item }}</td><td class="max-w-[16rem] truncate" title="{{ $r->model }}">{{ $r->model }}</td>
                            <td class="text-right">{{ Format::inr($r->allotment) }}</td><td class="text-right">{{ Format::inr($r->entered) }}</td><td class="text-right">{{ Format::inr($r->booked) }}</td><td class="text-right {{ $r->remaining < -0.5 ? 'font-semibold text-red-700' : '' }}">{{ Format::inr($r->remaining) }}</td></tr>
                    @empty
                        <tr><td colspan="9" class="text-center">Nothing to show.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="px-1 py-2">{{ $rows->links() }}</div>@endif
    </div>
</x-layouts.admin>
