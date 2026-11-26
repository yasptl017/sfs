@php
    use App\Support\Format;
    $cell = function ($row, array $col) {
        $v = $row->{$col[1]} ?? null;
        if ($v === null || $v === '') return '-';
        return match ($col[2] ?? 'text') {
            'money' => Format::inr($v),
            'date' => preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $v) ? \Illuminate\Support\Carbon::parse($v)->format('d-m-Y') : $v,
            'num' => rtrim(rtrim(number_format((float) $v, 4, '.', ''), '0'), '.'),
            default => $v,
        };
    };
@endphp
<x-layouts.admin title="{{ $def['title'] }} | Forest Inventory" heading="{{ $def['title'] }}" subheading="Financial year {{ \App\Support\CurrentFinancialYear::model()->code }} - {{ number_format($totals['_count']) }} records">
    <div class="space-y-5">
        <div class="flex flex-wrap gap-2">
            @foreach($menu as $group => $items)
                @foreach($items as $k => $title)
                    <a class="{{ $k === $key ? 'primary-button' : 'secondary-button' }} min-h-8 px-3 py-1 text-xs" href="{{ route('registers.index', $k) }}">{{ $title }}</a>
                @endforeach
            @endforeach
        </div>

        <form method="GET" class="grid gap-3 rounded-lg border border-emerald-100 bg-white p-4 md:grid-cols-3 xl:grid-cols-6">
            <div class="xl:col-span-2"><label class="form-label" for="q">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-input" placeholder="Search {{ strtolower($def['title']) }}..."></div>
            @if($rangeOptions->isNotEmpty())
                <div><label class="form-label" for="range_id">Office</label>
                    <select id="range_id" name="range_id" class="form-select"><option value="">All</option>@foreach($rangeOptions as $r)<option value="{{ $r->id }}" @selected(($filters['range_id'] ?? '') == $r->id)>{{ $r->name }}</option>@endforeach</select></div>
            @endif
            @foreach($def['filters'] as $col => $label)
                <div><label class="form-label" for="f_{{ $col }}">{{ $label }}</label>
                    <select id="f_{{ $col }}" name="f_{{ $col }}" class="form-select"><option value="">All</option>@foreach($filterOptions[$col] as $o)<option @selected(($filters['f_'.$col] ?? '') === (string) $o)>{{ $o }}</option>@endforeach</select></div>
            @endforeach
            <div class="flex items-end gap-3"><button class="primary-button" type="submit">Apply</button><a class="secondary-button" href="{{ route('registers.index', $key) }}">Reset</a></div>
        </form>

        @if(!empty($def['sum']))
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-lg border border-emerald-100 bg-white px-4 py-3"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Records</div><div class="mt-1 text-lg font-bold text-slate-950">{{ number_format($totals['_count']) }}</div></div>
                @foreach($def['sum'] as $col)
                    @php($lab = collect($def['columns'])->firstWhere(1, $col)[0] ?? ucfirst(str_replace('_', ' ', $col)))
                    <div class="rounded-lg border border-emerald-100 bg-white px-4 py-3"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total - {{ $lab }}</div><div class="mt-1 text-lg font-bold text-slate-950">{{ Format::inr($totals[$col] ?? 0) }}</div></div>
                @endforeach
            </div>
        @endif

        <div class="overflow-x-auto rounded-lg border border-emerald-100 bg-white">
            <table class="data-table compact-data-table min-w-[70rem]">
                <thead><tr>@foreach($def['columns'] as $col)<th class="{{ in_array($col[2] ?? '', ['money']) ? 'text-right' : '' }}">{{ $col[0] }}</th>@endforeach @if(($def['detail'] ?? true) !== false)<th></th>@endif</tr></thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            @foreach($def['columns'] as $col)
                                <td class="{{ in_array($col[2] ?? '', ['money']) ? 'text-right' : '' }} max-w-[22rem] truncate" title="{{ $row->{$col[1]} ?? '' }}">{{ $cell($row, $col) }}</td>
                            @endforeach
                            @if(($def['detail'] ?? true) !== false)<td><a class="secondary-button min-h-7 px-2 py-0.5 text-[11px]" href="{{ route('registers.show', [$key, $row->id]) }}">View</a></td>@endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($def['columns']) + 1 }}" class="text-center">No records match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())<div class="px-1 py-2">{{ $rows->links() }}</div>@endif
    </div>
</x-layouts.admin>
