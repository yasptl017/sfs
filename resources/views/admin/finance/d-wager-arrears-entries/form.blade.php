@php
    $isEdit = filled($entry);
    $field = fn (string $key, mixed $default = '') => old("data.$key", $entry?->data[$key] ?? $default);
@endphp
<x-layouts.admin title="{{ $isEdit ? 'Edit D. Wagers Arrears' : 'D. Wagers Arrears' }} | Forest Inventory" heading="{{ $isEdit ? 'Edit D. Wagers Arrears' : 'D. Wagers Arrears' }}" subheading="Range finance daily wager arrears entry">
    <form id="dWagerArrearsForm" class="space-y-5" method="POST" action="{{ $isEdit ? route('finance.d-wager-arrears-entries.update', $entry) : route('finance.d-wager-arrears-entries.store') }}"
        data-d-wager-arrears-form
        data-copy-url="{{ url('finance/d-wager-arrears-entry/copy') }}"
        data-redirect-url="{{ route('finance.d-wager-arrears-entries.index') }}"
        data-budget-codes="{{ $budgetCodes->mapWithKeys(fn ($b) => [$b->budget_code => ['scheme' => $b->scheme, 'model' => $b->model, 'scheme_year' => $b->scheme_year]])->toJson() }}"
        data-beats-by-round="{{ json_encode($beatsByRound) }}"
        data-places-by-beat="{{ json_encode($placesByBeat) }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <div id="dWagerArrearsFormAlert" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold"></div>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="entry_sr_no">Sr. No. (of this D.Wagers Arrears)</label>
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
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">D. Wager details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="d_wager_code">D. Wager Code</label>
                    <input id="d_wager_code" class="form-input" name="data[d_wager_code]" value="{{ $field('d_wager_code') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="d_wager_name">D.Wager Name</label>
                    <input id="d_wager_name" class="form-input" name="data[d_wager_name]" value="{{ $field('d_wager_name') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="pan_card_no">Pan Card No.</label>
                    <input id="pan_card_no" class="form-input" name="data[pan_card_no]" value="{{ $field('pan_card_no') }}" data-entry-field>
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
                <div>
                    <label class="form-label" for="pay_month">Pay Month</label>
                    <input id="pay_month" class="form-input" name="data[pay_month]" value="{{ $field('pay_month') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="from_date">From Date</label>
                    <input id="from_date" class="form-input" name="data[from_date]" value="{{ $field('from_date') }}" type="date" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="to_date">To Date</label>
                    <input id="to_date" class="form-input" name="data[to_date]" value="{{ $field('to_date') }}" type="date" data-entry-field>
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <label class="form-label" for="arrears_description">Arears Description</label>
                    <textarea id="arrears_description" class="form-textarea" name="data[arrears_description]" rows="2" maxlength="150" data-entry-field>{{ $field('arrears_description') }}</textarea>
                    <p class="mt-1 text-xs text-slate-500">Will show when D.Wager and arrears pay month are selected. Maximum characters: 150</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Salary components</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="basic_pay">1. Basic Pay</label>
                    <input id="basic_pay" class="form-input" name="data[basic_pay]" value="{{ $field('basic_pay', '0.00') }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="grade_pay">2. Grade Pay</label>
                    <input id="grade_pay" class="form-input" name="data[grade_pay]" value="{{ $field('grade_pay', '0.00') }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="da_percent">D.A.%</label>
                    <input id="da_percent" class="form-input" name="data[da_percent]" value="{{ $field('da_percent', '0.00') }}" type="number" min="0" step="any" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="da_amount">3. D.A.</label>
                    <input id="da_amount" class="form-input" name="data[da_amount]" value="{{ $field('da_amount', '0.00') }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="medical">4. Medical</label>
                    <input id="medical" class="form-input" name="data[medical]" value="{{ $field('medical', '0.00') }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="hra_percent">H.R.A.%</label>
                    <input id="hra_percent" class="form-input" name="data[hra_percent]" value="{{ $field('hra_percent', '0.00') }}" type="number" min="0" step="any" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="hra_amount">5. H.R.A.</label>
                    <input id="hra_amount" class="form-input" name="data[hra_amount]" value="{{ $field('hra_amount', '0.00') }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="cla">6. CLA</label>
                    <input id="cla" class="form-input" name="data[cla]" value="{{ $field('cla', '0.00') }}" type="number" min="0" step="any" data-entry-field data-total-input>
                </div>
                <div>
                    <label class="form-label" for="recovery">Recovery (If any)</label>
                    <input id="recovery" class="form-input" name="data[recovery]" value="{{ $field('recovery', '0.00') }}" type="number" step="any" data-entry-field data-total-input>
                    <p class="mt-1 text-xs text-slate-500">If Recovery, then keep minus sign</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 border-t border-emerald-100 px-5 py-4">
                <span class="text-sm font-semibold text-slate-950">Total Amount</span>
                <input class="form-input w-40 bg-stone-50 text-right font-semibold" readonly data-total-amount value="0">
            </div>
            <div class="px-5 pb-4">
                <p class="text-sm text-slate-500" data-remaining-allotment-2>Remaining Allotment: -</p>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="p-5">
                <label class="form-label" for="remarks_rfo">Remarks by RFO (if any)</label>
                <textarea id="remarks_rfo" class="form-textarea" name="data[remarks_rfo]" rows="2" maxlength="150" data-entry-field>{{ $field('remarks_rfo') }}</textarea>
                <p class="mt-1 text-xs text-slate-500">Maximum characters: 150</p>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 bg-amber-50 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Deduction</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="gpf">GPF</label>
                    <input id="gpf" class="form-input bg-amber-50" name="data[gpf]" value="{{ $field('gpf', 0) }}" type="number" min="0" step="any" data-entry-field data-deduction-input>
                </div>
                <div>
                    <label class="form-label" for="cpf">CPF</label>
                    <input id="cpf" class="form-input bg-amber-50" name="data[cpf]" value="{{ $field('cpf', 0) }}" type="number" min="0" step="any" data-entry-field data-deduction-input>
                </div>
                <div>
                    <label class="form-label" for="p_tax">P.Tax</label>
                    <input id="p_tax" class="form-input bg-amber-50" name="data[p_tax]" value="{{ $field('p_tax', 0) }}" type="number" min="0" step="any" data-entry-field data-deduction-input>
                </div>
                <div>
                    <label class="form-label" for="group_insurance">Group Insurance</label>
                    <input id="group_insurance" class="form-input bg-amber-50" name="data[group_insurance]" value="{{ $field('group_insurance', 0) }}" type="number" min="0" step="any" data-entry-field data-deduction-input>
                </div>
                <div>
                    <label class="form-label" for="tds">TDS</label>
                    <input id="tds" class="form-input bg-amber-50" name="data[tds]" value="{{ $field('tds', 0) }}" type="number" min="0" step="any" data-entry-field data-deduction-input>
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
                <a class="secondary-button" href="{{ route('finance.d-wager-arrears-entries.index') }}">View Details</a>
                <button class="primary-button" type="submit">{{ $isEdit ? 'Update' : 'Submit' }}</button>
            </div>
        </section>
    </form>
</x-layouts.admin>
