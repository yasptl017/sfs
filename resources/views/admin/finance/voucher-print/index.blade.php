<x-layouts.admin title="Voucher Print | Forest Inventory" heading="Voucher Print" subheading="Range Finance System - Print and Generate Official Range Vouchers">
    <style>
        .maroon-border-box {
            border: 2px solid #5c0606 !important;
            border-radius: 0.5rem !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            padding: 0.65rem 0.85rem !important;
            font-size: 0.95rem !important;
            transition: all 0.15s ease-in-out !important;
        }
        .maroon-border-box:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.25) !important;
            outline: none !important;
        }
        .sky-focus-select {
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23334155' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.85rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.25em 1.25em !important;
            padding-right: 2.5rem !important;
            border: 2px solid #38bdf8 !important;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.2) !important;
            border-radius: 0.5rem !important;
        }
    </style>

    <div class="space-y-6" id="voucherPrintModule"
        data-entries-url="{{ route('finance.voucher-print.entries') }}"
        data-preview-url="{{ route('finance.voucher-print.preview') }}"
        data-print-url="{{ route('finance.voucher-print.print') }}">

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

        <div id="voucherPrintAlert" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Main "Voucher Print:" Form Card (Exact Match to Reference Images) -->
        <section class="max-w-md mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-lg overflow-hidden transition-all">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 bg-slate-50/80 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-800 tracking-tight">
                        Voucher Print:
                    </h2>
                    <button type="button" id="closeVoucherFormBtn" title="Close" class="text-slate-500 hover:text-slate-800 text-xl font-bold transition-colors focus:outline-none leading-none">
                        ✕
                    </button>
                </div>

                <!-- Form Body -->
                <form id="voucherPrintForm" method="GET" action="{{ route('finance.voucher-print.print') }}" target="_blank" class="p-6 sm:p-7 space-y-4">
                    <!-- 1. Which entry to print ? -->
                    <div>
                        <label for="entry_type" class="block text-sm font-semibold text-slate-800 mb-1.5">
                            Which entry to print ?
                        </label>
                        <select id="entry_type" name="entry_type" required class="sky-focus-select w-full bg-white font-semibold text-slate-900 text-sm py-2.5 px-3.5 cursor-pointer">
                            @foreach($entryTypes as $type)
                                <option value="{{ $type }}" @selected($selectedType === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Serial numbers -->
                    <div>
                        <div class="flex flex-col gap-1 mb-1.5">
                            <label for="serial_numbers" class="text-sm font-semibold text-slate-800">
                                Serial numbers
                            </label>
                            <div>
                                <span class="bg-amber-300 text-slate-900 font-bold px-1.5 py-0.5 rounded text-xs inline-block">
                                    seperated by comma / dash
                                </span>
                            </div>
                        </div>
                        <input type="text" id="serial_numbers" name="serial_numbers"
                            value="{{ $suggestedRange }}"
                            placeholder="e.g. 1-4, 19, 23-25, 47"
                            class="maroon-border-box w-full font-medium text-slate-900 placeholder:text-slate-400">
                        <p class="mt-1 text-[11px] text-slate-500">Leave blank to print all available vouchers of selected entry type.</p>
                    </div>

                    <!-- 3. Checkboxes -->
                    <div class="space-y-2.5 pt-1">
                        <!-- Checkbox 1: With Work Order Numbers -->
                        <label class="flex items-center gap-2.5 text-sm font-medium text-slate-800 cursor-pointer select-none">
                            <input type="checkbox" id="with_work_order" name="with_work_order" value="1"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer">
                            <span>With Work Order Numbers</span>
                        </label>

                        <!-- Checkbox 2: Fit to page (Gujarati note) -->
                        <label class="flex items-start gap-2.5 text-sm font-medium text-slate-800 cursor-pointer select-none">
                            <input type="checkbox" id="fit_to_page" name="fit_to_page" value="1"
                                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer mt-0.5">
                            <span class="leading-tight">
                                Fit to page <span class="text-xs text-slate-500">(કોઈ પણ સંજોગોમા એક જ પેજ મા રાખો)</span>
                            </span>
                        </label>
                    </div>

                    <!-- 4. Print Action Buttons -->
                    <div class="pt-2 flex items-center gap-3">
                        <button type="submit" id="btnDirectPrint"
                            class="inline-flex items-center justify-center rounded-md bg-[#1877f2] hover:bg-blue-600 text-white font-bold px-6 py-2 text-sm shadow-sm transition-all active:scale-95 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            Print
                        </button>
                        <button type="button" id="btnLivePreview"
                            class="secondary-button text-xs py-2 px-4 border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100 flex items-center gap-1.5">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                            </svg>
                            <span>Preview Online</span>
                        </button>
                    </div>

                    <!-- 5. Instructions Note Box (Exact text from screenshot) -->
                    <div class="pt-4 border-t border-slate-100 text-xs text-slate-700 space-y-2.5 leading-relaxed bg-slate-50/60 -mx-6 -mb-6 p-5 sm:-mx-7 sm:-mb-7 sm:p-6 border-b-0">
                        <p class="text-slate-800 font-medium">
                            જો Approval No. બોક્સમાં, letter numbers પ્રિન્ટ ના થયા હોય તો ડિવિજન માંથી પાર્ટી એન્ટ્રી કરતી વખતે એન્ટર કરવાના રહી ગયા હશે, તેમને જાણ કરો અને પાર્ટી એડિટ કરવાનું કહો ('Division Finance System' માંથી 'Entry' માં 'Party Registration').
                        </p>
                        <p class="text-slate-600">
                            In Approval No. box, if letter numbers are not printed, then ask division to write letter numbers using 'Party Registration' menu of 'Division Finance System'.
                        </p>
                    </div>
                </form>
            </div>
        </section>

        <!-- Section 2: Online Live Voucher Preview Container -->
        <section id="voucherPreviewSection" class="hidden rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-4 bg-emerald-50/40 gap-3">
                <div class="flex items-center gap-3">
                    <span id="previewEntryTypeBadge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Tender Entry
                    </span>
                    <h3 id="previewTitle" class="text-base font-semibold text-slate-950">Voucher Preview</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrintLoadedVouchers" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Print Vouchers</span>
                    </button>
                    <button type="button" id="btnClosePreview" class="text-slate-400 hover:text-slate-700 text-xl font-bold px-2">
                        &times;
                    </button>
                </div>
            </div>

            <!-- Vouchers List Container -->
            <div id="voucherCardsContainer" class="p-6 space-y-8 bg-stone-50/50">
                <!-- Rendered dynamically by JavaScript -->
            </div>
        </section>

        <!-- Section 3: Available Entries of Selected Type -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3 bg-white">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Available Vouchers in this Range</h2>
                    <p class="mt-1 text-sm text-slate-500">Quickly select or inspect vouchers recorded under <span id="tableEntryTypeLabel" class="font-bold text-slate-800">{{ $selectedType }}</span>.</p>
                </div>
                <div class="w-full sm:w-72">
                    <input type="search" id="voucherTableSearch" placeholder="Search vouchers..." class="form-input min-h-9 text-xs py-1.5">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Sr. No.</th>
                            <th>Entry Type</th>
                            <th>Party / Vendor Name</th>
                            <th>Budget Code</th>
                            <th>Docket No.</th>
                            <th class="text-right">Amount (₹)</th>
                            <th>Date</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="availableEntriesTableBody" class="divide-y divide-slate-100">
                        @forelse($availableEntries as $entry)
                            <tr data-entry-row class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-bold text-slate-900">{{ $entry['serial_number'] }}</td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800">
                                        {{ $entry['entry_type'] }}
                                    </span>
                                </td>
                                <td class="font-bold text-slate-950">{{ $entry['party_name'] }}</td>
                                <td class="font-mono text-xs text-emerald-800 font-semibold">{{ $entry['budget_code'] }}</td>
                                <td class="text-xs text-slate-600 font-mono">{{ $entry['docket_no'] }}</td>
                                <td class="text-right font-bold text-slate-950">₹ {{ number_format($entry['amount'], 2) }}</td>
                                <td class="text-xs text-slate-600">{{ $entry['date'] }}</td>
                                <td class="text-right">
                                    <button type="button"
                                        class="secondary-button min-h-7 px-2.5 py-0.5 text-xs"
                                        onclick="printSingleVoucher('{{ $entry['entry_type'] }}', '{{ $entry['serial_number'] }}')">
                                        Print Voucher
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr id="noEntriesRow">
                                <td colspan="8" class="text-center py-10 text-slate-500">
                                    No voucher entries recorded yet for this entry type.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Dynamic JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('voucherPrintForm');
            const entryTypeSelect = document.getElementById('entry_type');
            const serialNumbersInput = document.getElementById('serial_numbers');
            const withWorkOrderCheckbox = document.getElementById('with_work_order');
            const fitToPageCheckbox = document.getElementById('fit_to_page');
            const btnLivePreview = document.getElementById('btnLivePreview');
            const closeVoucherFormBtn = document.getElementById('closeVoucherFormBtn');
            const previewSection = document.getElementById('voucherPreviewSection');
            const previewCardsContainer = document.getElementById('voucherCardsContainer');
            const previewEntryTypeBadge = document.getElementById('previewEntryTypeBadge');
            const previewTitle = document.getElementById('previewTitle');
            const btnPrintLoadedVouchers = document.getElementById('btnPrintLoadedVouchers');
            const btnClosePreview = document.getElementById('btnClosePreview');
            const tableEntryTypeLabel = document.getElementById('tableEntryTypeLabel');
            const tableBody = document.getElementById('availableEntriesTableBody');
            const tableSearchInput = document.getElementById('voucherTableSearch');

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            // Switch entry type: fetch entries and update suggested serials
            entryTypeSelect.addEventListener('change', async function () {
                const selected = this.value;
                tableEntryTypeLabel.textContent = selected;

                try {
                    const url = new URL(document.getElementById('voucherPrintModule').dataset.entriesUrl, window.location.origin);
                    url.searchParams.set('entry_type', selected);

                    const response = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const res = await response.json();
                        serialNumbersInput.value = res.suggested_range || '';
                        renderTableEntries(res.entries || []);
                    }
                } catch (e) {
                    console.error('Error switching entry type:', e);
                }
            });

            const renderTableEntries = (entries) => {
                if (entries.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-500">
                                No voucher entries found for ${entryTypeSelect.value}.
                            </td>
                        </tr>
                    `;
                    return;
                }

                tableBody.innerHTML = entries.map(e => `
                    <tr data-entry-row class="transition-colors hover:bg-emerald-50/40">
                        <td class="text-center font-bold text-slate-900">${e.serial_number}</td>
                        <td>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800">
                                ${e.entry_type}
                            </span>
                        </td>
                        <td class="font-bold text-slate-950">${e.party_name}</td>
                        <td class="font-mono text-xs text-emerald-800 font-semibold">${e.budget_code}</td>
                        <td class="text-xs text-slate-600 font-mono">${e.docket_no}</td>
                        <td class="text-right font-bold text-slate-950">${formatMoney(e.amount)}</td>
                        <td class="text-xs text-slate-600">${e.date}</td>
                        <td class="text-right">
                            <button type="button"
                                class="secondary-button min-h-7 px-2.5 py-0.5 text-xs"
                                onclick="printSingleVoucher('${e.entry_type}', '${e.serial_number}')">
                                Print Voucher
                            </button>
                        </td>
                    </tr>
                `).join('');
            };

            // Live Preview Action
            btnLivePreview.addEventListener('click', async function () {
                const entryType = entryTypeSelect.value;
                const serialNumbers = serialNumbersInput.value;
                const withWorkOrder = withWorkOrderCheckbox.checked;
                const fitToPage = fitToPageCheckbox.checked;

                try {
                    const response = await fetch(document.getElementById('voucherPrintModule').dataset.previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            entry_type: entryType,
                            serial_numbers: serialNumbers,
                            with_work_order: withWorkOrder ? 1 : 0,
                            fit_to_page: fitToPage ? 1 : 0
                        })
                    });

                    if (response.ok) {
                        const res = await response.json();
                        renderVoucherCards(res.vouchers || []);
                        previewEntryTypeBadge.textContent = entryType;
                        previewTitle.textContent = `${entryType} (${res.count} Vouchers)`;
                        previewSection.classList.remove('hidden');
                        previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } else {
                        const err = await response.json();
                        alert(err.message || 'Error generating preview.');
                    }
                } catch (e) {
                    console.error('Preview error:', e);
                    alert('Failed to generate preview. Please try again.');
                }
            });

            const renderVoucherCards = (vouchers) => {
                if (vouchers.length === 0) {
                    previewCardsContainer.innerHTML = `
                        <div class="text-center py-12 text-slate-500 bg-white rounded-xl border border-slate-200">
                            No vouchers found for the specified serial numbers.
                        </div>
                    `;
                    return;
                }

                previewCardsContainer.innerHTML = vouchers.map(v => `
                    <div class="bg-white rounded-xl border-2 border-slate-300 p-6 sm:p-8 shadow-md space-y-6">
                        <!-- Voucher Header -->
                        <div class="border-b-2 border-slate-900 pb-4 text-center space-y-1">
                            <h2 class="text-lg font-black text-slate-950 uppercase tracking-wide">GUJARAT FOREST DEPARTMENT</h2>
                            <h3 class="text-sm font-bold text-slate-800">${v.division_name} - ${v.range_name}</h3>
                            <h4 class="text-xs font-extrabold text-emerald-800 uppercase tracking-widest">${v.entry_type} VOUCHER (FORM NO. 35)</h4>
                            <div class="flex flex-wrap justify-between items-center text-xs text-slate-700 pt-2 font-semibold">
                                <span><strong>Voucher No:</strong> <span class="text-emerald-900 font-bold">${v.voucher_no}</span> (Sr. No. ${v.serial_number})</span>
                                <span><strong>Docket No:</strong> ${v.docket_no}</span>
                                <span><strong>Month:</strong> ${v.month}</span>
                                <span><strong>Date:</strong> ${v.date}</span>
                            </div>
                        </div>

                        <!-- Budget & Location Details -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs bg-slate-50 p-3 rounded-lg border border-slate-200">
                            <div><span class="text-slate-500 block text-[11px]">Budget Code:</span><strong class="text-slate-900 font-mono">${v.budget_code}</strong></div>
                            <div><span class="text-slate-500 block text-[11px]">Scheme:</span><strong class="text-slate-900">${v.scheme}</strong></div>
                            <div><span class="text-slate-500 block text-[11px]">Round / Beat:</span><strong class="text-slate-900">${v.round} / ${v.beat}</strong></div>
                            <div><span class="text-slate-500 block text-[11px]">Place / Survey:</span><strong class="text-slate-900">${v.place}</strong></div>
                        </div>

                        <!-- Payee & Approval Details -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs border border-slate-200 p-3.5 rounded-lg">
                            <div>
                                <span class="text-slate-500 block text-[11px] font-bold uppercase">Payee / Party Details:</span>
                                <p class="text-sm font-bold text-slate-950">${v.party_name}</p>
                                <p class="text-slate-600">${v.party_address}</p>
                                <p class="text-slate-600 font-mono text-[11px]">PAN: ${v.pan_card_no} | GSTIN: ${v.gst_no}</p>
                                <p class="text-slate-600 font-mono text-[11px]">${v.bank_name} - A/C: ${v.account_no} (IFSC: ${v.ifsc})</p>
                            </div>
                            <div class="space-y-1 sm:border-l sm:border-slate-200 sm:pl-4">
                                <div><span class="text-slate-500 block text-[11px] font-bold uppercase">Approval / Letter No:</span><strong class="text-emerald-900 font-mono text-xs">${v.approval_no || 'None'}</strong></div>
                                ${v.with_work_order ? `
                                    <div><span class="text-slate-500 block text-[11px] font-bold uppercase">Work Order No:</span><strong class="text-blue-900 font-mono text-xs">${v.work_order_no} (Dt. ${v.work_order_date})</strong></div>
                                ` : ''}
                            </div>
                        </div>

                        <!-- Itemized Measurement Table -->
                        <table class="w-full text-xs border border-slate-300 border-collapse">
                            <thead>
                                <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                    <th class="p-2 border border-slate-300 text-center w-10">Sr.</th>
                                    <th class="p-2 border border-slate-300 text-left">Description of Works &amp; Operations</th>
                                    <th class="p-2 border border-slate-300 text-center w-14">L</th>
                                    <th class="p-2 border border-slate-300 text-center w-14">B</th>
                                    <th class="p-2 border border-slate-300 text-center w-14">D</th>
                                    <th class="p-2 border border-slate-300 text-right w-16">Qty</th>
                                    <th class="p-2 border border-slate-300 text-center w-14">Unit</th>
                                    <th class="p-2 border border-slate-300 text-right w-20">Rate (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right w-24">Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${v.items.map(it => `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-bold">${it.sr_no}</td>
                                        <td class="p-2 border border-slate-300 text-slate-900 leading-relaxed">${it.description}</td>
                                        <td class="p-2 border border-slate-300 text-center font-mono">${it.length || '-'}</td>
                                        <td class="p-2 border border-slate-300 text-center font-mono">${it.breadth || '-'}</td>
                                        <td class="p-2 border border-slate-300 text-center font-mono">${it.depth || '-'}</td>
                                        <td class="p-2 border border-slate-300 text-right font-bold">${it.quantity}</td>
                                        <td class="p-2 border border-slate-300 text-center">${it.unit}</td>
                                        <td class="p-2 border border-slate-300 text-right font-medium">${formatMoney(it.rate)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(it.amount)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-50 font-bold border-t-2 border-slate-800">
                                    <td colspan="8" class="p-2 border border-slate-300 text-right uppercase">Gross Amount (કુલ રકમ):</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold text-slate-950">${formatMoney(v.gross_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Deductions & Net Payable Summary -->
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 text-xs border-t border-slate-200 pt-4">
                            <div class="w-full sm:w-1/2 space-y-1 bg-slate-50 p-3 rounded border border-slate-200">
                                <span class="font-bold text-slate-800 block uppercase text-[11px] mb-1">Deductions (કપાત વિગત):</span>
                                <div class="flex justify-between text-slate-600"><span>SGST:</span><span>${formatMoney(v.deductions.sgst)}</span></div>
                                <div class="flex justify-between text-slate-600"><span>CGST:</span><span>${formatMoney(v.deductions.cgst)}</span></div>
                                <div class="flex justify-between text-slate-600"><span>Labour Cess:</span><span>${formatMoney(v.deductions.labour_cess)}</span></div>
                                <div class="flex justify-between text-slate-600"><span>Security Deposit:</span><span>${formatMoney(v.deductions.deposit)}</span></div>
                                <div class="flex justify-between text-slate-600"><span>TDS / IT:</span><span>${formatMoney(v.deductions.tds)}</span></div>
                                <div class="flex justify-between font-bold text-rose-800 border-t border-slate-200 pt-1"><span>Total Deductions:</span><span>${formatMoney(v.deductions.total)}</span></div>
                            </div>
                            <div class="w-full sm:w-1/2 space-y-2 text-right">
                                <div class="p-3 bg-emerald-50 rounded border border-emerald-200">
                                    <span class="text-[11px] text-emerald-800 uppercase font-bold block">Net Payable Amount (ચુકવવાપાત્ર ચોખ્ખી રકમ):</span>
                                    <span class="text-xl font-extrabold text-emerald-950 block">${formatMoney(v.net_amount)}</span>
                                    <span class="text-xs text-slate-700 italic block mt-1">${v.net_amount_in_words}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Departmental Signatures -->
                        <div class="grid grid-cols-4 gap-2 pt-8 text-center text-xs font-bold text-slate-800 border-t border-slate-300">
                            <div><div class="border-t border-slate-400 pt-1.5">Beat Guard / Forester</div></div>
                            <div><div class="border-t border-slate-400 pt-1.5">Cashier / Clerk</div></div>
                            <div><div class="border-t border-slate-400 pt-1.5">Range Forest Officer (RFO)</div></div>
                            <div><div class="border-t border-slate-400 pt-1.5">Payee Signature</div></div>
                        </div>
                    </div>
                `).join('');
            };

            btnPrintLoadedVouchers.addEventListener('click', function () {
                const entryType = entryTypeSelect.value;
                const serialNumbers = serialNumbersInput.value;
                const withWorkOrder = withWorkOrderCheckbox.checked ? '1' : '0';
                const fitToPage = fitToPageCheckbox.checked ? '1' : '0';

                const url = new URL(document.getElementById('voucherPrintModule').dataset.printUrl, window.location.origin);
                url.searchParams.set('entry_type', entryType);
                url.searchParams.set('serial_numbers', serialNumbers);
                url.searchParams.set('with_work_order', withWorkOrder);
                url.searchParams.set('fit_to_page', fitToPage);

                window.open(url.toString(), '_blank');
            });

            btnClosePreview?.addEventListener('click', function () {
                previewSection.classList.add('hidden');
            });

            closeVoucherFormBtn?.addEventListener('click', function () {
                form.reset();
                entryTypeSelect.value = 'Tender Entry';
                serialNumbersInput.value = '1-4, 19, 23-25, 47';
            });

            // Live Table Search
            tableSearchInput?.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('[data-entry-row]');
                rows.forEach(r => {
                    const text = r.textContent.toLowerCase();
                    r.style.display = text.includes(query) ? '' : 'none';
                });
            });

            window.printSingleVoucher = function (type, srNo) {
                entryTypeSelect.value = type;
                serialNumbersInput.value = String(srNo);
                form.submit();
            };
        });
    </script>
</x-layouts.admin>
