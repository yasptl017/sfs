@php
    $isEdit = filled($entry);
    $field = fn (string $key) => old($key, $entry?->data[$key] ?? '');
    $details = ['operating_head' => 'Operating Head', 'scheme' => 'Scheme', 'object_class' => 'Object Class', 'object_code' => 'Object Code', 'description' => 'Description', 'item' => 'Item', 'model' => 'Model', 'scheme_year' => 'Scheme Year'];
@endphp
<x-layouts.admin title="{{ $isEdit ? 'Edit Allotment from Circle' : 'Allotment from Circle' }} | Forest Inventory" heading="{{ $isEdit ? 'Edit Allotment from Circle' : 'Allotment from Circle' }}" subheading="Division budget allotment entry">
    <form class="space-y-5" method="POST" action="{{ $isEdit ? route('division.allotments-from-circle.update', $entry) : route('division.allotments-from-circle.store') }}" data-allotment-from-circle-form data-budget-code-details="{{ $budgetCodeDetails->toJson() }}">
        @csrf
        @if($isEdit) @method('PUT') @endif
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4"><h2 class="text-base font-semibold text-slate-950">Budget code details</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="budget_code">Select Budget Code</label>
                    <select id="budget_code" class="form-select" name="budget_code" required data-allotment-budget-code><option value="" disabled @selected(blank($field('budget_code')))>Choose...</option>@foreach($budgetCodes as $budgetCode)<option value="{{ $budgetCode->budget_code }}" @selected($field('budget_code') === $budgetCode->budget_code)>{{ $budgetCode->budget_code }}</option>@endforeach</select>
                    @error('budget_code')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                @foreach($details as $key => $label)
                    <div><label class="form-label" for="{{ $key }}">{{ $label }}</label><input id="{{ $key }}" class="form-input bg-slate-50" value="{{ $field($key) }}" readonly data-budget-detail="{{ $key }}"></div>
                @endforeach
            </div>
        </section>
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4"><h2 class="text-base font-semibold text-slate-950">Allotment details</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-3">
                <div><label class="form-label" for="allotment_date">Allotment Date</label><input id="allotment_date" class="form-input" name="allotment_date" type="date" value="{{ old('allotment_date', $entry?->data['allotment_date'] ?? now()->toDateString()) }}" required>@error('allotment_date')<p class="form-error">{{ $message }}</p>@enderror</div>
                @foreach(['rate' => 'Rate', 'target' => 'Target', 'allotment' => 'Allotment'] as $key => $label)
                    <div><label class="form-label" for="{{ $key }}">{{ $label }}</label><input id="{{ $key }}" class="form-input" name="{{ $key }}" type="number" min="0" step="0.01" value="{{ $field($key) }}" required>@error($key)<p class="form-error">{{ $message }}</p>@enderror</div>
                @endforeach
            </div>
        </section>
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm"><div class="flex flex-wrap justify-end gap-3 p-5"><a class="secondary-button" href="{{ route('division.allotments-from-circle.index') }}">View Details</a><button class="primary-button" type="submit">{{ $isEdit ? 'Update' : 'Submit' }}</button></div></section>
    </form>
</x-layouts.admin>
