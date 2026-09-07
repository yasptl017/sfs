@php
    $isEdit = filled($party);
    $field = fn (string $key, mixed $default = '') => old("data.$key", $party?->data[$key] ?? $default);

    $tenderTypes = ['SOR', 'Tender', 'Bhav Patrak', 'Estimate', 'Lakhpati Didi Group', 'Lakhpati Didi Individual', 'WL Machan MOU', 'WL Machan Tender', 'WL Machan Beneficiary', 'WL Open Well MOU', 'WL Open Well Tender', 'WL Open Well Beneficiary'];
    $tenderModes = ['n-Procure', 'GEM', 'Offline'];
    $ddChequeModes = ['D.D.', 'Cheque', 'Banker Cheque'];
    $securityModes = ['F.D.', 'D.D.', 'Remittance Deposit (D.D.)', 'Deduction from Bill', 'Remittance Deposit (Cheque)', 'Remittance Deposit (Cash)'];
    $banks = ['State Bank of India', 'Bank of Baroda', 'Punjab National Bank', 'Saurashtra Gramin Bank', 'Allahabad Bank', 'Axis Bank', 'Bank of India', 'Canara Bank', 'HDFC Bank', 'ICICI Bank', 'IDBI Bank', 'Indian Overseas Bank', 'Kotak Mahindra Bank', 'The Co operative Bank of Rajkot', 'Syndicate Bank', 'The Saraswat Co operative Bank Ltd', 'Corporation Bank', 'Rajkot Nagarik Sahakari Bank Ltd', 'Union Bank', 'The Surendranagar Co operative Bank', 'Indian Bank', 'Adarsh Co operative Bank', 'Central Bank of India', 'Ahemdabad Dist Co operative Bank Ltd', 'The Surendranagar District Co operative Bank Ltd', 'BMCB', 'Baroda Gujarat Gramin Bank', 'Kukarwada Nagrik Sahkari Bank Ltd', 'Yes Bank', 'Union Bank of India', 'DCB Bank', 'Cosmos Bank', 'IndusInd Bank', 'The Kachchh District Central Co operative Bank Ltd', 'South Indian Bank', 'Standard Chartered Bank', 'AU Small Finance Bank', 'Federal Bank Limited', 'Bharat Co operative Bank (Mumbai) Ltd', 'The Mehsana Urban Co operative Bank Ltd', 'United Bank of India', 'Bandhan Bank', 'Associate Co operative Bank Ltd.', 'RBL Bank', 'Party Cheque', 'D.D.', 'Bank of Maharashtra', 'Karur Vysya Bank', 'Gujarat State Co operative Bank', 'The Mehsana District Central Co operative Bank Ltd.', 'SBBP CO-OPERATIVE  BANK LTD.', 'The Sarvodaya Sahakari Bank Ltd Modasa', 'The S.k District Central Co operative Bank Ltd', 'Punjab &  Sind Bank', 'Himmatnagar Nagarik Sahakari Bank Ltd', 'The Kalupur Commercial Co operative Bank Ltd', 'Gujarat Naramda Valley Fertilizer and Chemicals Limited', 'Gujarat Gramin Bank', 'The Sarvoday Nagrik Sahkari Bank Ltd', 'Janata Sahakari Bank Ltd', 'UCO Bank'];
