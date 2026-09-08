@php
    $isEdit = filled($entry);
    $field = fn (string $key, mixed $default = '') => old("data.$key", $entry?->data[$key] ?? $default);
    $items = old('data.items', $entry?->data['items'] ?? [[]]);
    if (empty($items)) {
        $items = [[]];
    }
    $itemField = fn (int $i, string $key, mixed $default = '') => old("data.items.$i.$key", $items[$i][$key] ?? $default);
@endphp
<x-layouts.admin title="{{ $isEdit ? 'Edit Free Entry' : 'Free Entry' }} | Forest Inventory" heading="{{ $isEdit ? 'Edit Free Entry' : 'Free Entry' }}" subheading="Range finance voucher entry">
    <form id="freeEntryForm" class="space-y-5" method="POST" action="{{ $isEdit ? route('finance.free-entries.update', $entry) : route('finance.free-entries.store') }}"
        data-free-entry-form
        data-copy-url="{{ url('finance/free-entry/copy') }}"
        data-redirect-url="{{ route('finance.free-entries.index') }}"
        data-budget-codes="{{ $budgetCodes->mapWithKeys(fn ($b) => [$b->budget_code => ['scheme' => $b->scheme, 'model' => $b->model, 'scheme_year' => $b->scheme_year]])->toJson() }}"
        data-beats-by-round="{{ json_encode($beatsByRound) }}"
        data-places-by-beat="{{ json_encode($placesByBeat) }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <div id="freeEntryFormAlert" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold"></div>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="entry_sr_no">Sr. No. (of this Free Entry)</label>
                    <input id="entry_sr_no" class="form-input" name="entry_sr_no" value="{{ $isEdit ? $entry->serial_number : $next }}" readonly required data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="entry_type">Entry Type</label>
                    <input id="entry_type" class="form-input" name="data[entry_type]" value="{{ $field('entry_type') }}" data-entry-field>
                </div>
            </div>
        </section>

        @unless($isEdit)
            <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Copy or start new entry</h2>
                    <p class="mt-1 text-sm text-slate-500">Enter Voucher number to copy and click 'Copy Entry' button, or do totally new entry.</p>
                </div>
                <div class="grid gap-4 p-5 lg:grid-cols-[1fr_1fr_auto]">
                    <div>
                        <label class="form-label" for="copy_entry_sr_no">Voucher no to copy</label>
                        <input id="copy_entry_sr_no" class="form-input" name="copy_entry_sr_no" placeholder="Enter existing Sr. No.">
                    </div>
                    <div>
                        <label class="form-label" for="edit_copied_entry">Edit copied entry?</label>
                        <label class="toggle-field">
                            <input id="edit_copied_entry" type="checkbox" data-edit-copied-entry>
                            <span></span>
                            <strong>Enable editing after copy</strong>
                        </label>
                    </div>
                    <div class="flex items-end">
                        <button class="secondary-button w-full lg:w-auto" type="button" data-copy-entry>Copy Entry</button>
                    </div>
                </div>
            </section>
        @endunless

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Budget &amp; scheme</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="tender_code">Tender Code</label>
                    <input id="tender_code" class="form-input" name="data[tender_code]" value="{{ $field('tender_code') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="budget_code">Budget Code</label>
                    <select id="budget_code" class="form-select" name="data[budget_code]" data-entry-field data-budget-code-select>
                        <option value="" @selected(blank($field('budget_code'))) disabled>Choose...</option>
                        @foreach($budgetCodes as $budgetCode)
                            <option @selected($field('budget_code') === $budgetCode->budget_code)>{{ $budgetCode->budget_code }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500" data-remaining-allotment>Remaining Allotment = Select Budget Code</p>
                </div>
                <div class="md:col-span-2 xl:col-span-2">
                    <label class="form-label">Scheme, Model &amp; Scheme Year</label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        <input class="form-input" name="data[scheme]" value="{{ $field('scheme') }}" placeholder="Scheme" readonly data-entry-field data-scheme-field>
                        <input class="form-input" name="data[model]" value="{{ $field('model') }}" placeholder="Model" readonly data-entry-field data-model-field>
                        <input class="form-input" name="data[scheme_year]" value="{{ $field('scheme_year') }}" placeholder="Scheme Year" readonly data-entry-field data-scheme-year-field>
                    </div>
                </div>
                <div>
                    <label class="form-label" for="data_entry_month">Data Entry Month</label>
                    <input id="data_entry_month" class="form-input" name="data[data_entry_month]" value="{{ $field('data_entry_month') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="docket_no">Docket No.</label>
                    <input id="docket_no" class="form-input" name="data[docket_no]" value="{{ $field('docket_no') }}" data-entry-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Location</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="round">Round</label>
                    <select id="round" class="form-select" name="data[round]" data-entry-field data-round-select>
                        <option value="" @selected(blank($field('round'))) disabled>Choose...</option>
                        @foreach($rounds as $r)
                            <option @selected($field('round') === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="beat">Beat</label>
                    <select id="beat" class="form-select" name="data[beat]" data-entry-field data-beat-select>
                        <option value="" @selected(blank($field('beat'))) disabled>Choose...</option>
                        @if($field('beat'))
                            <option selected>{{ $field('beat') }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="form-label" for="place">Place</label>
                    <select id="place" class="form-select" name="data[place]" data-entry-field data-place-select>
                        <option value="" @selected(blank($field('place'))) disabled>Choose...</option>
                        @if($field('place'))
                            <option selected>{{ $field('place') }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="form-label" for="area">Total Area</label>
                    <input id="area" class="form-input" name="data[area]" value="{{ $field('area') }}" data-entry-field>
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <label class="form-label" for="description">Description</label>
                    <textarea id="description" class="form-textarea" name="data[description]" rows="2" maxlength="150" data-entry-field>{{ $field('description') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Maximum characters: 150</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Bill details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="bill_no">Bill No.</label>
                    <input id="bill_no" class="form-input" name="data[bill_no]" value="{{ $field('bill_no') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="bill_date">Bill Date</label>
                    <input id="bill_date" class="form-input" name="data[bill_date]" value="{{ $field('bill_date') }}" type="date" data-entry-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Party details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="party_code">Party Code</label>
                    <input id="party_code" class="form-input" name="data[party_code]" value="{{ $field('party_code') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="party_name">Party Name</label>
                    <input id="party_name" class="form-input" name="data[party_name]" value="{{ $field('party_name') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="pan_card_no">Pan Card No.</label>
                    <input id="pan_card_no" class="form-input" name="data[pan_card_no]" value="{{ $field('pan_card_no') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="gst_no">GST No.</label>
                    <input id="gst_no" class="form-input" name="data[gst_no]" value="{{ $field('gst_no') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="account_no">Account No.</label>
                    <input id="account_no" class="form-input" name="data[account_no]" value="{{ $field('account_no') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="bank_name">Bank Name</label>
                    <input id="bank_name" class="form-input" name="data[bank_name]" value="{{ $field('bank_name') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="ifsc">IFSC</label>
                    <input id="ifsc" class="form-input" name="data[ifsc]" value="{{ $field('ifsc') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="branch">Branch</label>
                    <input id="branch" class="form-input" name="data[branch]" value="{{ $field('branch') }}" data-entry-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Deadstock register</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="register_name">Register Name For Deadstock Entry</label>
                    <input id="register_name" class="form-input" name="data[register_name]" value="{{ $field('register_name') }}" placeholder="1st letter = D" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="register_page_no">Register Page No.</label>
                    <input id="register_page_no" class="form-input" name="data[register_page_no]" value="{{ $field('register_page_no') }}" data-entry-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm" data-work-items>
            <div class="flex items-center justify-between border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Work items</h2>
                <button class="secondary-button" type="button" data-add-item>Add Item</button>
            </div>
            <div data-item-rows>
                @foreach($items as $i => $item)
                    <div class="border-b border-emerald-100 p-5 last:border-b-0" data-item-row data-item-index="{{ $i }}">
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-slate-950" data-item-title>{{ $i + 1 }}. Work item</h3>
                            @if($i > 0)
                                <button class="secondary-button min-h-7 border-red-200 bg-red-50 px-1.5 py-0.5 text-[10px] text-red-700" type="button" data-remove-item>Remove</button>
                            @endif
                        </div>
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="md:col-span-2 xl:col-span-2">
                                <label class="form-label">Work Description</label>
                                <input class="form-input" name="data[items][{{ $i }}][work_description]" value="{{ $itemField($i, 'work_description') }}" data-item-field>
                            </div>
                            <div>
                                <label class="form-label">No. of Unit</label>
                                <input class="form-input" name="data[items][{{ $i }}][no_of_unit]" value="{{ $itemField($i, 'no_of_unit', '0.00000') }}" type="number" min="0" step="any" data-item-field data-item-qty>
                            </div>
                            <div>
                                <label class="form-label">Rate</label>
                                <input class="form-input" name="data[items][{{ $i }}][rate]" value="{{ $itemField($i, 'rate', '0.00000') }}" type="number" min="0" step="any" data-item-field data-item-rate>
                            </div>
                            <div>
                                <label class="form-label">Amount</label>
                                <input class="form-input bg-stone-50" name="data[items][{{ $i }}][amount]" value="{{ $itemField($i, 'amount') }}" readonly data-item-field data-item-amount>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-emerald-100 px-5 py-4">
                <span class="text-sm font-semibold text-slate-950">Sub Total</span>
                <input class="form-input w-40 bg-stone-50 text-right font-semibold" readonly data-sub-total value="0">
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Approval &amp; totals</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div class="md:col-span-2 xl:col-span-4">
                    <label class="form-label" for="remarks_rfo">Remarks by RFO (if any)</label>
                    <textarea id="remarks_rfo" class="form-textarea" name="data[remarks_rfo]" rows="2" maxlength="150" data-entry-field>{{ $field('remarks_rfo') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Maximum characters: 150</p>
                </div>
                <div>
                    <label class="form-label" for="additional_sgst">Additional SGST</label>
                    <input id="additional_sgst" class="form-input" name="data[additional_sgst]" value="{{ $field('additional_sgst', '0.00') }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="additional_cgst">Additional CGST</label>
                    <input id="additional_cgst" class="form-input" name="data[additional_cgst]" value="{{ $field('additional_cgst', '0.00') }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="additional_igst">Additional IGST</label>
                    <input id="additional_igst" class="form-input" name="data[additional_igst]" value="{{ $field('additional_igst', '0.00') }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="adjustment">Adjustment (Plus or Minus)</label>
                    <input id="adjustment" class="form-input" name="data[adjustment]" value="{{ $field('adjustment', '0.00000') }}" type="number" step="any" data-entry-field data-total-input>
                    <p class="mt-1 text-xs text-slate-500">If discount then keep minus sign</p>
                </div>
                <div>
                    <label class="form-label">Total Amount</label>
                    <input class="form-input bg-stone-50" readonly data-total-amount value="0">
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <p class="text-sm text-slate-500" data-remaining-allotment-2>Remaining Allotment: -</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-emerald-100 bg-amber-50 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Deduction</h2>
                <label class="toggle-field">
                    <input type="checkbox" data-toggle-salary-deductions>
                    <span></span>
                    <strong>Show salary deductions</strong>
                </label>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="deduction_sgst">Deduction SGST</label>
                    <input id="deduction_sgst" class="form-input bg-amber-50" name="data[deduction_sgst]" value="{{ $field('deduction_sgst', 0) }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="deduction_cgst">Deduction CGST</label>
                    <input id="deduction_cgst" class="form-input bg-amber-50" name="data[deduction_cgst]" value="{{ $field('deduction_cgst', 0) }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="deduction_igst">Deduction IGST</label>
                    <input id="deduction_igst" class="form-input bg-amber-50" name="data[deduction_igst]" value="{{ $field('deduction_igst', 0) }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div data-salary-deduction hidden>
                    <label class="form-label" for="tds">TDS</label>
                    <input id="tds" class="form-input bg-amber-50" name="data[tds]" value="{{ $field('tds', 0) }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div data-salary-deduction hidden>
                    <label class="form-label" for="labour_cess">Labour Cess</label>
                    <input id="labour_cess" class="form-input bg-amber-50" name="data[labour_cess]" value="{{ $field('labour_cess', 0) }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label">Total Deduction</label>
                    <input class="form-input bg-stone-50" readonly data-total-deduction value="0">
                </div>
                <div>
                    <label class="form-label">Net Amount</label>
                    <input class="form-input bg-stone-50 font-semibold" readonly data-net-amount value="0">
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="flex flex-wrap justify-end gap-3 p-5">
                <button class="secondary-button" type="reset">Clear</button>
                <a class="secondary-button" href="{{ route('finance.free-entries.index') }}">View Details</a>
                <button class="primary-button" type="submit">{{ $isEdit ? 'Update' : 'Submit' }}</button>
            </div>
        </section>
    </form>
</x-layouts.admin>
