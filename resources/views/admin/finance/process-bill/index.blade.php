<x-layouts.admin title="Process Bill (Advice) | Forest Inventory" heading="Process Bill (Advice)" subheading="Division Finance System - Bill / Advice Processing & Generation">
    <style>
        #billsTable {
            border-collapse: collapse;
            width: 100%;
        }
        #billsTable th, #billsTable td {
            padding: 0.35rem 0.45rem !important;
            font-size: 0.75rem !important;
            line-height: 1.25 !important;
            vertical-align: middle;
        }
        #billsTable th {
            font-size: 0.68rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.025em !important;
            color: rgb(71, 85, 105) !important;
            background-color: rgb(248, 250, 252) !important;
            border-bottom: 1px solid rgb(209, 250, 229) !important;
            white-space: nowrap !important;
        }
        #billsTable td {
            border-top: 1px solid rgb(241, 245, 249) !important;
        }
        #billsTable .col-desc {
            min-width: 450px !important;
            width: 100% !important;
            white-space: normal !important;
            word-break: break-word !important;
            padding-left: 0.65rem !important;
            padding-right: 0.65rem !important;
        }
        #billsTable th.col-desc {
            white-space: normal !important;
        }
    </style>

    <div class="space-y-5" id="processBillModule"
        data-entries="{{ json_encode($entries) }}"
        data-filter-fields="{{ json_encode($filterFields) }}"
        data-store-url="{{ route('division.process-bill.store') }}">

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

        <div id="processBillAlert" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Bill Type Selector & Options (Matching Party Registration Theme) -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Select Bill Type</h2>
                    <p class="mt-1 text-sm text-slate-500">Choose bill type to view and process voucher entries into Advice batches.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Active Type:</span>
                    <span id="currentBillTypeBadge" class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 border border-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        <span id="activeBillTypeText">{{ $selectedBillType ?? 'Contingency' }}</span>
                    </span>
                </div>
            </div>

            <div class="p-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">
                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="form-label" for="billTypeSelect">Bill Type</label>
                    <select id="billTypeSelect" class="form-select font-medium text-slate-900">
                        @foreach($billTypes as $type)
                            <option value="{{ $type }}" @selected(($selectedBillType ?? 'Contingency') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="proceedBillTypeBtn" class="primary-button w-full">
                        Load Bills
                    </button>
                </div>

                <div class="flex items-center justify-end">
                    <button type="button" id="btnVerifyAndProcess" class="primary-button bg-emerald-700 hover:bg-emerald-800 w-full flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                        <span>Verify and Process</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Section 2: Action Controls & 3-Level Multi Filters -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">Quick Selection &amp; Multi-Level Filters</h2>
                <p class="mt-1 text-sm text-slate-500">Filter, search, and bulk select voucher entries for advice batching.</p>
            </div>

            <div class="p-5 space-y-4">
                <!-- Action Buttons & Search Row -->
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" id="btnSelectReceived" class="secondary-button text-xs py-1.5 px-3 border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100">
                            Select Received
                        </button>
                        <button type="button" id="btnShowAll" class="secondary-button text-xs py-1.5 px-3 border-emerald-200 bg-emerald-50 text-emerald-800 hover:bg-emerald-100">
                            Show All
                        </button>
                        <button type="button" id="btnShowSelected" class="secondary-button text-xs py-1.5 px-3 border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100">
                            Show Selected
                        </button>
                        <button type="button" id="btnShowRed" class="secondary-button text-xs py-1.5 px-3 border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">
                            Show Red
                        </button>
                        <button type="button" id="btnDeselectAll" class="secondary-button text-xs py-1.5 px-3 border-slate-200 bg-slate-50 text-slate-700 hover:bg-slate-100">
                            Deselect All
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
                        <input type="text" id="mainSearchInput" placeholder="Search entries..."
                            class="form-input text-xs py-1.5 w-full sm:w-48">

                        <select id="mainSearchColumn" class="form-select text-xs py-1.5 w-full sm:w-36">
                            <option value="all">Search in all</option>
                            @foreach($filterFields as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <button type="button" id="btnSearchSelect" class="secondary-button text-xs py-1.5 px-3">
                            Select Matching
                        </button>
                    </div>
                </div>

                <!-- 3-Level Filter Grid -->
                <div class="pt-3 border-t border-emerald-50 flex flex-wrap items-center gap-3">
                    <span class="text-xs font-bold text-slate-700">Filter Levels:</span>

                    <!-- Filter 1 -->
                    <div class="flex items-center gap-1 bg-stone-50 p-1.5 rounded-md border border-slate-200">
                        <span class="text-[11px] font-bold text-slate-500 px-1">1</span>
                        <select id="filterField1" class="form-select text-xs py-1 px-2 border-slate-200 bg-white">
                            <option value="">Choose Field...</option>
                            @foreach($filterFields as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select id="filterVal1" class="form-select text-xs py-1 px-2 border-slate-200 bg-white" disabled>
                            <option value="">Choose Value...</option>
                        </select>
                    </div>

                    <!-- Filter 2 -->
                    <div class="flex items-center gap-1 bg-stone-50 p-1.5 rounded-md border border-slate-200">
                        <span class="text-[11px] font-bold text-slate-500 px-1">2</span>
                        <select id="filterField2" class="form-select text-xs py-1 px-2 border-slate-200 bg-white">
                            <option value="">Choose Field...</option>
                            @foreach($filterFields as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select id="filterVal2" class="form-select text-xs py-1 px-2 border-slate-200 bg-white" disabled>
                            <option value="">Choose Value...</option>
                        </select>
                    </div>

                    <!-- Filter 3 -->
                    <div class="flex items-center gap-1 bg-stone-50 p-1.5 rounded-md border border-slate-200">
                        <span class="text-[11px] font-bold text-slate-500 px-1">3</span>
                        <select id="filterField3" class="form-select text-xs py-1 px-2 border-slate-200 bg-white">
                            <option value="">Choose Field...</option>
                            @foreach($filterFields as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <select id="filterVal3" class="form-select text-xs py-1 px-2 border-slate-200 bg-white" disabled>
                            <option value="">Choose Value...</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" id="btnApplyFilterSelect" class="secondary-button text-xs py-1 px-3 border-emerald-200 bg-emerald-50 text-emerald-800">
                            Select
                        </button>
                        <button type="button" id="btnApplyFilterDeselect" class="secondary-button text-xs py-1 px-3 border-red-200 bg-red-50 text-red-700">
                            Deselect
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: Voucher Bills Data Table (Matching Party Registration Table Theme) -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-3.5 bg-emerald-50/40 gap-3">
                <div class="text-sm font-semibold text-slate-900" id="bottomCounterDisplay">
                    Total of Selected = <span id="sumSelectedAmount" class="text-emerald-800 font-bold">₹ 0.00</span> ; No. of Selected = <span id="countSelectedRows" class="text-emerald-800 font-bold">0</span> out of <span id="totalVisibleRows">0</span>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" id="btnQuickSelectAll" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900">
                        Select All Visible
                    </button>
                    <span class="text-slate-300">|</span>
                    <button type="button" id="btnQuickClear" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                        Clear Selection
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto max-h-[620px] overflow-y-auto scrollbar-thin">
                <table class="data-table w-full text-left" id="billsTable">
                    <thead class="sticky top-0 z-10 uppercase tracking-wider">
                        <tr>
                            <th class="text-center w-7 !px-1">
                                <input type="checkbox" id="masterCheckbox" class="rounded border-slate-300 text-emerald-600 focus:ring-0 cursor-pointer">
                            </th>
                            <th class="w-16">Range</th>
                            <th class="w-10 text-center">Month</th>
                            <th class="w-10 text-center">Docket</th>
                            <th class="w-10 text-center">Sr. No.</th>
                            <th class="w-18">Entry Type</th>
                            <th class="w-10 text-center">No. Edi.</th>
                            <th class="w-16 text-center">Budget Code</th>
                            <th class="w-12 text-center">Head</th>
                            <th class="w-20">Scheme</th>
                            <th class="w-10 text-center">Class</th>
                            <th class="w-20">Model</th>
                            <th class="w-24">Party Name</th>
                            <th class="w-20 text-right">Amount (₹)</th>
                            <th class="w-16 text-center">Received?</th>
                            <th class="col-desc">All Descriptions</th>
                            <th class="w-14 text-center">EDP Code</th>
                        </tr>
                    </thead>
                    <tbody id="billsTableBody" class="divide-y divide-slate-100">
                        <!-- Populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Verify & Process Advice Modal (Themed) -->
        <div id="processModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
            <div class="w-full max-w-lg bg-white rounded-xl border border-emerald-100 shadow-2xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-emerald-100 bg-emerald-50/50">
                    <div>
                        <h3 class="text-base font-semibold text-slate-950">Generate &amp; Process Advice</h3>
                        <p class="text-xs text-slate-500">Confirm selected batch of bills for advice generation</p>
                    </div>
                    <button type="button" id="closeProcessModalBtn" class="text-slate-400 hover:text-slate-700 text-2xl leading-none">&times;</button>
                </div>

                <form id="adviceProcessForm" method="POST" action="{{ route('division.process-bill.store') }}" class="p-6 space-y-4">
                    @csrf
                    <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 space-y-2">
                        <div class="flex justify-between text-xs text-emerald-900">
                            <span>Selected Bills:</span>
                            <span class="font-bold" id="modalBillCount">0 Bills</span>
                        </div>
                        <div class="flex justify-between text-base text-emerald-950 font-bold border-t border-emerald-200/80 pt-2">
                            <span>Total Amount:</span>
                            <span id="modalTotalAmount">₹ 0.00</span>
                        </div>
                    </div>

                    <input type="hidden" name="bill_type" id="modalBillTypeInput" value="{{ $selectedBillType ?? 'Contingency' }}">
                    <input type="hidden" name="total_amount" id="modalTotalAmountInput" value="0">
                    <div id="modalHiddenEntriesContainer"></div>

                    <div>
                        <label class="form-label" for="advice_no">Advice No. / Order No.</label>
                        <input type="text" id="advice_no" name="advice_no" required placeholder="e.g. ADV/2026/001"
                            value="ADV/{{ date('Y') }}/{{ str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT) }}"
                            class="form-input">
                    </div>

                    <div>
                        <label class="form-label" for="advice_date">Advice Date</label>
                        <input type="date" id="advice_date" name="advice_date" required value="{{ date('Y-m-d') }}" class="form-input">
                    </div>

                    <div>
                        <label class="form-label" for="remarks">Remarks (Optional)</label>
                        <textarea id="remarks" name="remarks" rows="2" class="form-textarea text-xs" placeholder="Add any approval notes..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" id="cancelProcessBtn" class="secondary-button">Cancel</button>
                        <button type="submit" class="primary-button">Confirm &amp; Generate Advice</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 4: History of Processed Advices -->
        @if($history->isNotEmpty())
            <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-emerald-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-slate-950">Recently Processed Advices</h2>
                    <p class="mt-1 text-sm text-slate-500">Batches processed for Bill / Advice generation</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>Advice No.</th>
                                <th>Date</th>
                                <th>Bill Type</th>
                                <th>Bills Processed</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Generated At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $item)
                                <tr>
                                    <td class="font-bold text-emerald-800">{{ $item->advice_no }}</td>
                                    <td>{{ $item->advice_date->format('d M Y') }}</td>
                                    <td><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-800">{{ $item->bill_type }}</span></td>
                                    <td class="font-semibold">{{ $item->total_bills_count }} bills</td>
                                    <td class="font-bold text-emerald-700">₹ {{ number_format($item->total_amount, 2) }}</td>
                                    <td><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">{{ $item->status }}</span></td>
                                    <td class="text-xs text-slate-500">{{ $item->created_at->format('d M Y, h:i A') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    <!-- Interactive Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('processBillModule');
            let rawEntries = JSON.parse(container.dataset.entries || '[]');
            const filterFields = JSON.parse(container.dataset.filterFields || '{}');

            // Elements
            const tableBody = document.getElementById('billsTableBody');
            const masterCheckbox = document.getElementById('masterCheckbox');
            const sumSelectedAmount = document.getElementById('sumSelectedAmount');
            const countSelectedRows = document.getElementById('countSelectedRows');
            const totalVisibleRows = document.getElementById('totalVisibleRows');
            const activeBillTypeText = document.getElementById('activeBillTypeText');

            const billTypeSelect = document.getElementById('billTypeSelect');
            const proceedBillTypeBtn = document.getElementById('proceedBillTypeBtn');

            const processModal = document.getElementById('processModal');
            const btnVerifyAndProcess = document.getElementById('btnVerifyAndProcess');
            const closeProcessModalBtn = document.getElementById('closeProcessModalBtn');
            const cancelProcessBtn = document.getElementById('cancelProcessBtn');
            const modalBillCount = document.getElementById('modalBillCount');
            const modalTotalAmount = document.getElementById('modalTotalAmount');
            const modalTotalAmountInput = document.getElementById('modalTotalAmountInput');
            const modalBillTypeInput = document.getElementById('modalBillTypeInput');
            const modalHiddenEntriesContainer = document.getElementById('modalHiddenEntriesContainer');

            // Top Buttons
            const btnSelectReceived = document.getElementById('btnSelectReceived');
            const btnShowAll = document.getElementById('btnShowAll');
            const btnShowSelected = document.getElementById('btnShowSelected');
            const btnShowRed = document.getElementById('btnShowRed');
            const btnDeselectAll = document.getElementById('btnDeselectAll');
            const mainSearchInput = document.getElementById('mainSearchInput');
            const mainSearchColumn = document.getElementById('mainSearchColumn');
            const btnSearchSelect = document.getElementById('btnSearchSelect');

            // 3-Level Filters
            const filterField1 = document.getElementById('filterField1');
            const filterVal1 = document.getElementById('filterVal1');
            const filterField2 = document.getElementById('filterField2');
            const filterVal2 = document.getElementById('filterVal2');
            const filterField3 = document.getElementById('filterField3');
            const filterVal3 = document.getElementById('filterVal3');
            const btnApplyFilterSelect = document.getElementById('btnApplyFilterSelect');
            const btnApplyFilterDeselect = document.getElementById('btnApplyFilterDeselect');

            const btnQuickSelectAll = document.getElementById('btnQuickSelectAll');
            const btnQuickClear = document.getElementById('btnQuickClear');

            // State
            let selectedIds = new Set();
            let viewMode = 'all';
            let activeFilterCondition = null;

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            const populateFilterValues = (fieldSelect, valSelect) => {
                const field = fieldSelect.value;
                if (!field) {
                    valSelect.innerHTML = '<option value="">Choose Value...</option>';
                    valSelect.disabled = true;
                    return;
                }

                const uniqueVals = [...new Set(rawEntries.map(e => String(e[field] ?? '')))].filter(Boolean).sort();
                valSelect.innerHTML = '<option value="">Choose Value...</option>' + 
                    uniqueVals.map(v => `<option value="${v}">${v}</option>`).join('');
                valSelect.disabled = false;
            };

            filterField1.addEventListener('change', () => populateFilterValues(filterField1, filterVal1));
            filterField2.addEventListener('change', () => populateFilterValues(filterField2, filterVal2));
            filterField3.addEventListener('change', () => populateFilterValues(filterField3, filterVal3));

            const renderTable = () => {
                const query = mainSearchInput.value.toLowerCase().trim();
                const searchCol = mainSearchColumn.value;

                let visibleEntries = rawEntries.filter(entry => {
                    if (viewMode === 'selected_only' && !selectedIds.has(entry.id)) return false;
                    if (viewMode === 'red_only' && !entry.is_red) return false;

                    if (activeFilterCondition && !activeFilterCondition(entry)) return false;

                    if (query) {
                        if (searchCol === 'all') {
                            const combined = Object.values(entry).join(' ').toLowerCase();
                            if (!combined.includes(query)) return false;
                        } else {
                            const val = String(entry[searchCol] ?? '').toLowerCase();
                            if (!val.includes(query)) return false;
                        }
                    }

                    return true;
                });

                totalVisibleRows.textContent = visibleEntries.length;

                if (visibleEntries.length === 0) {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="17" class="py-10 text-center text-slate-500 font-medium">
                                No records found matching the selection or filter.
                            </td>
                        </tr>
                    `;
                    updateCounters();
                    return;
                }

                tableBody.innerHTML = visibleEntries.map((entry, idx) => {
                    const isSelected = selectedIds.has(entry.id);
                    const isRed = entry.is_red;
                    const rowBgClass = isRed 
                        ? (isSelected ? 'bg-rose-100 border-l-4 border-rose-600' : 'bg-rose-50/70 text-rose-950')
                        : (isSelected ? 'bg-emerald-50/80 border-l-4 border-emerald-600 font-medium' : '');

                    const isReceived = entry.received === 'Received';
                    const receivedBadge = isReceived 
                        ? '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Received</span>'
                        : '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-600">Not Received</span>';

                    return `
                        <tr class="transition-colors hover:bg-emerald-50/40 cursor-pointer ${rowBgClass}" data-entry-id="${entry.id}">
                            <td class="text-center !px-1" onclick="event.stopPropagation()">
                                <input type="checkbox" class="row-checkbox rounded border-slate-300 text-emerald-600 focus:ring-0 cursor-pointer" 
                                    data-id="${entry.id}" ${isSelected ? 'checked' : ''}>
                            </td>
                            <td class="whitespace-nowrap font-medium text-slate-900 max-w-[85px] truncate" title="${entry.range ?? ''}">${entry.range ?? '-'}</td>
                            <td class="whitespace-nowrap text-center text-slate-700">${entry.entry_month ?? '-'}</td>
                            <td class="whitespace-nowrap text-center font-bold text-slate-800">${entry.docket_no ?? '-'}</td>
                            <td class="whitespace-nowrap text-center font-bold text-slate-800">${entry.sr_no ?? '-'}</td>
                            <td class="whitespace-nowrap text-slate-700 max-w-[90px] truncate" title="${entry.entry_type ?? ''}">${entry.entry_type ?? '-'}</td>
                            <td class="whitespace-nowrap text-slate-600 text-center">${entry.no_edi ?? '0'}</td>
                            <td class="whitespace-nowrap text-center font-bold text-emerald-800">${entry.budget_code ?? '-'}</td>
                            <td class="whitespace-nowrap text-center text-slate-700">${entry.head ?? '-'}</td>
                            <td class="whitespace-nowrap text-slate-800 max-w-[95px] truncate" title="${entry.scheme ?? ''}">${entry.scheme ?? '-'}</td>
                            <td class="whitespace-nowrap text-center text-slate-700">${entry.class ?? '-'}</td>
                            <td class="whitespace-nowrap text-slate-800 max-w-[95px] truncate" title="${entry.model ?? ''}">${entry.model ?? '-'}</td>
                            <td class="whitespace-nowrap font-medium text-slate-900 max-w-[110px] truncate" title="${entry.party_name ?? ''}">${entry.party_name ?? '-'}</td>
                            <td class="whitespace-nowrap font-bold text-slate-950 text-right">₹ ${Number(entry.total_amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                            <td class="whitespace-nowrap text-center">${receivedBadge}</td>
                            <td class="col-desc text-xs text-slate-800 leading-relaxed break-words">${entry.all_descriptions ?? '-'}</td>
                            <td class="whitespace-nowrap text-center text-slate-600">${entry.edp_code ?? '-'}</td>
                        </tr>
                    `;
                }).join('');

                updateCounters();
            };

            const updateCounters = () => {
                let sum = 0;
                rawEntries.forEach(e => {
                    if (selectedIds.has(e.id)) {
                        sum += Number(e.total_amount || 0);
                    }
                });

                countSelectedRows.textContent = selectedIds.size;
                sumSelectedAmount.textContent = formatMoney(sum);

                const checkboxes = [...tableBody.querySelectorAll('.row-checkbox')];
                if (checkboxes.length > 0 && checkboxes.every(cb => cb.checked)) {
                    masterCheckbox.checked = true;
                    masterCheckbox.indeterminate = false;
                } else if (checkboxes.some(cb => cb.checked)) {
                    masterCheckbox.checked = false;
                    masterCheckbox.indeterminate = true;
                } else {
                    masterCheckbox.checked = false;
                    masterCheckbox.indeterminate = false;
                }
            };

            tableBody.addEventListener('click', (e) => {
                const tr = e.target.closest('tr[data-entry-id]');
                if (!tr) return;
                const id = tr.dataset.entryId;
                if (!id) return;

                if (selectedIds.has(id)) {
                    selectedIds.delete(id);
                } else {
                    selectedIds.add(id);
                }
                renderTable();
            });

            tableBody.addEventListener('change', (e) => {
                if (e.target.matches('.row-checkbox')) {
                    const id = e.target.dataset.id;
                    if (e.target.checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                    renderTable();
                }
            });

            masterCheckbox.addEventListener('change', () => {
                const checkboxes = [...tableBody.querySelectorAll('.row-checkbox')];
                checkboxes.forEach(cb => {
                    const id = cb.dataset.id;
                    if (masterCheckbox.checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                renderTable();
            });

            btnSelectReceived.addEventListener('click', () => {
                rawEntries.forEach(e => {
                    if (e.received === 'Received') {
                        selectedIds.add(e.id);
                    }
                });
                renderTable();
            });

            btnShowAll.addEventListener('click', () => {
                viewMode = 'all';
                activeFilterCondition = null;
                mainSearchInput.value = '';
                renderTable();
            });

            btnShowSelected.addEventListener('click', () => {
                viewMode = 'selected_only';
                renderTable();
            });

            btnShowRed.addEventListener('click', () => {
                viewMode = 'red_only';
                renderTable();
            });

            btnDeselectAll.addEventListener('click', () => {
                selectedIds.clear();
                renderTable();
            });

            btnQuickClear?.addEventListener('click', () => {
                selectedIds.clear();
                renderTable();
            });

            btnQuickSelectAll?.addEventListener('click', () => {
                tableBody.querySelectorAll('.row-checkbox').forEach(cb => {
                    selectedIds.add(cb.dataset.id);
                });
                renderTable();
            });

            mainSearchInput.addEventListener('input', renderTable);
            mainSearchColumn.addEventListener('change', renderTable);

            btnSearchSelect.addEventListener('click', () => {
                const query = mainSearchInput.value.toLowerCase().trim();
                const searchCol = mainSearchColumn.value;
                if (!query) return;

                rawEntries.forEach(entry => {
                    let matches = false;
                    if (searchCol === 'all') {
                        matches = Object.values(entry).join(' ').toLowerCase().includes(query);
                    } else {
                        matches = String(entry[searchCol] ?? '').toLowerCase().includes(query);
                    }
                    if (matches) selectedIds.add(entry.id);
                });
                renderTable();
            });

            const get3LevelFilterPredicate = () => {
                const f1 = filterField1.value, v1 = filterVal1.value;
                const f2 = filterField2.value, v2 = filterVal2.value;
                const f3 = filterField3.value, v3 = filterVal3.value;

                return (entry) => {
                    if (f1 && v1 && String(entry[f1] ?? '') !== v1) return false;
                    if (f2 && v2 && String(entry[f2] ?? '') !== v2) return false;
                    if (f3 && v3 && String(entry[f3] ?? '') !== v3) return false;
                    return true;
                };
            };

            btnApplyFilterSelect.addEventListener('click', () => {
                const predicate = get3LevelFilterPredicate();
                rawEntries.forEach(entry => {
                    if (predicate(entry)) {
                        selectedIds.add(entry.id);
                    }
                });
                renderTable();
            });

            btnApplyFilterDeselect.addEventListener('click', () => {
                const predicate = get3LevelFilterPredicate();
                rawEntries.forEach(entry => {
                    if (predicate(entry)) {
                        selectedIds.delete(entry.id);
                    }
                });
                renderTable();
            });

            proceedBillTypeBtn.addEventListener('click', () => {
                const selected = billTypeSelect.value;
                if (!selected) {
                    alert('Please select a Bill Type first.');
                    return;
                }
                activeBillTypeText.textContent = selected;
                modalBillTypeInput.value = selected;
                window.location.href = `{{ route('division.process-bill.index') }}?bill_type=${encodeURIComponent(selected)}`;
            });

            btnVerifyAndProcess.addEventListener('click', () => {
                if (selectedIds.size === 0) {
                    alert('Please select at least one bill before verifying and processing.');
                    return;
                }

                let sum = 0;
                modalHiddenEntriesContainer.innerHTML = '';

                let i = 0;
                rawEntries.forEach(entry => {
                    if (selectedIds.has(entry.id)) {
                        sum += Number(entry.total_amount || 0);

                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = `selected_entries[${i}][id]`;
                        hidden.value = entry.id;
                        modalHiddenEntriesContainer.appendChild(hidden);

                        const hiddenSr = document.createElement('input');
                        hiddenSr.type = 'hidden';
                        hiddenSr.name = `selected_entries[${i}][sr_no]`;
                        hiddenSr.value = entry.sr_no;
                        modalHiddenEntriesContainer.appendChild(hiddenSr);

                        const hiddenAmt = document.createElement('input');
                        hiddenAmt.type = 'hidden';
                        hiddenAmt.name = `selected_entries[${i}][amount]`;
                        hiddenAmt.value = entry.total_amount;
                        modalHiddenEntriesContainer.appendChild(hiddenAmt);

                        i++;
                    }
                });

                modalBillCount.textContent = `${selectedIds.size} Bills Selected`;
                modalTotalAmount.textContent = formatMoney(sum);
                modalTotalAmountInput.value = sum.toFixed(2);

                processModal.classList.remove('hidden');
                processModal.classList.add('flex');
            });

            const closeProcessModal = () => {
                processModal.classList.add('hidden');
                processModal.classList.remove('flex');
            };

            closeProcessModalBtn?.addEventListener('click', closeProcessModal);
            cancelProcessBtn?.addEventListener('click', closeProcessModal);

            renderTable();
        });
    </script>
</x-layouts.admin>