@endphp
<x-layouts.admin title="{{ $isEdit ? 'Edit Tender Party' : 'Tender Party Registration' }}" heading="{{ $isEdit ? 'Edit Tender Party' : 'Tender Party Registration' }}" subheading="Division finance party entry">
    <form id="divisionPartyForm" class="space-y-5" method="POST" action="{{ $isEdit ? route('division.parties.update', $party) : route('division.parties.store') }}" enctype="multipart/form-data"
        data-division-party-form
        data-copy-url="{{ url('division/parties/copy') }}"
        data-redirect-url="{{ route('division.parties.index') }}"
        data-rounds-by-range="{{ json_encode($roundsByRange) }}">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif
        <div id="divisionPartyFormAlert" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold"></div>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="party_sr_no" style="color:#2563eb">Sr. No. (of this party)</label>
                    <input id="party_sr_no" class="form-input" name="party_sr_no" value="{{ $isEdit ? $party->serial_number : $next }}" readonly required data-party-field>
                </div>
            </div>
        </section>

        @unless($isEdit)
            <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Copy or start new entry</h2>
                    <p class="mt-1 text-sm text-slate-500">Enter Sr. No. to copy and click 'Copy Entry' button, or do totally new entry.</p>
                </div>
                <div class="grid gap-4 p-5 lg:grid-cols-[1fr_1fr_auto]">
                    <div>
                        <label class="form-label" for="copy_division_party_sr_no">Sr. No. to copy</label>
                        <input id="copy_division_party_sr_no" class="form-input" name="copy_party_sr_no" placeholder="Enter existing Sr. No.">
                    </div>
                    <div>
                        <label class="form-label" for="edit_copied_division_entry">Edit copied entry?</label>
                        <label class="toggle-field">
                            <input id="edit_copied_division_entry" type="checkbox" data-edit-copied-entry>
                            <span></span>
                            <strong>Enable editing after copy</strong>
                        </label>
                    </div>
                    <div class="flex items-end">
                        <button class="secondary-button w-full lg:w-auto" type="button" data-copy-party-entry>Copy Entry</button>
                    </div>
                </div>
            </section>
        @endunless

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Tender details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="tender_type">Tender Type</label>
                    <select id="tender_type" class="form-select" name="data[tender_type]" required data-party-field data-tender-type>
                        <option value="" @selected(blank($field('tender_type'))) disabled>Choose...</option>
                        @foreach($tenderTypes as $type)
                            <option @selected($field('tender_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="tender_mode">Tender Mode</label>
                    <select id="tender_mode" class="form-select" name="data[tender_mode]" required data-party-field data-tender-mode>
                        <option value="" @selected(blank($field('tender_mode'))) disabled>Choose...</option>
                        @foreach($tenderModes as $mode)
                            <option @selected($field('tender_mode') === $mode)>{{ $mode }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="tender_no">Tender No.</label>
                    <input id="tender_no" class="form-input" name="data[tender_no]" value="{{ $field('tender_no') }}" list="ExistingTenderNos" required data-party-field data-tender-no>
                    <datalist id="ExistingTenderNos">
                        @foreach($existingTenderNos as $no)
                            <option value="{{ $no }}"></option>
                        @endforeach
                    </datalist>
                </div>
                <div>
                    <label class="form-label" for="tender_code">Tender Code</label>
                    <input id="tender_code" class="form-input" name="data[tender_code]" value="{{ $field('tender_code') }}" required readonly data-party-field data-tender-code>
                </div>
                <div>
                    <label class="form-label" for="tender_opening_date">Tender Opening Date</label>
                    <input id="tender_opening_date" class="form-input" name="data[tender_opening_date]" value="{{ $field('tender_opening_date') }}" type="date" required data-party-field>
                </div>
                <div>
                    <label class="form-label" for="tender_validity_date">Tender Validity up to Date</label>
                    <input id="tender_validity_date" class="form-input" name="data[tender_validity_date]" value="{{ $field('tender_validity_date') }}" type="date" data-party-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Party details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="party_code" style="color:#2563eb">Party Code (of this party)</label>
                    <input id="party_code" class="form-input" name="data[party_code]" value="{{ $field('party_code') }}" required readonly data-party-field data-party-code>
                </div>
                <div>
                    <label class="form-label" for="range">Range</label>
                    <select id="range" class="form-select" name="data[range]" required data-party-field data-range-select>
                        <option value="" @selected(blank($field('range'))) disabled>Choose...</option>
                        <option @selected($field('range') === 'AllRanges')>AllRanges</option>
                        @foreach($ranges as $r)
                            <option @selected($field('range') === $r)>{{ $r }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="round">Round</label>
                    <select id="round" class="form-select" name="data[round]" data-party-field data-round-select>
                        <option value="" @selected(blank($field('round'))) disabled>Choose...</option>
                        @if($field('round'))
                            <option selected>{{ $field('round') }}</option>
                        @endif
                    </select>
                </div>
                <div>
                    <label class="form-label" for="approved_percent">Approved %</label>
                    <input id="approved_percent" class="form-input" name="data[approved_percent]" value="{{ $field('approved_percent', 100) }}" type="number" min="0" step="any" data-party-field>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="small_description">Small Description</label>
                    <input id="small_description" class="form-input" name="data[small_description]" value="{{ $field('small_description') }}" placeholder="e.g. Vanikaran or PGVCL bill etc" data-party-field>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="party_name" style="color:#2563eb">Party Name</label>
                    <input id="party_name" class="form-input" name="data[party_name]" value="{{ $field('party_name') }}" placeholder="As per bank passbook" required data-party-field>
                </div>
                <div>
                    <label class="form-label" for="pan_card_no">Pan Card No.</label>
                    <input id="pan_card_no" class="form-input uppercase" name="data[pan_card_no]" value="{{ $field('pan_card_no') }}" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Required if TDS or GST to deduct</p>
                </div>
                <div>
                    <label class="form-label" for="gst_no">GST No.</label>
                    <input id="gst_no" class="form-input uppercase" name="data[gst_no]" value="{{ $field('gst_no') }}" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Required if GST to deduct</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Bank details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="bank_name">Bank Name</label>
                    <select id="bank_name" class="form-select" name="data[bank_name]" data-party-field>
                        <option value="" @selected(blank($field('bank_name'))) disabled>Choose...</option>
                        @foreach($banks as $bank)
                            <option @selected($field('bank_name') === $bank)>{{ $bank }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-slate-500">Required if any deduction to do.</p>
                </div>
                <div>
                    <label class="form-label" for="account_no">Account No.</label>
                    <input id="account_no" class="form-input" name="data[account_no]" value="{{ $field('account_no') }}" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Required if any deduction to do.</p>
                </div>
                <div>
                    <label class="form-label" for="ifsc">IFSC</label>
                    <input id="ifsc" class="form-input uppercase" name="data[ifsc]" value="{{ $field('ifsc') }}" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Required if any deduction to do.</p>
                </div>
                <div>
                    <label class="form-label" for="branch">Branch</label>
                    <input id="branch" class="form-input" name="data[branch]" value="{{ $field('branch') }}" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Required if any deduction to do.</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Additional Percentage</h2>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="additional_sgst">(Additional) SGST %</label>
                    <input id="additional_sgst" class="form-input" name="data[additional_sgst]" value="{{ $field('additional_sgst') }}" type="number" min="0" step="any" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="additional_cgst">(Additional) CGST %</label>
                    <input id="additional_cgst" class="form-input" name="data[additional_cgst]" value="{{ $field('additional_cgst') }}" type="number" min="0" step="any" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="additional_igst">(Additional) IGST %</label>
                    <input id="additional_igst" class="form-input" name="data[additional_igst]" value="{{ $field('additional_igst') }}" type="number" min="0" step="any" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="additional_labour_cess">(Additional) Labour Cess %</label>
                    <input id="additional_labour_cess" class="form-input" name="data[additional_labour_cess]" value="{{ $field('additional_labour_cess') }}" type="number" min="0" step="any" data-party-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 bg-amber-50 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Deduction Percentage</h2>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="deduction_sgst">Deduction SGST %</label>
                    <input id="deduction_sgst" class="form-input bg-amber-50" name="data[deduction_sgst]" value="{{ $field('deduction_sgst') }}" type="number" min="0" step="any" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Pan and GST numbers are required to cut GST.</p>
                </div>
                <div>
                    <label class="form-label" for="deduction_cgst">Deduction CGST %</label>
                    <input id="deduction_cgst" class="form-input bg-amber-50" name="data[deduction_cgst]" value="{{ $field('deduction_cgst') }}" type="number" min="0" step="any" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Pan and GST numbers are required to cut GST.</p>
                </div>
                <div>
                    <label class="form-label" for="deduction_igst">Deduction IGST %</label>
                    <input id="deduction_igst" class="form-input bg-amber-50" name="data[deduction_igst]" value="{{ $field('deduction_igst') }}" type="number" min="0" step="any" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Pan and GST numbers are required to cut GST.</p>
                </div>
                <div>
                    <label class="form-label" for="deduction_labour_cess">Deduction Labour Cess %</label>
                    <input id="deduction_labour_cess" class="form-input bg-amber-50" name="data[deduction_labour_cess]" value="{{ $field('deduction_labour_cess') }}" type="number" min="0" step="any" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Pan and GST numbers are required to cut Labour Cess.</p>
                </div>
                <div>
                    <label class="form-label" for="tds">TDS %</label>
                    <input id="tds" class="form-input bg-amber-50" name="data[tds]" value="{{ $field('tds') }}" type="number" min="0" step="any" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Pan number must be given</p>
                </div>
                <div>
                    <label class="form-label" for="deposit_deduction">Deposit Deduction % from Bill</label>
                    <input id="deposit_deduction" class="form-input bg-amber-50" name="data[deposit_deduction]" value="{{ $field('deposit_deduction') }}" type="number" min="0" step="any" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Pan number must be given</p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Approval and contact</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="party_approval_no">Party Approval No.</label>
                    <input id="party_approval_no" class="form-input" name="data[party_approval_no]" value="{{ $field('party_approval_no') }}" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="party_approval_date">Party Approval Date</label>
                    <input id="party_approval_date" class="form-input" name="data[party_approval_date]" value="{{ $field('party_approval_date') }}" type="date" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="party_aadhaar_no">Party Aadhaar No.</label>
                    <input id="party_aadhaar_no" class="form-input" name="data[party_aadhaar_no]" value="{{ $field('party_aadhaar_no') }}" placeholder="e.g. 232546462325" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Aadhaar number must be 12 digit, without spaces or comma.</p>
                </div>
                <div>
                    <label class="form-label" for="party_mobile_no">Party Mobile No.</label>
                    <input id="party_mobile_no" class="form-input" name="data[party_mobile_no]" value="{{ $field('party_mobile_no') }}" placeholder="e.g. 9998812345" data-party-field>
                    <p class="mt-1 text-xs text-slate-500">Mobile number must be 10 digit, without spaces or comma.</p>
                </div>
                <div>
                    <label class="form-label" for="party_email">Party Email</label>
                    <input id="party_email" class="form-input" name="data[party_email]" value="{{ $field('party_email') }}" type="email" placeholder="e.g. axay@gmail.com" data-party-field>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="link_of_doc">Link of Doc</label>
                    <input id="link_of_doc" class="form-input" name="data[link_of_doc]" value="{{ $field('link_of_doc') }}" data-party-field>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="party_address">Party Address</label>
                    <input id="party_address" class="form-input" name="data[party_address]" value="{{ $field('party_address') }}" data-party-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Tender Fee</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="tender_fee_mode">Tender Fee by DD / Cheque</label>
                    <select id="tender_fee_mode" class="form-select" name="data[tender_fee_mode]" data-party-field>
                        <option value="" @selected(blank($field('tender_fee_mode'))) disabled>Choose...</option>
                        @foreach($ddChequeModes as $mode)
                            <option @selected($field('tender_fee_mode') === $mode)>{{ $mode }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="tender_fee_no">Tender Fee DD No. / Cheque No.</label>
                    <input id="tender_fee_no" class="form-input" name="data[tender_fee_no]" value="{{ $field('tender_fee_no') }}" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="tender_fee_date">Tender Fee DD / Cheque Date</label>
                    <input id="tender_fee_date" class="form-input" name="data[tender_fee_date]" value="{{ $field('tender_fee_date') }}" type="date" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="tender_fee_amount">Tender Fee Amount</label>
                    <input id="tender_fee_amount" class="form-input" name="data[tender_fee_amount]" value="{{ $field('tender_fee_amount') }}" type="number" min="0" step="any" data-party-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">EMD details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="emd_mode">EMD by DD / Cheque</label>
                    <select id="emd_mode" class="form-select" name="data[emd_mode]" data-party-field>
                        <option value="" @selected(blank($field('emd_mode'))) disabled>Choose...</option>
                        @foreach($ddChequeModes as $mode)
                            <option @selected($field('emd_mode') === $mode)>{{ $mode }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="emd_no">EMD DD No. / Cheque No.</label>
                    <input id="emd_no" class="form-input" name="data[emd_no]" value="{{ $field('emd_no') }}" type="number" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="emd_date">EMD DD / Cheque Date</label>
                    <input id="emd_date" class="form-input" name="data[emd_date]" value="{{ $field('emd_date') }}" type="date" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="emd_amount">EMD Amount</label>
                    <input id="emd_amount" class="form-input" name="data[emd_amount]" value="{{ $field('emd_amount') }}" type="number" min="0" step="any" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="emd_refund_date">EMD Refund to Party Date</label>
                    <input id="emd_refund_date" class="form-input" name="data[emd_refund_date]" value="{{ $field('emd_refund_date') }}" type="date" data-party-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Security Deposit</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="security_mode">Security Deposite DD / FD / Remittance Deposite</label>
                    <select id="security_mode" class="form-select" name="data[security_mode]" data-party-field>
                        <option value="" @selected(blank($field('security_mode'))) disabled>Choose...</option>
                        @foreach($securityModes as $mode)
                            <option @selected($field('security_mode') === $mode)>{{ $mode }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="security_no">Security Deposite DD No. / FD / Remittance Deposite No.</label>
                    <input id="security_no" class="form-input" name="data[security_no]" value="{{ $field('security_no') }}" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="security_date">Security Deposite DD / FD / Remittance Deposite Date</label>
                    <input id="security_date" class="form-input" name="data[security_date]" value="{{ $field('security_date') }}" type="date" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="security_amount">Security Deposite Amount</label>
                    <input id="security_amount" class="form-input" name="data[security_amount]" value="{{ $field('security_amount') }}" type="number" min="0" step="any" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="security_received_date">Security Deposite Received Date</label>
                    <input id="security_received_date" class="form-input" name="data[security_received_date]" value="{{ $field('security_received_date') }}" type="date" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="security_submitted">Security Deposite Submitted to Remmitance?</label>
                    <select id="security_submitted" class="form-select" name="data[security_submitted]" data-party-field>
                        <option value="" @selected(blank($field('security_submitted'))) disabled>Choose...</option>
                        <option @selected($field('security_submitted') === 'Yes')>Yes</option>
                        <option @selected($field('security_submitted') === 'No')>No</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="security_challan_no">Security Deposite Remittance Chalan No.</label>
                    <input id="security_challan_no" class="form-input" name="data[security_challan_no]" value="{{ $field('security_challan_no') }}" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="security_remittance_date">Security Deposite Remittance Date</label>
                    <input id="security_remittance_date" class="form-input" name="data[security_remittance_date]" value="{{ $field('security_remittance_date') }}" type="date" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="security_given_date">Security Deposite given to Party Date</label>
                    <input id="security_given_date" class="form-input" name="data[security_given_date]" value="{{ $field('security_given_date') }}" type="date" data-party-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Attachments</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="attachments">Upload files</label>
                    <input id="attachments" class="form-input" type="file" name="attachments[]" multiple>
                    <p class="mt-1 text-xs text-slate-500">Any file type, up to 20 MB each.</p>
                </div>
                @if($isEdit && $party->attachments)
                    <div>
                        <label class="form-label">Existing files</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($party->attachments as $file)
                                <a class="text-xs font-semibold text-emerald-700" href="{{ Storage::disk('public')->url($file) }}" target="_blank">File</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="grid gap-4 p-5 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <label class="form-label">Party Status</label>
                    <p class="text-sm text-slate-500">Deactive એટલે હવે પેમેન્ટ નથી કરવાનું આ પાર્ટી ને.</p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <label class="radio-pill">
                            <input type="radio" name="party_status" value="Active" @checked(old('party_status', $party?->party_status ?? 'Active') === 'Active') data-party-field>
                            <span>Active</span>
                        </label>
                        <label class="radio-pill">
                            <input type="radio" name="party_status" value="Deactive" @checked(old('party_status', $party?->party_status ?? 'Active') === 'Deactive') data-party-field>
                            <span>Deactive</span>
                        </label>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button class="secondary-button" type="reset">Clear</button>
                    <a class="secondary-button" href="{{ route('division.parties.index') }}">View Details</a>
                    <button class="primary-button" type="submit">{{ $isEdit ? 'Update' : 'Submit' }}</button>
                </div>
            </div>
        </section>
    </form>
</x-layouts.admin>
