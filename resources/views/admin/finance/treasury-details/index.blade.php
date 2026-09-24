<x-layouts.admin title="Add Treasury Details | Forest Inventory" heading="Add Treasury Details" subheading="Division Finance System - Bill &amp; Advice Treasury Submission &amp; Clearance">
    <div class="space-y-5" id="treasuryDetailModule"
        data-info-url="{{ route('division.treasury-details.info') }}"
        data-store-url="{{ route('division.treasury-details.store') }}"
        data-bill-advice-map="{{ json_encode($billAdviceMap) }}">

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-lg font-bold">&times;</button>
            </div>
        @endif

        <div id="treasuryAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Add Treasury Details Card (Matching Party Registration Theme) -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-emerald-100 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Add Treasury Details of a Bill</h2>
                    <p class="mt-1 text-sm text-slate-500">Record Treasury Token Number, Treasury Voucher (TV) details, and clearance status for bills.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Module:</span>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 border border-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        <span>Treasury Details</span>
                    </span>
                </div>
            </div>

            <form id="treasuryDetailsForm" method="POST" action="{{ route('division.treasury-details.store') }}" class="p-6 space-y-6">
                @csrf

                <!-- Top Reference Bar: Bill No, Advice No, Date, Type, Amount (Matching Reference Layout in Theme) -->
                <div class="rounded-xl border border-emerald-200/80 bg-emerald-50/40 p-5 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-2">
                        <!-- Bill No -->
                        <div>
                            <label for="bill_no" class="form-label font-bold text-slate-900">
                                Bill No.: <span class="text-red-500">*</span>
                            </label>
                            <select id="bill_no" name="bill_no" required class="form-select font-bold text-slate-900 bg-white">
                                <option value="" disabled selected>Select Tag (Choose Bill No)...</option>
                                @foreach($billNumbers as $bNo)
                                    <option value="{{ $bNo }}" @selected(old('bill_no') == $bNo)>{{ $bNo }}</option>
                                @endforeach
                            </select>
                            @error('bill_no')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Advice No -->
                        <div>
                            <label for="advice_no" class="form-label font-bold text-slate-900">
                                Advice No.: <span class="text-red-500">*</span>
                            </label>
                            <select id="advice_no" name="advice_no" required class="form-select font-bold text-slate-900 bg-white">
                                <option value="" disabled selected>Select Tag (Choose Advice No)...</option>
                                @foreach($adviceNumbers as $aNo)
                                    <option value="{{ $aNo }}" @selected(old('advice_no') == $aNo)>{{ $aNo }}</option>
                                @endforeach
                            </select>
                            @error('advice_no')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Row 2: Advice Date, Bill Type, Bill Amount (Matching 3 Fields in Image) -->
                    <div class="grid gap-4 sm:grid-cols-3 pt-2">
                        <!-- Advice Date -->
                        <div>
                            <label for="advice_date" class="form-label font-semibold text-slate-800">
                                Advice Date
                            </label>
                            <input type="date" id="advice_date" name="advice_date"
                                value="{{ old('advice_date', date('Y-m-d')) }}"
                                class="form-input bg-white font-medium text-slate-900">
                            @error('advice_date')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bill Type -->
                        <div>
                            <label for="bill_type" class="form-label font-semibold text-slate-800">
                                Bill Type
                            </label>
                            <select id="bill_type" name="bill_type" class="form-select bg-white font-medium text-slate-900">
                                @foreach($billTypes as $type)
                                    <option value="{{ $type }}" @selected(old('bill_type', 'Contingency') == $type)>{{ $type }}</option>
                                @endforeach
                            </select>
                            @error('bill_type')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bill Amount -->
                        <div>
                            <label for="bill_amount" class="form-label font-semibold text-slate-800">
                                Bill Amount (₹) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="bill_amount" name="bill_amount" required
                                value="{{ old('bill_amount', '0.00') }}" placeholder="0.00"
                                class="form-input bg-white font-bold text-slate-900">
                            @error('bill_amount')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Treasury Clearance Details Grid -->
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-2 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        <span>Treasury Token, Voucher &amp; Clearance Info</span>
                    </h3>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <!-- Treasury Office Name -->
                        <div class="sm:col-span-2">
                            <label for="treasury_name" class="form-label">
                                Treasury Office Name
                            </label>
                            <input type="text" id="treasury_name" name="treasury_name"
                                value="{{ old('treasury_name', 'District Treasury Office, Gandhinagar') }}"
                                placeholder="e.g. District Treasury Office"
                                class="form-input text-slate-900">
                            @error('treasury_name')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Treasury Status -->
                        <div>
                            <label for="status" class="form-label">
                                Treasury Status
                            </label>
                            <select id="status" name="status" class="form-select font-semibold text-slate-900">
                                <option value="Submitted" @selected(old('status') == 'Submitted')>Submitted to Treasury</option>
                                <option value="Cleared" @selected(old('status') == 'Cleared')>Cleared / Passed</option>
                                <option value="Pending" @selected(old('status') == 'Pending')>Pending Clearance</option>
                                <option value="Objected" @selected(old('status') == 'Objected')>Objected / Returned</option>
                            </select>
                            @error('status')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Payment Ref / UTR No -->
                        <div>
                            <label for="payment_ref_no" class="form-label">
                                Payment Ref. / UTR No.
                            </label>
                            <input type="text" id="payment_ref_no" name="payment_ref_no"
                                value="{{ old('payment_ref_no') }}" placeholder="e.g. UTR2026090123"
                                class="form-input text-slate-900">
                            @error('payment_ref_no')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Token No -->
                        <div>
                            <label for="token_no" class="form-label">
                                Treasury Token No.
                            </label>
                            <input type="text" id="token_no" name="token_no"
                                value="{{ old('token_no') }}" placeholder="e.g. TOK/2026/0458"
                                class="form-input font-medium text-slate-900">
                            @error('token_no')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Token Date -->
                        <div>
                            <label for="token_date" class="form-label">
                                Token Date (Submission)
                            </label>
                            <input type="date" id="token_date" name="token_date"
                                value="{{ old('token_date', date('Y-m-d')) }}"
                                class="form-input text-slate-900">
                            @error('token_date')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Treasury Voucher No (TV No) -->
                        <div>
                            <label for="tv_no" class="form-label">
                                Treasury Voucher No. (TV No.)
                            </label>
                            <input type="text" id="tv_no" name="tv_no"
                                value="{{ old('tv_no') }}" placeholder="e.g. TV/2026/890"
                                class="form-input font-medium text-slate-900">
                            @error('tv_no')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- TV Date -->
                        <div>
                            <label for="tv_date" class="form-label">
                                TV Date (Clearance Date)
                            </label>
                            <input type="date" id="tv_date" name="tv_date"
                                value="{{ old('tv_date') }}"
                                class="form-input text-slate-900">
                            @error('tv_date')
                                <p class="form-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div>
                        <label for="remarks" class="form-label">
                            Remarks / Treasury Notes (Optional)
                        </label>
                        <textarea id="remarks" name="remarks" rows="2" class="form-textarea text-xs sm:text-sm"
                            placeholder="Add any treasury clearance notes or objection details...">{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <p class="form-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex flex-wrap items-center justify-end gap-3 pt-3 border-t border-emerald-50">
                    <button type="button" id="resetTreasuryFormBtn" class="secondary-button">
                        Reset Form
                    </button>
                    <button type="submit" id="submitTreasuryBtn" class="primary-button flex items-center gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Save Treasury Details</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- Section 2: Recent Treasury Records Table (Matching Party Registration Theme) -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Updated Treasury Records</h2>
                    <p class="mt-1 text-sm text-slate-500">History of Bill and Advice submissions and TV numbers</p>
                </div>
                <div class="w-full sm:w-72">
                    <input type="search" id="treasurySearchInput" placeholder="Search treasury records..." class="form-input min-h-9 text-xs py-1.5">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-14 text-center">Sr. No.</th>
                            <th>Bill No.</th>
                            <th>Advice No.</th>
                            <th>Advice Date</th>
                            <th>Bill Type</th>
                            <th class="text-right">Amount (₹)</th>
                            <th>Token No. &amp; Date</th>
                            <th>TV No. &amp; Date</th>
                            <th>Status</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="treasuryTableBody" class="divide-y divide-slate-100">
                        @forelse($records as $index => $record)
                            <tr data-treasury-row class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-medium text-slate-600">{{ $records->firstItem() + $index }}</td>
                                <td class="font-bold text-slate-900">{{ $record->bill_no }}</td>
                                <td class="font-semibold text-slate-800">{{ $record->advice_no }}</td>
                                <td class="text-xs text-slate-600">{{ $record->advice_date ? $record->advice_date->format('d/m/Y') : '-' }}</td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-800">
                                        {{ $record->bill_type ?? 'Contingency' }}
                                    </span>
                                </td>
                                <td class="font-bold text-slate-950 text-right">₹ {{ number_format($record->bill_amount, 2) }}</td>
                                <td>
                                    <div class="text-xs">
                                        <span class="font-semibold text-slate-900 block">{{ $record->token_no ?? '-' }}</span>
                                        <span class="text-[11px] text-slate-500 block">{{ $record->token_date ? $record->token_date->format('d/m/Y') : '' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-xs">
                                        <span class="font-semibold text-emerald-800 block">{{ $record->tv_no ?? '-' }}</span>
                                        <span class="text-[11px] text-slate-500 block">{{ $record->tv_date ? $record->tv_date->format('d/m/Y') : '' }}</span>
                                    </div>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($record->status) {
                                            'Cleared' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                            'Objected' => 'bg-red-100 text-red-800 border-red-200',
                                            'Pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                            default => 'bg-blue-100 text-blue-800 border-blue-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $statusClass }}">
                                        {{ $record->status }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" 
                                            class="secondary-button min-h-7 px-2.5 py-0.5 text-xs"
                                            onclick="populateTreasuryForm('{{ $record->bill_no }}', '{{ $record->advice_no }}', '{{ $record->advice_date ? $record->advice_date->format('Y-m-d') : '' }}', '{{ $record->bill_type }}', '{{ $record->bill_amount }}', '{{ addslashes($record->treasury_name ?? '') }}', '{{ $record->token_no }}', '{{ $record->token_date ? $record->token_date->format('Y-m-d') : '' }}', '{{ $record->tv_no }}', '{{ $record->tv_date ? $record->tv_date->format('Y-m-d') : '' }}', '{{ $record->status }}', '{{ addslashes($record->remarks ?? '') }}')">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('division.treasury-details.destroy', $record) }}" onsubmit="return confirm('Are you sure you want to delete this Treasury record?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="secondary-button min-h-7 border-red-200 bg-red-50 px-2.5 py-0.5 text-xs text-red-700 hover:border-red-300 hover:bg-white">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="noTreasuryRecordsRow">
                                <td colspan="10" class="text-center py-10 text-slate-500">
                                    No Treasury details recorded yet. Use the form above to add Token No. &amp; TV No.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($records->hasPages())
                <div class="border-t border-emerald-100 px-5 py-4">
                    {{ $records->links() }}
                </div>
            @endif
        </section>
    </div>

    <!-- Dynamic JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('treasuryDetailsForm');
            const alertBox = document.getElementById('treasuryAlertBox');
            const billSelect = document.getElementById('bill_no');
            const adviceSelect = document.getElementById('advice_no');
            const adviceDateInput = document.getElementById('advice_date');
            const billTypeSelect = document.getElementById('bill_type');
            const billAmountInput = document.getElementById('bill_amount');
            const treasuryNameInput = document.getElementById('treasury_name');
            const tokenNoInput = document.getElementById('token_no');
            const tokenDateInput = document.getElementById('token_date');
            const tvNoInput = document.getElementById('tv_no');
            const tvDateInput = document.getElementById('tv_date');
            const statusSelect = document.getElementById('status');
            const remarksInput = document.getElementById('remarks');
            const resetBtn = document.getElementById('resetTreasuryFormBtn');
            const searchInput = document.getElementById('treasurySearchInput');

            let billAdviceMap = {};
            try {
                billAdviceMap = JSON.parse(document.getElementById('treasuryDetailModule').dataset.billAdviceMap || '{}');
            } catch (e) {
                billAdviceMap = {};
            }

            const fetchBillTreasuryInfo = async () => {
                const billNo = billSelect.value;
                const adviceNo = adviceSelect.value;

                if (!billNo && !adviceNo) return;

                // Check local map first for immediate sync
                if (billNo && billAdviceMap[billNo]) {
                    const match = billAdviceMap[billNo].find(item => !adviceNo || item.advice_no === adviceNo);
                    if (match) {
                        if (match.advice_date) adviceDateInput.value = match.advice_date;
                        if (match.bill_type) billTypeSelect.value = match.bill_type;
                        if (match.bill_amount) billAmountInput.value = Number(match.bill_amount).toFixed(2);
                    }
                }

                // Call info endpoint
                try {
                    const url = new URL(document.getElementById('treasuryDetailModule').dataset.infoUrl, window.location.origin);
                    if (billNo) url.searchParams.set('bill_no', billNo);
                    if (adviceNo) url.searchParams.set('advice_no', adviceNo);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const data = await response.json();
                        if (data.advice_date) adviceDateInput.value = data.advice_date;
                        if (data.bill_type) billTypeSelect.value = data.bill_type;
                        if (data.bill_amount !== undefined) billAmountInput.value = Number(data.bill_amount).toFixed(2);
                        if (data.treasury_name) treasuryNameInput.value = data.treasury_name;
                        if (data.token_no) tokenNoInput.value = data.token_no;
                        if (data.token_date) tokenDateInput.value = data.token_date;
                        if (data.tv_no) tvNoInput.value = data.tv_no;
                        if (data.tv_date) tvDateInput.value = data.tv_date;
                        if (data.status) statusSelect.value = data.status;
                        if (data.remarks) remarksInput.value = data.remarks;
                    }
                } catch (error) {
                    console.error('Error fetching treasury info:', error);
                }
            };

            billSelect.addEventListener('change', function () {
                const selectedBill = billSelect.value;
                if (selectedBill && billAdviceMap[selectedBill] && billAdviceMap[selectedBill].length > 0) {
                    const firstAdvice = billAdviceMap[selectedBill][0].advice_no;
                    if (firstAdvice) {
                        adviceSelect.value = firstAdvice;
                    }
                }
                fetchBillTreasuryInfo();
            });

            adviceSelect.addEventListener('change', fetchBillTreasuryInfo);

            resetBtn?.addEventListener('click', function () {
                form.reset();
                billAmountInput.value = '0.00';
                tokenNoInput.value = '';
                tvNoInput.value = '';
                alertBox.className = 'hidden';
            });

            // Table Live Search
            searchInput?.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('[data-treasury-row]');
                let visibleCount = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            window.populateTreasuryForm = function (billNo, adviceNo, advDate, billType, amount, treasuryName, tokenNo, tokenDate, tvNo, tvDate, status, remarks) {
                billSelect.value = billNo;
                adviceSelect.value = adviceNo;
                if (advDate) adviceDateInput.value = advDate;
                if (billType) billTypeSelect.value = billType;
                billAmountInput.value = Number(amount || 0).toFixed(2);
                if (treasuryName) treasuryNameInput.value = treasuryName;
                tokenNoInput.value = tokenNo || '';
                if (tokenDate) tokenDateInput.value = tokenDate;
                tvNoInput.value = tvNo || '';
                if (tvDate) tvDateInput.value = tvDate;
                if (status) statusSelect.value = status;
                remarksInput.value = remarks || '';

                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };
        });
    </script>
</x-layouts.admin>
