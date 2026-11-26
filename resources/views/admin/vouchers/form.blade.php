@php
    use Illuminate\Support\Carbon;
    $isEdit = $voucher !== null;
    $isBene = in_array($type, ['sf-bene', 'wl-bene'], true);
    $isDw = in_array($type, ['dw-salary', 'dw-arrears'], true);
    $d = fn ($v) => $v ? substr((string) $v, 0, 10) : '';
    $num = fn ($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 6, '.', ''), '0'), '.');
    $field = function (string $col, string $label, string $kind = 'text', string $extra = '', string $wrap = '') use ($value, $d, $num, $errors) {
        $v = $value($col);
        $v = in_array($kind, ['date']) ? $d($v) : (in_array($kind, ['number']) ? $num($v) : $v);
        $type = $kind === 'number' ? 'number' : ($kind === 'date' ? 'date' : 'text');
        $step = $kind === 'number' ? 'step="any"' : '';
        return new \Illuminate\Support\HtmlString('<div class="'.$wrap.'"><label class="form-label" for="v_'.$col.'">'.e($label).'</label><input id="v_'.$col.'" class="form-input" type="'.$type.'" '.$step.' name="v['.$col.']" value="'.e($v).'" data-f="'.$col.'" '.$extra.'>'.($errors->has("v.$col") ? '<p class="form-error">'.e($errors->first("v.$col")).'</p>' : '').'</div>');
    };
    $title = ($isEdit ? 'Edit ' : 'New ').$label;
    $action = $isEdit ? route('finance.'.['tender' => 'tender-entries', 'free' => 'free-entries', 'dw-salary' => 'd-wager-salary-entries', 'dw-arrears' => 'd-wager-arrears-entries', 'sf-bene' => 'sf-beneficiary-entries', 'wl-bene' => 'wl-beneficiary-entries'][$type].'.update', $voucher)
                       : route('finance.'.['tender' => 'tender-entries', 'free' => 'free-entries', 'dw-salary' => 'd-wager-salary-entries', 'dw-arrears' => 'd-wager-arrears-entries', 'sf-bene' => 'sf-beneficiary-entries', 'wl-bene' => 'wl-beneficiary-entries'][$type].'.store');
    $plural = ['tender' => 'tender-entries', 'free' => 'free-entries', 'dw-salary' => 'd-wager-salary-entries', 'dw-arrears' => 'd-wager-arrears-entries', 'sf-bene' => 'sf-beneficiary-entries', 'wl-bene' => 'wl-beneficiary-entries'][$type];
    $items = $items ?: [[]];
    $jsBudget = $budgetCodes->map(function ($b) { return [$b->scheme, $b->object_class, $b->model, $b->scheme_year]; })->all();
    $jsParties = $parties->map(function ($p) { return ['code' => $p->party_code, 'name' => $p->party_name, 'pan' => $p->pan_card_no, 'gst' => $p->gst_no, 'acc' => $p->account_no, 'bank' => $p->bank_name, 'ifsc' => $p->ifsc, 'branch' => $p->branch, 'pct' => $p->approved_percent]; })->values()->all();
    $jsDw = $dwagers->map(function ($p) { return ['code' => $p->d_wager_code, 'name' => $p->d_wager_name, 'pan' => $p->pan_card_no, 'acc' => $p->account_no, 'bank' => $p->bank_name, 'ifsc' => $p->ifsc, 'branch' => $p->branch]; })->values()->all();
    $beneFields = function ($b, $code, $extra = []) use ($d) {
        return ['code' => $b->{$code}, 'name' => $b->full_name, 'acc' => $b->account_no, 'bank' => $b->bank_name, 'ifsc' => $b->ifsc, 'branch' => $b->branch, 'bene' => array_merge([
            'bene_code' => $b->{$code}, 'bene_full_name' => $b->full_name, 'name_of_yojana' => $b->yojana_name, 'bene_name_gujarati' => $b->name_gujarati, 'mobile_no' => $b->mobile_no, 'gender' => $b->gender,
            'address' => $b->address, 'pin_code' => $b->pin_code, 'category' => $b->category, 'district' => $b->district, 'taluka' => $b->taluka, 'village' => $b->village, 'survey_block_no' => $b->survey_block_no,
            'id_type' => $b->id_type, 'id_no' => $b->id_no, 'gps_n' => $b->gps_n, 'gps_e' => $b->gps_e, 'gps' => $b->gps, 'bene_selection_year' => $b->selection_year], $extra)];
    };
    $jsSf = $sfBenes->map(function ($b) use ($beneFields, $d) { return $beneFields($b, 'sf_bene_code', ['father_name' => $b->father_name, 'birth_date' => $d($b->birth_date), 'ha_dcp_plants' => $b->hactor_dcp_plants]); })->values()->all();
    $jsWl = $wlBenes->map(function ($b) use ($beneFields) { return $beneFields($b, 'wl_bene_code'); })->values()->all();
