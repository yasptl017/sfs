@php($isEdit = filled($budgetCode))
<x-layouts.admin title="{{ $isEdit ? 'Edit Budget Code' : 'Add Budget Code' }} | Forest Inventory" heading="{{ $isEdit ? 'Edit Budget Code' : 'Add Budget Code' }}" subheading="Budget code configuration">
    <form class="space-y-5" method="POST" action="{{ $isEdit ? route('admin.budget-codes.update', $budgetCode) : route('admin.budget-codes.store') }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Budget code details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="budget_code">Budget Code</label>
                    <input id="budget_code" class="form-input" name="budget_code" value="{{ old('budget_code', $budgetCode?->budget_code) }}" required>
                    @error('budget_code')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="operating_head">Operating Head</label>
                    <input id="operating_head" class="form-input" name="operating_head" value="{{ old('operating_head', $budgetCode?->operating_head) }}">
                    @error('operating_head')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="scheme">Scheme</label>
                    <input id="scheme" class="form-input" name="scheme" value="{{ old('scheme', $budgetCode?->scheme) }}">
                    @error('scheme')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="object_class">Object Class</label>
                    <input id="object_class" class="form-input" name="object_class" value="{{ old('object_class', $budgetCode?->object_class) }}">
                    @error('object_class')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="object_code">Object Code</label>
                    <input id="object_code" class="form-input" name="object_code" value="{{ old('object_code', $budgetCode?->object_code) }}">
                    @error('object_code')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="item">Item</label>
                    <input id="item" class="form-input" name="item" value="{{ old('item', $budgetCode?->item) }}">
                    @error('item')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="model">Model</label>
                    <input id="model" class="form-input" name="model" value="{{ old('model', $budgetCode?->model) }}">
                    @error('model')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="form-label" for="scheme_year">Scheme Year</label>
                    <input id="scheme_year" class="form-input" name="scheme_year" value="{{ old('scheme_year', $budgetCode?->scheme_year) }}" placeholder="e.g. 2025-26">
                    @error('scheme_year')<p class="form-error">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <label class="form-label" for="description">Description</label>
                    <input id="description" class="form-input" name="description" value="{{ old('description', $budgetCode?->description) }}">
                    @error('description')<p class="form-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="flex flex-wrap justify-end gap-3 p-5">
                <a class="secondary-button" href="{{ route('admin.budget-codes.index') }}">Cancel</a>
                <button class="primary-button" type="submit">{{ $isEdit ? 'Update' : 'Submit' }}</button>
            </div>
        </section>
    </form>
</x-layouts.admin>
