<x-layouts.admin title="Party Registration | Forest Inventory" heading="Party Registration" subheading="Registration Entry for range finance parties">
    <form id="partyRegistrationForm" class="space-y-5" method="POST" action="{{ route('finance.registration.party.store') }}" data-party-registration-form data-copy-url="{{ url('finance/registration/party/copy') }}">
        @csrf
        <div id="partyFormAlert" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold"></div>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Copy or start new entry</h2>
                <p class="mt-1 text-sm text-slate-500">Enter Sr. No. to copy and click Copy Entry, or continue with a totally new entry.</p>
            </div>
            <div class="grid gap-4 p-5 lg:grid-cols-[1fr_1fr_auto]">
                <div>
                    <label class="form-label" for="copy_party_sr_no">Sr. No. to copy</label>
                    <input id="copy_party_sr_no" class="form-input" name="copy_party_sr_no" placeholder="Enter existing Sr. No.">
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
                    <button class="secondary-button w-full lg:w-auto" type="button" data-copy-party-entry>Copy Entry</button>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Party details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="party_sr_no">Sr. No. (of this party)</label>
                    <input id="party_sr_no" class="form-input" name="party_sr_no" value="{{ $nextSerial }}" readonly required data-party-field>
                </div>
                <div>
                    <label class="form-label" for="party_code">Party Code (of this party)</label>
                    <input id="party_code" class="form-input" name="party_code" required data-party-field>
                </div>
                <div>
                    <label class="form-label" for="round">Round</label>
                    <select id="round" class="form-select" name="round" required data-party-field>
                        <option value="" selected disabled>Choose...</option>
                        <option>All</option>
                        <option>Rounds-</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="approved_percent">Approved %</label>
                    <input id="approved_percent" class="form-input" name="approved_percent" type="number" min="0" max="100" step="0.01" data-party-field>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="small_description">Small Description</label>
                    <input id="small_description" class="form-input" name="small_description" data-party-field>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="party_name">Party Name</label>
                    <input id="party_name" class="form-input" name="party_name" required data-party-field>
                </div>
                <div>
                    <label class="form-label" for="pan_card_no">Pan Card No.</label>
                    <input id="pan_card_no" class="form-input" name="pan_card_no" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="gst_no">GST No.</label>
                    <input id="gst_no" class="form-input" name="gst_no" data-party-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Bank details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label class="form-label" for="BankName">Bank Name</label>
                    <select class="form-select" aria-label="BankName" id="BankName" name="bank_name" required data-party-field>
                        <option value="" selected disabled>Choose...</option>
                        @foreach($banks as $bank)
                            <option>{{ $bank }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="account_no">Account No.</label>
                    <input id="account_no" class="form-input" name="account_no" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="ifsc">IFSC</label>
                    <input id="ifsc" class="form-input" name="ifsc" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="branch">Branch</label>
                    <input id="branch" class="form-input" name="branch" data-party-field>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Deduction Percentage</h2>
            </div>
            <div class="grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-3">
                <div>
                    <label class="form-label" for="deduction_sgst">Deduction SGST %</label>
                    <input id="deduction_sgst" class="form-input" name="deduction_sgst" type="number" min="0" step="0.01" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="deduction_cgst">Deduction CGST %</label>
                    <input id="deduction_cgst" class="form-input" name="deduction_cgst" type="number" min="0" step="0.01" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="deduction_igst">Deduction IGST %</label>
                    <input id="deduction_igst" class="form-input" name="deduction_igst" type="number" min="0" step="0.01" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="deduction_labour_cess">Deduction Labour Cess %</label>
                    <input id="deduction_labour_cess" class="form-input" name="deduction_labour_cess" type="number" min="0" step="0.01" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="deposit_deduction">Deposit Deduction % from Bill</label>
                    <input id="deposit_deduction" class="form-input" name="deposit_deduction" type="number" min="0" step="0.01" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="tds">TDS %</label>
                    <input id="tds" class="form-input" name="tds" type="number" min="0" step="0.01" data-party-field>
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
                    <input id="party_approval_no" class="form-input" name="party_approval_no" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="party_aadhaar_no">Party Aadhaar No.</label>
                    <input id="party_aadhaar_no" class="form-input" name="party_aadhaar_no" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="party_mobile_no">Party Mobile No.</label>
                    <input id="party_mobile_no" class="form-input" name="party_mobile_no" data-party-field>
                </div>
                <div>
                    <label class="form-label" for="party_email">Party Email</label>
                    <input id="party_email" class="form-input" name="party_email" type="email" data-party-field>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="link_of_doc">Link of Doc</label>
                    <input id="link_of_doc" class="form-input" name="link_of_doc" type="url" placeholder="https://..." data-party-field>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label" for="party_address">Party Address</label>
                    <textarea id="party_address" class="form-textarea" name="party_address" rows="3" data-party-field></textarea>
                </div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="grid gap-4 p-5 lg:grid-cols-[1fr_auto] lg:items-center">
                <div>
                    <label class="form-label">Party Status</label>
                    <p class="text-sm text-slate-500">Deactive એટલે હવે પેમેન્ટ નથી કરવાનું આ પાર્ટી ને.</p>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <label class="radio-pill">
                            <input type="radio" name="party_status" value="Active" checked data-party-field>
                            <span>Active</span>
                        </label>
                        <label class="radio-pill">
                            <input type="radio" name="party_status" value="Deactive" data-party-field>
                            <span>Deactive</span>
                        </label>
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button class="secondary-button" type="reset">Clear</button>
                    <button class="primary-button" type="submit">Submit</button>
                </div>
            </div>
        </section>
    </form>
</x-layouts.admin>