@endphp
<x-layouts.admin title="{{ $title }} | Forest Inventory" heading="{{ $title }}" subheading="Financial year {{ \App\Support\CurrentFinancialYear::model()->code }} - amounts are calculated automatically">
    <form id="voucherForm" method="POST" action="{{ $action }}" class="space-y-5">
        @csrf
        @if($isEdit) @method('PUT') @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="font-semibold">Please correct the following:</div>
                <ul class="mt-1 list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif
        @if($isEdit)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">Editing keeps the previous version under "Earlier versions". If you do not change any amount-related field, the stored amounts stay exactly as they are.</div>
        @elseif($isCopy)
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">Copied from Sr. {{ $source->serial_number }}. A new serial number will be given when you save.</div>
        @endif

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">Voucher</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="form-label">Sr. No.</label><input class="form-input bg-slate-50" value="{{ $next }}" readonly></div>
                <div><label class="form-label">Entry type</label><input class="form-input bg-slate-50" value="{{ $label }}" readonly></div>
                <div><label class="form-label" for="v_entry_month">Entry month</label>
                    <select id="v_entry_month" name="v[entry_month]" class="form-select" required><option value="">Choose...</option>@foreach($months as $m)<option @selected($value('entry_month') === $m)>{{ $m }}</option>@endforeach</select></div>
                {!! $field('docket_no', 'Docket No.', 'number') !!}
                @unless($isEdit)
                    <div class="md:col-span-2 xl:col-span-4 flex flex-wrap items-end gap-3 rounded-lg bg-stone-50 p-3">
                        <div><label class="form-label" for="copySr">Copy an earlier voucher (Sr. No.)</label><input id="copySr" class="form-input w-40" type="number" min="1" placeholder="e.g. 12"></div>
                        <a id="copyBtn" class="secondary-button" href="#">Copy entry</a>
                    </div>
                @endunless
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">Budget &amp; scheme</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="form-label" for="v_budget_code">Budget code</label>
                    <select id="v_budget_code" name="v[budget_code]" class="form-select" required data-f="budget_code"><option value="">Choose...</option>@foreach($budgetCodes as $code => $b)<option @selected($value('budget_code') === $code)>{{ $code }}</option>@endforeach</select></div>
                {!! $field('tender_code', 'Tender code') !!}
                <div class="md:col-span-2 xl:col-span-2"><label class="form-label">Scheme / class / model / scheme year (from budget code)</label><input id="budgetInfo" class="form-input bg-slate-50" readonly></div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">Location &amp; work</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div><label class="form-label" for="v_round">Round</label><select id="v_round" name="v[round]" class="form-select"><option value="">Choose...</option>@foreach($rounds as $r)<option @selected($value('round') === $r)>{{ $r }}</option>@endforeach @if($value('round') && !in_array($value('round'), $rounds))<option selected>{{ $value('round') }}</option>@endif</select></div>
                <div><label class="form-label" for="v_beat">Beat</label><select id="v_beat" name="v[beat]" class="form-select" data-keep="{{ $value('beat') }}"></select></div>
                <div><label class="form-label" for="v_place">Place</label><select id="v_place" name="v[place]" class="form-select" data-keep="{{ $value('place') }}"></select></div>
                {!! $field('area_ha', 'Area (Ha.)', 'number') !!}
                <div class="md:col-span-2 xl:col-span-4"><label class="form-label" for="v_description">Description (printed on the voucher)</label><textarea id="v_description" name="v[description]" rows="3" class="form-textarea">{{ $value('description') }}</textarea></div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">Bill</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                {!! $field('bill_no', 'Bill No.') !!} {!! $field('bill_date', 'Bill date', 'date') !!} {!! $field('register_name', 'Register name') !!} {!! $field('register_page_no', 'Register page no.', 'number') !!}
                <div class="md:col-span-2 xl:col-span-4"><label class="form-label" for="v_remarks_by_rfo">Remarks by RFO</label><textarea id="v_remarks_by_rfo" name="v[remarks_by_rfo]" rows="2" class="form-textarea">{{ $value('remarks_by_rfo') }}</textarea></div>
            </div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">{{ $isBene ? 'Beneficiary &amp; bank' : ($isDw ? 'D.Wager &amp; bank' : 'Party &amp; bank') }}</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div class="md:col-span-2 xl:col-span-4"><label class="form-label" for="picker">Pick from the register (fills the fields below)</label>
                    <select id="picker" class="form-select"><option value="">Choose...</option></select></div>
                {!! $field('party_code', $isBene ? 'Beneficiary code' : ($isDw ? 'D.Wager code' : 'Party code'), 'text', 'required') !!}
                {!! $field('party_name', $isBene ? 'Beneficiary name' : ($isDw ? 'D.Wager name' : 'Party name'), 'text', 'required', 'md:col-span-2') !!}
                {!! $field('pan_card_no', 'PAN') !!} {!! $field('gst_no', 'GST No.') !!} {!! $field('account_no', 'Account No.') !!}
                {!! $field('bank_name', 'Bank') !!} {!! $field('ifsc', 'IFSC') !!} {!! $field('branch', 'Branch') !!}
            </div>
        </section>

        @if($isBene)
            <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
                <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">Beneficiary details</h2></div>
                <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                    {!! $field('bene_code', 'Bene. code') !!} {!! $field('bene_full_name', 'Bene. full name') !!} {!! $field('name_of_yojana', 'Name of yojana') !!} {!! $field('year_installment_no', 'Year / instalment no.', 'number') !!}
                    {!! $field('bene_application_date', 'Application date', 'date') !!} {!! $field('bene_application_sanctioned_date', 'Application sanctioned date', 'date') !!} {!! $field('bene_selection_year', 'Selection year') !!} {!! $field('ha_dcp_plants', 'Ha / DCP plants', 'number') !!}
                    {!! $field('total_no_of_plants', 'Total no. of plants', 'number') !!} {!! $field('survival_data_date', 'Survival data date', 'date') !!} {!! $field('survival_percent', 'Survival %') !!} {!! $field('sf_model_year', 'SF model + year') !!}
                    {!! $field('bene_name_gujarati', 'Name in Gujarati') !!} {!! $field('mobile_no', 'Mobile') !!} {!! $field('gender', 'Gender') !!} {!! $field('father_name', "Father's name (female bene.)") !!}
                    {!! $field('birth_date', 'Birth date', 'date') !!} {!! $field('address', 'Address') !!} {!! $field('pin_code', 'Pin code') !!} {!! $field('category', 'Category') !!}
                    {!! $field('district', 'District') !!} {!! $field('taluka', 'Taluka') !!} {!! $field('village', 'Village') !!} {!! $field('survey_block_no', 'Survey / block no.') !!}
                    {!! $field('id_type', 'ID type') !!} {!! $field('id_no', 'ID no.') !!} {!! $field('gps_n', 'GPS-N', 'number') !!} {!! $field('gps_e', 'GPS-E', 'number') !!}
                    {!! $field('gps', 'GPS') !!} {!! $field('range_case_number', 'Range case number') !!} {!! $field('division_case_number', 'Division case number') !!}
                </div>
            </section>
        @endif

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-emerald-100 px-5 py-3">
                <h2 class="text-base font-semibold text-slate-950">{{ $isDw ? 'Salary components / line items' : 'Work items' }}</h2>
                <button type="button" class="secondary-button" id="addItem">+ Add line</button>
            </div>
            <div class="overflow-x-auto"><table class="data-table compact-data-table min-w-[80rem]">
                <thead><tr><th>SOR code / label</th><th>Description</th><th>Work order no.</th><th>W.O. date</th><th>Start</th><th>End</th><th>Qty</th><th>Unit</th><th>Rate</th><th>Amount</th><th></th></tr></thead>
                <tbody id="itemRows">
                    @foreach($items as $i => $it)
                        @php($it = (array) $it)
                        <tr data-item>
                            <td><input class="form-input min-w-28" name="items[{{ $i }}][sor_code]" value="{{ $it['sor_code'] ?? '' }}"></td>
                            <td><textarea class="form-textarea min-w-64" rows="2" name="items[{{ $i }}][description]" required>{{ $it['description'] ?? '' }}</textarea></td>
                            <td><input class="form-input min-w-28" name="items[{{ $i }}][work_order_no]" value="{{ $it['work_order_no'] ?? '' }}"></td>
                            <td><input class="form-input" type="date" name="items[{{ $i }}][work_order_date]" value="{{ $d($it['work_order_date'] ?? null) }}"></td>
                            <td><input class="form-input" type="date" name="items[{{ $i }}][work_start_date]" value="{{ $d($it['work_start_date'] ?? null) }}"></td>
                            <td><input class="form-input" type="date" name="items[{{ $i }}][work_end_date]" value="{{ $d($it['work_end_date'] ?? null) }}"></td>
                            <td><input class="form-input w-24" type="number" step="any" data-qty name="items[{{ $i }}][quantity]" value="{{ $num($it['quantity'] ?? null) }}"></td>
                            <td><input class="form-input w-20" name="items[{{ $i }}][unit]" value="{{ $it['unit'] ?? '' }}"></td>
                            <td><input class="form-input w-28" type="number" step="any" data-rate name="items[{{ $i }}][rate]" value="{{ $num($it['rate'] ?? null) }}"></td>
                            <td><input class="form-input w-32" type="number" step="any" data-amount name="items[{{ $i }}][amount]" value="{{ $num($it['amount'] ?? null) }}"></td>
                            <td><button type="button" class="secondary-button min-h-7 px-2 py-0.5 text-[11px] text-red-700" data-remove>Remove</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table></div>
        </section>

        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-3"><h2 class="text-base font-semibold text-slate-950">Amounts &amp; deductions</h2></div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                {!! $field('approved_percent', 'Approved %', 'number', 'data-calc') !!}
                {!! $field('sgst', 'SGST (added)', 'number', 'data-calc data-add') !!} {!! $field('cgst', 'CGST (added)', 'number', 'data-calc data-add') !!} {!! $field('igst', 'IGST (added)', 'number', 'data-calc data-add') !!}
                {!! $field('labour_cess', 'Labour cess (added)', 'number', 'data-calc data-add') !!} {!! $field('adjustment', 'Adjustment (+ or -)', 'number', 'data-calc data-add') !!}
                <div class="md:col-span-2 xl:col-span-2"></div>
                {!! $field('deduction_sgst', 'Deduction SGST', 'number', 'data-calc data-ded') !!} {!! $field('deduction_cgst', 'Deduction CGST', 'number', 'data-calc data-ded') !!} {!! $field('deduction_igst', 'Deduction IGST', 'number', 'data-calc data-ded') !!} {!! $field('deduction_labour_cess', 'Deduction labour cess', 'number', 'data-calc data-ded') !!}
                {!! $field('tds', 'TDS', 'number', 'data-calc data-ded') !!} {!! $field('deposit_deduction_amount', 'Deposit deduction', 'number', 'data-calc data-ded') !!} {!! $field('gpf', 'GPF', 'number', 'data-calc data-ded') !!} {!! $field('cpf', 'CPF', 'number', 'data-calc data-ded') !!}
                {!! $field('p_tax', 'Professional tax', 'number', 'data-calc data-ded') !!} {!! $field('group_insurance', 'Group insurance', 'number', 'data-calc data-ded') !!} {!! $field('other_deductions', 'Other deductions', 'number', 'data-calc data-ded') !!}
            </div>
            <div class="grid gap-3 border-t border-emerald-100 bg-stone-50 p-5 sm:grid-cols-2 xl:grid-cols-5">
                @foreach(['subTotal' => 'Sub total', 'approvedAmt' => 'Amount as per approved rate', 'payable' => 'Payable total', 'totalDed' => 'Total deduction', 'net' => 'Net amount'] as $id => $lab)
                    <div><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $lab }}</div><div id="{{ $id }}" class="mt-1 text-lg font-bold text-slate-950">0.00</div></div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a class="secondary-button" href="{{ $isEdit ? route('vouchers.show', $voucher) : route('vouchers.index', ['entry_type' => $label]) }}">Cancel</a>
            <button class="primary-button" type="submit">{{ $isEdit ? 'Save changes' : 'Save voucher' }}</button>
        </div>
    </form>

    <script>
    (function () {
        const budget = @json($jsBudget);
        const beatsByRound = @json($beatsByRound), placesByBeat = @json($placesByBeat);
        const type = @json($type);
        const registers = { parties: @json($jsParties), dwagers: @json($jsDw), sf: @json($jsSf), wl: @json($jsWl) };
        const list = type === 'sf-bene' ? registers.sf : type === 'wl-bene' ? registers.wl : (type === 'dw-salary' || type === 'dw-arrears') ? registers.dwagers : registers.parties;
        const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => [...r.querySelectorAll(s)];
        const set = (col, val) => { const el = $(`[data-f="${col}"]`); if (el && val !== null && val !== undefined) el.value = val; };
        const n = (v) => parseFloat(v) || 0;
        const fmt = (v) => v.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // budget code -> scheme info
        const bc = $('#v_budget_code'), info = $('#budgetInfo');
        const showBudget = () => { const b = budget[bc.value]; info.value = b ? b.filter(Boolean).join('  |  ') : ''; };
        bc.addEventListener('change', showBudget); showBudget();

        // round -> beat -> place
        const round = $('#v_round'), beat = $('#v_beat'), place = $('#v_place');
        const fill = (sel, values, keep) => { const v = [...values]; if (keep && !v.includes(keep)) v.push(keep); sel.innerHTML = '<option value="">Choose...</option>' + v.map((x) => `<option ${x === keep ? 'selected' : ''}>${x}</option>`).join(''); };
        const refreshBeats = (k) => fill(beat, beatsByRound[round.value] || [], k);
        const refreshPlaces = (k) => fill(place, placesByBeat[beat.value] || [], k);
        round.addEventListener('change', () => { refreshBeats(''); refreshPlaces(''); }); beat.addEventListener('change', () => refreshPlaces(''));
        refreshBeats(beat.dataset.keep); refreshPlaces(place.dataset.keep);

        // register picker
        const picker = $('#picker');
        picker.innerHTML += list.map((p, i) => `<option value="${i}">${p.code} - ${p.name}</option>`).join('');
        picker.addEventListener('change', () => {
            const p = list[picker.value]; if (!p) return;
            set('party_code', p.code); set('party_name', p.name); set('pan_card_no', p.pan); set('gst_no', p.gst); set('account_no', p.acc); set('bank_name', p.bank); set('ifsc', p.ifsc); set('branch', p.branch);
            if (p.pct !== undefined && p.pct !== null) set('approved_percent', p.pct);
            if (p.bene) Object.entries(p.bene).forEach(([k, v]) => set(k, v));
            calc();
        });

        // items
        const body = $('#itemRows');
        const rowTemplate = () => {
            const i = body.querySelectorAll('[data-item]').length;
            const tr = document.createElement('tr'); tr.setAttribute('data-item', '');
            tr.innerHTML = `<td><input class="form-input min-w-28" name="items[${i}][sor_code]"></td><td><textarea class="form-textarea min-w-64" rows="2" name="items[${i}][description]" required></textarea></td>
                <td><input class="form-input min-w-28" name="items[${i}][work_order_no]"></td><td><input class="form-input" type="date" name="items[${i}][work_order_date]"></td>
                <td><input class="form-input" type="date" name="items[${i}][work_start_date]"></td><td><input class="form-input" type="date" name="items[${i}][work_end_date]"></td>
                <td><input class="form-input w-24" type="number" step="any" data-qty name="items[${i}][quantity]"></td><td><input class="form-input w-20" name="items[${i}][unit]"></td>
                <td><input class="form-input w-28" type="number" step="any" data-rate name="items[${i}][rate]"></td><td><input class="form-input w-32" type="number" step="any" data-amount name="items[${i}][amount]"></td>
                <td><button type="button" class="secondary-button min-h-7 px-2 py-0.5 text-[11px] text-red-700" data-remove>Remove</button></td>`;
            return tr;
        };
        const renumber = () => $$('[data-item]', body).forEach((tr, i) => $$('[name]', tr).forEach((f) => f.name = f.name.replace(/items\[\d+\]/, `items[${i}]`)));
        $('#addItem').addEventListener('click', () => { body.appendChild(rowTemplate()); renumber(); });
        body.addEventListener('click', (e) => { if (e.target.matches('[data-remove]') && $$('[data-item]', body).length > 1) { e.target.closest('[data-item]').remove(); renumber(); calc(); } });

        // totals - same rules as the server (VoucherCalculator)
        function calc() {
            let sub = 0;
            $$('[data-item]', body).forEach((tr) => {
                const q = $('[data-qty]', tr), r = $('[data-rate]', tr), a = $('[data-amount]', tr);
                sub += n(a.value);
            });
            const pct = $('[data-f="approved_percent"]').value === '' ? 100 : n($('[data-f="approved_percent"]').value);
            const approved = sub * pct / 100;
            const add = $$('[data-add]').reduce((s, f) => s + n(f.value), 0);
            const ded = $$('[data-ded]').reduce((s, f) => s + n(f.value), 0);
            $('#subTotal').textContent = fmt(sub); $('#approvedAmt').textContent = fmt(approved); $('#payable').textContent = fmt(approved + add);
            $('#totalDed').textContent = fmt(ded); $('#net').textContent = fmt(approved + add - ded);
        }
        document.addEventListener('input', (e) => {
            if (!e.target.closest('#voucherForm')) return;
            // an amount is only auto-calculated when the user edits that row's quantity or rate (never on page load: typed amounts are authoritative)
            if (e.target.matches('[data-qty],[data-rate]')) {
                const tr = e.target.closest('[data-item]'), q = $('[data-qty]', tr), r = $('[data-rate]', tr);
                if (q.value !== '' && r.value !== '') $('[data-amount]', tr).value = (n(q.value) * n(r.value)).toFixed(2);
            }
            if (e.target.matches('[data-qty],[data-rate],[data-amount],[data-calc]')) calc();
        });
        calc();

        // copy
        const copyBtn = $('#copyBtn');
        if (copyBtn) copyBtn.addEventListener('click', (e) => { e.preventDefault(); const v = $('#copySr').value; if (v) location.href = location.pathname + '?copy=' + encodeURIComponent(v); });
    })();
    </script>
</x-layouts.admin>
