@php
    $isEdit = filled($entry);
    $field = fn (string $key, mixed $default = '') => old("data.$key", $entry?->data[$key] ?? $default);
@endphp
<x-layouts.admin title="{{ $isEdit ? 'Edit SF Beneficiary Entry' : 'SF Beneficiary Entry' }} | Forest Inventory" heading="{{ $isEdit ? 'Edit SF Beneficiary Entry' : 'SF Beneficiary Entry' }}" subheading="Range finance SF beneficiary voucher entry">
    <form id="sfBeneficiaryEntryForm" class="space-y-5" method="POST" action="{{ $isEdit ? route('finance.sf-beneficiary-entries.update', $entry) : route('finance.sf-beneficiary-entries.store') }}"
        data-sf-bene-entry-form
        data-copy-url="{{ url('finance/sf-beneficiary-entry/copy') }}"
        data-redirect-url="{{ route('finance.sf-beneficiary-entries.index') }}"
        data-budget-codes="{{ $budgetCodes->mapWithKeys(fn ($b) => [$b->budget_code => ['scheme' => $b->scheme, 'model' => $b->model, 'scheme_year' => $b->scheme_year]])->toJson() }}"
        data-beats-by-round="{{ json_encode($beatsByRound) }}"
        data-beneficiaries-by-round-beat="{{ json_encode($beneficiariesByRoundBeat) }}"
        data-beneficiary-details="{{ $beneficiaryDetails->toJson() }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <div id="sfBeneficiaryEntryFormAlert" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold"></div>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="entry_sr_no">Sr. No. (of this Bene. Entry)</label>
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
                <h2 class="text-base font-semibold text-slate-950">Yojana &amp; budget</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="yojana_name">Name of Yojana</label>
                    <select id="yojana_name" class="form-select" name="data[yojana_name]" data-entry-field>
                        <option value="" @selected(blank($field('yojana_name'))) disabled>Choose...</option>
                        @foreach($yojanas as $yojana)
                            <option @selected($field('yojana_name') === $yojana)>{{ $yojana }}</option>
                        @endforeach
                    </select>
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
                    <label class="form-label" for="selection_year">Year / Instal. No.</label>
                    <select id="selection_year" class="form-select" name="data[selection_year]" data-entry-field>
                        <option value="" @selected(blank($field('selection_year'))) disabled>Choose Year / Instal. No.</option>
                        @foreach($years as $year)
                            <option @selected($field('selection_year') === $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">SF Model + Year</p>
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
                <h2 class="text-base font-semibold text-slate-950">Location &amp; beneficiary</h2>
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
                        <option value="" @selected(blank($field('beat'))) disabled>First Choose Round</option>
                        @if($field('beat'))
                            <option selected>{{ $field('beat') }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="form-label" for="sf_bene_code">Bene. Code</label>
                    <select id="sf_bene_code" class="form-select" name="data[sf_bene_code]" data-entry-field data-bene-code-select>
                        <option value="" @selected(blank($field('sf_bene_code'))) disabled>First Choose Round, Beat, SF model</option>
                        @if($field('sf_bene_code'))
                            <option selected>{{ $field('sf_bene_code') }}</option>
                        @endif
                    </select>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Beneficiary details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="full_name">Beneficiary Name</label>
                    <input id="full_name" class="form-input" name="data[full_name]" value="{{ $field('full_name') }}" data-entry-field data-bene-field="full_name">
                </div>
                <div>
                    <label class="form-label" for="name_gujarati">Bene. Name In Gujarati</label>
                    <input id="name_gujarati" class="form-input" name="data[name_gujarati]" value="{{ $field('name_gujarati') }}" data-entry-field data-bene-field="name_gujarati">
                </div>
                <div>
                    <label class="form-label" for="mobile_no">Mobile No.</label>
                    <input id="mobile_no" class="form-input" name="data[mobile_no]" value="{{ $field('mobile_no') }}" data-entry-field data-bene-field="mobile_no">
                </div>
                <div>
                    <label class="form-label" for="gender">Gender</label>
                    <select id="gender" class="form-select" name="data[gender]" data-entry-field data-bene-field="gender">
                        <option value="" @selected(blank($field('gender'))) disabled>Choose...</option>
                        <option @selected($field('gender') === 'Male')>Male</option>
                        <option @selected($field('gender') === 'Female')>Female</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="father_name">Fathers Name in case of Female Bene.</label>
                    <input id="father_name" class="form-input" name="data[father_name]" value="{{ $field('father_name') }}" data-entry-field data-bene-field="father_name">
                </div>
                <div>
                    <label class="form-label" for="birth_date">Birth Date of Beni.</label>
                    <input id="birth_date" class="form-input" name="data[birth_date]" value="{{ $field('birth_date') }}" type="date" data-entry-field data-bene-field="birth_date">
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="address">Address of Beni.</label>
                    <input id="address" class="form-input" name="data[address]" value="{{ $field('address') }}" data-entry-field data-bene-field="address">
                </div>
                <div>
                    <label class="form-label" for="pin_code">Pin Code of Beni.</label>
                    <input id="pin_code" class="form-input" name="data[pin_code]" value="{{ $field('pin_code') }}" data-entry-field data-bene-field="pin_code">
                </div>
                <div>
                    <label class="form-label" for="account_no">Account No.</label>
                    <input id="account_no" class="form-input" name="data[account_no]" value="{{ $field('account_no') }}" data-entry-field data-bene-field="account_no">
                </div>
                <div>
                    <label class="form-label" for="bank_name">Bank Name</label>
                    <input id="bank_name" class="form-input" name="data[bank_name]" value="{{ $field('bank_name') }}" data-entry-field data-bene-field="bank_name">
                </div>
                <div>
                    <label class="form-label" for="ifsc">IFSC</label>
                    <input id="ifsc" class="form-input" name="data[ifsc]" value="{{ $field('ifsc') }}" data-entry-field data-bene-field="ifsc">
                </div>
                <div>
                    <label class="form-label" for="branch">Branch</label>
                    <input id="branch" class="form-input" name="data[branch]" value="{{ $field('branch') }}" data-entry-field data-bene-field="branch">
                </div>
                <div>
                    <label class="form-label" for="gps">GPS</label>
                    <input id="gps" class="form-input" name="data[gps]" value="{{ $field('gps') }}" data-entry-field data-bene-field="gps">
                </div>
                <div>
                    <label class="form-label" for="id_type">ID Type</label>
                    <input id="id_type" class="form-input" name="data[id_type]" value="{{ $field('id_type') }}" data-entry-field data-bene-field="id_type">
                </div>
                <div>
                    <label class="form-label" for="id_no">ID No.</label>
                    <input id="id_no" class="form-input" name="data[id_no]" value="{{ $field('id_no') }}" data-entry-field data-bene-field="id_no">
                </div>
                <div>
                    <label class="form-label" for="hactor_dcp_plants">Ha / DCP Plants</label>
                    <input id="hactor_dcp_plants" class="form-input" name="data[hactor_dcp_plants]" value="{{ $field('hactor_dcp_plants') }}" data-entry-field data-bene-field="hactor_dcp_plants">
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Plantation &amp; survival</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="total_no_of_plants">Total No. of Plants</label>
                    <input id="total_no_of_plants" class="form-input" name="data[total_no_of_plants]" value="{{ $field('total_no_of_plants') }}" type="number" min="0" step="any" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="unit">Unit</label>
                    <input id="unit" class="form-input" name="data[unit]" value="{{ $field('unit') }}" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="bene_application_date">Bene. Application Date</label>
                    <input id="bene_application_date" class="form-input" name="data[bene_application_date]" value="{{ $field('bene_application_date') }}" type="date" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="bene_application_sanctioned_date">Bene. Application Sanctioned Date</label>
                    <input id="bene_application_sanctioned_date" class="form-input" name="data[bene_application_sanctioned_date]" value="{{ $field('bene_application_sanctioned_date') }}" type="date" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="survival_percent_data_date">Survival % Data Date</label>
                    <input id="survival_percent_data_date" class="form-input" name="data[survival_percent_data_date]" value="{{ $field('survival_percent_data_date') }}" type="date" data-entry-field>
                </div>
                <div>
                    <label class="form-label" for="no_of_plants_survived">No. of Plants Survived</label>
                    <input id="no_of_plants_survived" class="form-input" name="data[no_of_plants_survived]" value="{{ $field('no_of_plants_survived') }}" type="number" min="0" step="any" data-entry-field data-plants-survived>
                </div>
                <div>
                    <label class="form-label" for="survival_percent">Survival %</label>
                    <input id="survival_percent" class="form-input bg-stone-50" name="data[survival_percent]" value="{{ $field('survival_percent') }}" readonly data-entry-field data-survival-percent>
                </div>
                <div>
                    <label class="form-label" for="rate_per_plant">Rate Per Plant</label>
                    <input id="rate_per_plant" class="form-input" name="data[rate_per_plant]" value="{{ $field('rate_per_plant') }}" type="number" min="0" step="any" data-entry-field data-rate-per-plant>
                    <p class="mt-1 text-xs text-slate-500">1st Choose Bene. Code</p>
                </div>
                <div>
                    <label class="form-label">Amount</label>
                    <input class="form-input bg-stone-50 font-semibold" readonly data-total-amount value="0">
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <p class="text-sm text-slate-500" data-remaining-allotment-2>Remaining Allotment = Select Budget Code</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="details">Details</label>
                    <textarea id="details" class="form-textarea" name="data[details]" rows="2" data-entry-field>{{ $field('details') }}</textarea>
                </div>
                <div>
                    <label class="form-label" for="remarks_rfo">Remarks by RFO (if any)</label>
                    <textarea id="remarks_rfo" class="form-textarea" name="data[remarks_rfo]" rows="2" data-entry-field>{{ $field('remarks_rfo') }}</textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="flex flex-wrap justify-end gap-3 p-5">
                <button class="secondary-button" type="reset">Clear</button>
                <a class="secondary-button" href="{{ route('finance.sf-beneficiary-entries.index') }}">View Details</a>
                <button class="primary-button" type="submit">{{ $isEdit ? 'Update' : 'Submit' }}</button>
            </div>
        </section>
    </form>
</x-layouts.admin>
