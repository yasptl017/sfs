@php
    $designations = ['Guard', 'Forester', 'R.F.O.', 'A.C.F', 'D.C.F'];
    $roundRanges = ['Division', 'Range', '-'];
    $recoveryTypes = [
        'ગવર્મેન્ટ એજન્સી- ટોપર રોકડરી',
        'ગવર્મેન્ટ એજન્સી- કાયમટુક એક્સ ચારકોલ રોકડરી',
        'ગવર્મેન્ટ એજન્સી- બાયમુ રોકડરી',
        'ગવર્મેન્ટ એજન્સી- ખાસ એક્સ અખર MFP રોકડરી',
        'ગવર્મેન્ટ એજન્સી- ઓજોર એક્સ અખર ખાસ રોકડરી',
        'અખર એજન્સી- ટોપર રોકડરી',
        'અખર એજન્સી- કાયમટુક એક્સ ચારકોલ રોકડરી',
        'અખર એજન્સી- બાયમુ રોકડરી',
        'અખર એજન્સી- ખાસ એક્સ અખર MFP રોકડરી',
        'અખર એજન્સી- અખર MFP રોકડરી',
        'એન્વાયરમેન્ટ કો એક્સ વાલ- જ્યોલોજિકલ પાર્ટી કો.',
        'કાઇમ એક્સ કોટર ક્યાર',
        'ઓવર એજન્ટ રોકડરી',
        'લીવ સેલરી કોન્ટ્રીબ્યુશન',
        'GWB એન્ડાઉમેન્ટ ફી',
        'અખર આઇટમ- કાઇમ એક્સ કોટર ક્યાર',
        'અખર આઇટમ- ઓવર એજન્ટ રોકડરી',
        'અખર આઇટમ- લીવ સેલરી કોન્ટ્રીબ્યુશન',
        'અખર આઇટમ- WL એન્ડાઉમેન્ટ ફી',
        'અખર આઇટમ',
        'સી-મેલ વાયરસને / રીપુ ફી',
        'ટ્રાન્સફર પાસ ફી',
        'સેન્સરી / નેપાઇડ એન્ડ્રી ફી',
        'માલિકી ટુ રોયલ્ટી ફી',
        'ડાયરેક્ટ રેસ્ટહાઉસ ચાર્જ ફી',
        'અખર',
        'ગુણાકામ રોકડરી',
        'માસવાડી પાસ ફી',
        'કોલેક્ટર ફૂટ સેલ ફી',
        'ડાયરેક્ટ સેલમેન્ટ કોપી ફી',
        'ટેન્ડર ફી',
        'વિઝિટ ફી',
        'એકઝામ ફી',
        'લેબ ડિપોઝિટ',
        'રેટ એક્સ ફોરેસ્ટ બેંક',
        'લેબ એમાઉન્ટ એક્સ ખાસ બર્ન',
        'F.C.A. પ્રોસેસિંગ / રજિસ્ટ્રેશન ફી',
        'C.R.S.P ફી',
        'રોકડ એક્સ રેવન્યુ',
        'ડાયરેક્ટ ડિપોઝિટ',
        'ઈ-ઓક્શન',
        'વન મહોત્સવ રોપા વિતરણ',
        'ફ્રીમેલ રોપા વેચાણ વળતર',
    ];
@endphp
<x-layouts.admin title="Cash Account | Forest Inventory" heading="Cash Account" subheading="Division recovery and bank deposit entry">
    <form class="space-y-5" method="POST" action="{{ route('division.cash-accounts.store') }}">
        @csrf
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Cash account details</h2>
            </div>
            <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="form-label" for="cash_account_month">Cash Acc. of Month</label>
                    <input id="cash_account_month" class="form-input" name="cash_account_month" type="month" value="{{ old('cash_account_month') }}" required>
                    @error('cash_account_month') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="designation">Designation</label>
                    <select id="designation" class="form-select" name="designation" required><option value="" disabled @selected(blank(old('designation')))>Choose...</option>@foreach($designations as $option)<option value="{{ $option }}" @selected(old('designation') === $option)>{{ $option }}</option>@endforeach</select>
                    @error('designation') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="round_range">Round / range</label>
                    <select id="round_range" class="form-select" name="round_range" required><option value="" disabled @selected(blank(old('round_range')))>Choose...</option>@foreach($roundRanges as $option)<option value="{{ $option }}" @selected(old('round_range') === $option)>{{ $option }}</option>@endforeach</select>
                    @error('round_range') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="recoverer_designation">Designation of Recoverer</label>
                    <input id="recoverer_designation" class="form-input" name="recoverer_designation" value="{{ old('recoverer_designation') }}" required>
                    @error('recoverer_designation') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="recovery_officer_name">Recovery Officer Name</label>
                    <input id="recovery_officer_name" class="form-input" name="recovery_officer_name" value="{{ old('recovery_officer_name') }}" required>
                    @error('recovery_officer_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 xl:col-span-3">
                    <label class="form-label" for="recovery_type">Recovery Type</label>
                    <select id="recovery_type" class="form-select" name="recovery_type" required><option value="" disabled @selected(blank(old('recovery_type')))>Choose recovery type...</option>@foreach($recoveryTypes as $option)<option value="{{ $option }}" @selected(old('recovery_type') === $option)>{{ $option }}</option>@endforeach</select>
                    @error('recovery_type') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="recovery_date">Recovery Date</label>
                    <input id="recovery_date" class="form-input" name="recovery_date" type="date" value="{{ old('recovery_date') }}" required>
                    @error('recovery_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="bank_deposit_date">Bank Deposite Date</label>
                    <input id="bank_deposit_date" class="form-input" name="bank_deposit_date" type="date" value="{{ old('bank_deposit_date') }}" required>
                    @error('bank_deposit_date') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="bank_name_branch">Bank Name &amp; Branch</label>
                    <input id="bank_name_branch" class="form-input" name="bank_name_branch" value="{{ old('bank_name_branch') }}" required>
                    @error('bank_name_branch') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="challan_no">Challan No.</label>
                    <input id="challan_no" class="form-input" name="challan_no" value="{{ old('challan_no') }}" required>
                    @error('challan_no') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="amount">Amount</label>
                    <input id="amount" class="form-input" name="amount" type="number" min="0" step="0.01" value="{{ old('amount') }}" required>
                    @error('amount') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2 xl:col-span-4">
                    <label class="form-label" for="recovery_details">Details of Recovery</label>
                    <textarea id="recovery_details" class="form-textarea" name="recovery_details" rows="3">{{ old('recovery_details') }}</textarea>
                    @error('recovery_details') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </section>
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="flex flex-wrap justify-end gap-3 p-5"><button class="secondary-button" type="reset">Clear</button><a class="secondary-button" href="{{ route('division.cash-accounts.index') }}">View Details</a><button class="primary-button" type="submit">Submit</button></div>
        </section>
    </form>
</x-layouts.admin>
