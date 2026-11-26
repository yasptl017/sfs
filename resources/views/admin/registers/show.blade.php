@php use App\Support\Format; @endphp
<x-layouts.admin title="{{ $def['title'] }} | Forest Inventory" heading="{{ $def['title'] }}" subheading="Record details - financial year {{ \App\Support\CurrentFinancialYear::model()->code }}">
    <div class="space-y-5">
        <a class="secondary-button" href="{{ route('registers.index', $key) }}">&larr; Back to {{ strtolower($def['title']) }}</a>
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <dl class="grid gap-x-6 gap-y-3 p-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($fields as $label => $value)
                    @php($text = is_numeric($value) && !is_string($value) ? rtrim(rtrim(number_format((float) $value, 6, '.', ''), '0'), '.') : (string) $value)
                    <div class="min-w-0 @if(mb_strlen($text) > 80) sm:col-span-2 xl:col-span-3 @endif">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</dt>
                        <dd class="mt-0.5 whitespace-pre-line break-words text-sm text-slate-900">{{ $text }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
        @if($children)
            <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">{{ $children['title'] }}</h2></div>
                <table class="data-table compact-data-table">
                    <thead><tr>@foreach($children['columns'] as $c)<th>{{ $c[0] }}</th>@endforeach</tr></thead>
                    <tbody>
                        @foreach($children['rows'] as $r)
                            <tr>@foreach($children['columns'] as $c)<td>{{ ($c[2] ?? '') === 'money' ? Format::inr($r->{$c[1]}) : (($c[2] ?? '') === 'date' ? \Illuminate\Support\Carbon::parse($r->{$c[1]})->format('d-m-Y') : $r->{$c[1]}) }}</td>@endforeach</tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    </div>
</x-layouts.admin>
