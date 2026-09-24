<x-layouts.admin title="Change Bill No and Order No | Forest Inventory" heading="Change Bill No. and Order No." subheading="Division Finance System - Update Bill Register No. &amp; Order Outward No.">
    <style>
        .pill-field {
            border-radius: 9999px !important;
            border: 2px solid #5c0606 !important;
            text-align: center !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            padding: 0.625rem 1.25rem !important;
            font-size: 0.925rem !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        }
        .pill-field:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.25) !important;
            outline: none !important;
        }
        .pill-select {
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23334155' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 1rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.25em 1.25em !important;
            padding-right: 2.5rem !important;
            padding-left: 2.5rem !important;
        }
        .field-label {
            display: block !important;
            text-align: center !important;
            font-weight: 700 !important;
            color: #1e2574 !important;
            font-size: 0.875rem !important;
            margin-bottom: 0.35rem !important;
            letter-spacing: 0.01em !important;
        }
    </style>

    <div class="space-y-6" id="changeBillOrderNoModule"
        data-details-url="{{ route('division.change-bill-order-no.details') }}"
        data-store-url="{{ route('division.change-bill-order-no.store') }}"
        data-advice-map="{{ json_encode($adviceMap) }}">

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

        <div id="changeAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Main "Change Bill No and Order No:" Form Container (Exact Match to Image Theme) -->
        <section class="max-w-md mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-lg overflow-hidden transition-all">
                <!-- Modal / Card Header -->
                <div class="flex items-center justify-between px-6 py-4 bg-slate-50/70 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-800 tracking-tight">
                        Change Bill No and Order No:
                    </h2>
                    <button type="button" id="closeFormBtn" title="Close" class="text-slate-500 hover:text-slate-800 text-xl font-bold transition-colors focus:outline-none leading-none">
                        ✕
                    </button>
                </div>

                <!-- Main Form Body -->
                <form id="changeBillOrderNoForm" method="POST" action="{{ route('division.change-bill-order-no.store') }}" class="p-6 sm:p-8 space-y-5">
                    @csrf

                    <!-- 1. Bill Type -->
                    <div>
                        <label for="bill_type" class="field-label">
                            Bill Type:
                        </label>
                        <select id="bill_type" name="bill_type" required class="pill-field pill-select w-full cursor-pointer">
                            <option value="" disabled>Choose...</option>
                            @foreach($billTypes as $type)
                                <option value="{{ $type }}" @selected(old('bill_type', 'Simple Receipt') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                        @error('bill_type')
                            <p class="form-error text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 2. Advice No -->
                    <div>
                        <label for="advice_no" class="field-label">
                            Advice No:
                        </label>
                        <select id="advice_no" name="advice_no" required class="pill-field pill-select w-full cursor-pointer">
                            <option value="" disabled>Choose...</option>
                            @foreach($adviceNumbers as $aNo)
                                <option value="{{ $aNo }}" @selected(old('advice_no', $initialAdviceNo) == $aNo)>{{ $aNo }}</option>
                            @endforeach
                        </select>
                        @error('advice_no')
                            <p class="form-error text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 3. Advice Date -->
                    <div>
                        <label for="advice_date" class="field-label">
                            Advice Date:
                        </label>
                        <input type="date" id="advice_date" name="advice_date" required
                            value="{{ old('advice_date', $initialAdviceDate) }}"
                            class="pill-field w-full">
                        @error('advice_date')
                            <p class="form-error text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 4. Bill Register No -->
                    <div>
                        <label for="bill_register_no" class="field-label">
                            Bill Register No.:
                        </label>
                        <input type="text" id="bill_register_no" name="bill_register_no" required
                            value="{{ old('bill_register_no', $initialBillRegisterNo) }}"
                            placeholder="74"
                            class="pill-field w-full">
                        @error('bill_register_no')
                            <p class="form-error text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 5. Order Outward No -->
                    <div>
                        <label for="order_outward_no" class="field-label">
                            Order Outward No.:
                        </label>
                        <input type="text" id="order_outward_no" name="order_outward_no" required
                            value="{{ old('order_outward_no', $initialOrderOutwardNo) }}"
                            placeholder="બ/હસબ/૩૮૮૩/૨૦૨૬-૨૭ ત"
                            class="pill-field w-full">
                        @error('order_outward_no')
                            <p class="form-error text-center">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Remarks (Optional) -->
                    <input type="hidden" name="remarks" id="remarks" value="">

                    <!-- 6. Submit Button -->
                    <div class="pt-3 flex justify-center">
                        <button type="submit" id="btnSubmitForm"
                            class="inline-flex items-center justify-center rounded-full bg-[#1877f2] hover:bg-blue-600 text-white font-bold px-10 py-2.5 text-sm shadow-md transition-all active:scale-95 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Section 2: Updated Records History Table (Matching Division Finance System theme) -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3 bg-white">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Updated Bill &amp; Order Outward Numbers</h2>
                    <p class="mt-1 text-sm text-slate-500">History of changed Bill Register and Order Outward numbers for division advices.</p>
                </div>
                <div class="w-full sm:w-72">
                    <input type="search" id="changeSearchInput" placeholder="Search updated numbers..." class="form-input min-h-9 text-xs py-1.5">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-14 text-center">Sr. No.</th>
                            <th>Bill Type</th>
                            <th>Advice No.</th>
                            <th>Advice Date</th>
                            <th>Bill Register No.</th>
                            <th>Order Outward No.</th>
                            <th>Updated At</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="changeTableBody" class="divide-y divide-slate-100">
                        @forelse($records as $index => $record)
                            <tr data-change-row class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-medium text-slate-600">{{ $records->firstItem() + $index }}</td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800">
                                        {{ $record->bill_type }}
                                    </span>
                                </td>
                                <td class="font-bold text-blue-700">{{ $record->advice_no }}</td>
                                <td class="text-slate-700 font-medium">{{ $record->advice_date ? $record->advice_date->format('d/m/Y') : '-' }}</td>
                                <td class="font-extrabold text-slate-950">{{ $record->bill_register_no }}</td>
                                <td class="font-semibold text-emerald-900">{{ $record->order_outward_no }}</td>
                                <td class="text-xs text-slate-500">{{ $record->updated_at->format('d M Y, h:i A') }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button"
                                            class="secondary-button min-h-7 px-2.5 py-0.5 text-xs"
                                            onclick="populateChangeForm('{{ $record->bill_type }}', '{{ $record->advice_no }}', '{{ $record->advice_date ? $record->advice_date->format('Y-m-d') : '' }}', '{{ addslashes($record->bill_register_no) }}', '{{ addslashes($record->order_outward_no) }}')">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('division.change-bill-order-no.destroy', $record) }}" onsubmit="return confirm('Are you sure you want to delete this updated record?');" class="inline">
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
                            <tr id="noChangeRecordsRow">
                                <td colspan="8" class="text-center py-10 text-slate-500">
                                    No custom Bill Register or Order Outward updates recorded yet. Use the form above to modify numbers.
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
            const form = document.getElementById('changeBillOrderNoForm');
            const alertBox = document.getElementById('changeAlertBox');
            const billTypeSelect = document.getElementById('bill_type');
            const adviceNoSelect = document.getElementById('advice_no');
            const adviceDateInput = document.getElementById('advice_date');
            const billRegisterInput = document.getElementById('bill_register_no');
            const orderOutwardInput = document.getElementById('order_outward_no');
            const searchInput = document.getElementById('changeSearchInput');
            const closeFormBtn = document.getElementById('closeFormBtn');

            let adviceMap = {};
            try {
                adviceMap = JSON.parse(document.getElementById('changeBillOrderNoModule').dataset.adviceMap || '{}');
            } catch (e) {
                adviceMap = {};
            }

            const fetchDetails = async () => {
                const billType = billTypeSelect.value;
                const adviceNo = adviceNoSelect.value;

                if (!adviceNo) return;

                // 1. Instant sync from local client dataset if available
                if (adviceMap[billType] && adviceMap[billType][adviceNo]) {
                    const data = adviceMap[billType][adviceNo];
                    if (data.advice_date) adviceDateInput.value = data.advice_date;
                    if (data.bill_register_no) billRegisterInput.value = data.bill_register_no;
                    if (data.order_outward_no) orderOutwardInput.value = data.order_outward_no;
                }

                // 2. Fetch from backend endpoint for live accurate data
                try {
                    const url = new URL(document.getElementById('changeBillOrderNoModule').dataset.detailsUrl, window.location.origin);
                    url.searchParams.set('bill_type', billType);
                    url.searchParams.set('advice_no', adviceNo);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const res = await response.json();
                        if (res.advice_date) adviceDateInput.value = res.advice_date;
                        if (res.bill_register_no) billRegisterInput.value = res.bill_register_no;
                        if (res.order_outward_no) orderOutwardInput.value = res.order_outward_no;
                    }
                } catch (err) {
                    console.error('Error fetching advice details:', err);
                }
            };

            billTypeSelect.addEventListener('change', fetchDetails);
            adviceNoSelect.addEventListener('change', fetchDetails);

            closeFormBtn?.addEventListener('click', function () {
                // Flash subtle reset feedback on close button
                form.reset();
                billTypeSelect.value = 'Simple Receipt';
                adviceNoSelect.value = '66';
                adviceDateInput.value = '{{ date("Y-m-d") }}';
                billRegisterInput.value = '74';
                orderOutwardInput.value = 'બ/હસબ/૩૮૮૩/{{ date("Y") }}-{{ substr(date("Y") + 1, 2) }} ત';
            });

            // Table Live Search
            searchInput?.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('[data-change-row]');
                let count = 0;

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                        count++;
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            window.populateChangeForm = function (billType, adviceNo, adviceDate, billRegNo, orderOutwardNo) {
                if (billType) billTypeSelect.value = billType;
                if (adviceNo) adviceNoSelect.value = adviceNo;
                if (adviceDate) adviceDateInput.value = adviceDate;
                if (billRegNo) billRegisterInput.value = billRegNo;
                if (orderOutwardNo) orderOutwardInput.value = orderOutwardNo;

                form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            };
        });
    </script>
</x-layouts.admin>
