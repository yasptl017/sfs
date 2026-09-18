<x-layouts.admin title="Abstract Print | Forest Inventory" heading="Abstract Print" subheading="Range Finance System - Monthly Docket &amp; Voucher Abstract Generation">
    <style>
        .maroon-border-field {
            border: 2px solid #5c0606 !important;
            border-radius: 0.375rem !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            padding: 0.55rem 0.75rem !important;
            font-size: 0.925rem !important;
            font-weight: 600 !important;
            transition: all 0.15s ease-in-out !important;
            width: 100% !important;
        }
        .maroon-border-field:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.25) !important;
            outline: none !important;
        }
        .maroon-select {
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23334155' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.75rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.25em 1.25em !important;
            padding-right: 2.25rem !important;
        }
        .cyan-callout-box {
            background-color: #00c5ff !important;
            color: #0c4a6e !important;
            font-weight: 700 !important;
            font-size: 0.78rem !important;
            line-height: 1.35 !important;
            padding: 0.65rem 0.75rem !important;
            border-radius: 0.125rem !important;
        }
        .cyan-print-btn {
            background-color: #00c5ff !important;
            color: #0f172a !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            padding: 0.45rem 1.75rem !important;
            border-radius: 0.375rem !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .cyan-print-btn:hover {
            background-color: #00b0e6 !important;
        }
    </style>

    <div class="space-y-6" id="abstractPrintModule"
        data-dockets-url="{{ route('finance.abstract-print.dockets') }}"
        data-preview-url="{{ route('finance.abstract-print.preview') }}"
        data-print-url="{{ route('finance.abstract-print.print') }}">

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

        <div id="abstractAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Main "Abstract Print:" Form Card (Exact Match to Reference Image) -->
        <section class="max-w-xs sm:max-w-sm mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-lg overflow-hidden transition-all">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 bg-slate-50/80 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-800 tracking-tight">
                        Abstract Print:
                    </h2>
                    <button type="button" id="closeAbstractFormBtn" title="Close" class="text-slate-500 hover:text-slate-800 text-xl font-bold transition-colors focus:outline-none leading-none">
                        ✕
                    </button>
                </div>

                <!-- Form Body -->
                <form id="abstractPrintForm" method="GET" action="{{ route('finance.abstract-print.print') }}" target="_blank" class="p-5 sm:p-6 space-y-4">
                    <input type="hidden" name="mode" id="printModeInput" value="detailed">

                    <!-- 1. Month -->
                    <div>
                        <label for="month" class="block text-sm font-semibold text-slate-800 mb-1.5">
                            Month
                        </label>
                        <select id="month" name="month" required class="maroon-border-field maroon-select cursor-pointer">
                            @foreach($months as $m)
                                <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Docket No. -->
                    <div>
                        <label for="docket_no" class="block text-sm font-semibold text-slate-800 mb-1.5">
                            Docket No.
                        </label>
                        <input type="text" id="docket_no" name="docket_no"
                            value="{{ $docketNo }}"
                            placeholder=""
                            class="maroon-border-field">
                    </div>

                    <!-- 3. Primary Button: Print (with Rate and Quantity) -->
                    <div class="pt-1">
                        <button type="button" id="btnPrintDetailed"
                            class="w-full inline-flex items-center justify-center rounded-md bg-[#1877f2] hover:bg-blue-600 text-white font-bold py-2.5 px-4 text-xs sm:text-sm shadow-sm transition-all active:scale-95 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            Print (with Rate and Quantity)
                        </button>
                    </div>

                    <!-- 4. Cyan Callout Highlight Note -->
                    <div class="cyan-callout-box">
                        To print, the detailed Abstract with plot area, Rate, Quantity and work dates, then click above button
                    </div>

                    <!-- 5. Secondary Button: Print (General / Summary Abstract) -->
                    <div>
                        <button type="button" id="btnPrintSummary"
                            class="cyan-print-btn active:scale-95 focus:outline-none focus:ring-4 focus:ring-cyan-200">
                            Print
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Section 2: Online Live Abstract Preview Section -->
        <section id="abstractPreviewSection" class="hidden rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-4 bg-emerald-50/40 gap-3">
                <div class="flex items-center gap-3">
                    <span id="previewMonthBadge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Aug Abstract
                    </span>
                    <h3 id="previewAbstractTitle" class="text-base font-semibold text-slate-950">Abstract Preview</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrintCurrentPreview" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Print Sheet</span>
                    </button>
                    <button type="button" id="btnClosePreview" class="text-slate-400 hover:text-slate-700 text-xl font-bold px-2">
                        &times;
                    </button>
                </div>
            </div>

            <!-- Preview Sheet -->
            <div id="abstractSheetContainer" class="p-6 sm:p-8 bg-white overflow-x-auto space-y-6">
                <!-- Rendered dynamically by JavaScript -->
            </div>
        </section>

        <!-- Section 3: Recent Abstracts Summary Table -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3 bg-white">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Recent Monthly Docket Abstracts</h2>
                    <p class="mt-1 text-sm text-slate-500">Summary of recent monthly voucher batches prepared in this range.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Sr.</th>
                            <th>Month</th>
                            <th>Docket No.</th>
                            <th class="text-center">Vouchers Count</th>
                            <th class="text-right">Gross Claimed (₹)</th>
                            <th class="text-right">Net Disbursed (₹)</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentAbstracts as $index => $item)
                            <tr class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-bold text-slate-900">{{ $index + 1 }}</td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        {{ $item['month'] }}
                                    </span>
                                </td>
                                <td class="font-bold text-slate-950 font-mono">{{ $item['docket_no'] }}</td>
                                <td class="text-center font-semibold text-slate-800">{{ $item['count'] }} Vouchers</td>
                                <td class="text-right font-semibold text-slate-900">₹ {{ number_format($item['gross'], 2) }}</td>
                                <td class="text-right font-extrabold text-emerald-900">₹ {{ number_format($item['net'], 2) }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" class="secondary-button min-h-7 px-2.5 py-0.5 text-xs"
                                            onclick="triggerAbstractPrint('{{ $item['month'] }}', '{{ $item['docket_no'] }}', 'detailed')">
                                            Detailed
                                        </button>
                                        <button type="button" class="secondary-button min-h-7 px-2.5 py-0.5 text-xs bg-cyan-50 border-cyan-200 text-cyan-900"
                                            onclick="triggerAbstractPrint('{{ $item['month'] }}', '{{ $item['docket_no'] }}', 'summary')">
                                            Summary
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Dynamic JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('abstractPrintForm');
            const monthSelect = document.getElementById('month');
            const docketInput = document.getElementById('docket_no');
            const printModeInput = document.getElementById('printModeInput');
            const btnPrintDetailed = document.getElementById('btnPrintDetailed');
            const btnPrintSummary = document.getElementById('btnPrintSummary');
            const closeAbstractFormBtn = document.getElementById('closeAbstractFormBtn');

            const previewSection = document.getElementById('abstractPreviewSection');
            const previewSheetContainer = document.getElementById('abstractSheetContainer');
            const previewMonthBadge = document.getElementById('previewMonthBadge');
            const previewAbstractTitle = document.getElementById('previewAbstractTitle');
            const btnPrintCurrentPreview = document.getElementById('btnPrintCurrentPreview');
            const btnClosePreview = document.getElementById('btnClosePreview');

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            const triggerPrint = (mode) => {
                printModeInput.value = mode;
                form.submit();
            };

            btnPrintDetailed.addEventListener('click', () => triggerPrint('detailed'));
            btnPrintSummary.addEventListener('click', () => triggerPrint('summary'));

            closeAbstractFormBtn?.addEventListener('click', function () {
                form.reset();
                monthSelect.value = 'Aug';
                docketInput.value = '';
            });

            // Live preview generation
            const loadPreview = async (mode) => {
                const month = monthSelect.value;
                const docketNo = docketInput.value;

                try {
                    const response = await fetch(document.getElementById('abstractPrintModule').dataset.previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            month: month,
                            docket_no: docketNo,
                            mode: mode
                        })
                    });

                    if (response.ok) {
                        const res = await response.json();
                        renderAbstractSheet(res.data, mode);
                        previewMonthBadge.textContent = `${month} (${mode === 'detailed' ? 'Detailed' : 'Summary'})`;
                        previewAbstractTitle.textContent = `Abstract - ${res.data.docket_no}`;
                        previewSection.classList.remove('hidden');
                        previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } catch (e) {
                    console.error('Preview error:', e);
                }
            };

            const renderAbstractSheet = (data, mode) => {
                const entries = data.entries || [];
                const totals = data.totals || {};
                const isDetailed = mode === 'detailed';

                let html = `
                    <div class="border-b-2 border-slate-900 pb-4 text-center space-y-1">
                        <h1 class="text-lg font-black text-slate-950 uppercase tracking-wide">GUJARAT FOREST DEPARTMENT</h1>
                        <h2 class="text-sm font-bold text-slate-800">${data.division_name} - ${data.range_name}</h2>
                        <h3 class="text-xs font-extrabold text-emerald-900 uppercase tracking-widest">
                            MONTHLY VOUCHER ABSTRACT (ગોશવારો / એબ્સ્ટ્રેક્ટ) - ${data.month.toUpperCase()}
                        </h3>
                        <div class="flex flex-wrap justify-between items-center text-xs text-slate-700 pt-2 font-semibold">
                            <span><strong>Month:</strong> ${data.month}</span>
                            <span><strong>Docket No:</strong> ${data.docket_no}</span>
                            <span><strong>Mode:</strong> ${isDetailed ? 'With Rate & Quantity' : 'Summary'}</span>
                            <span><strong>Generated At:</strong> ${data.generated_at}</span>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center text-xs">
                        <div class="p-3 bg-slate-50 rounded border border-slate-200">
                            <span class="text-slate-500 block text-[11px] font-bold uppercase">Total Vouchers</span>
                            <span class="text-base font-extrabold text-slate-950">${totals.count}</span>
                        </div>
                        <div class="p-3 bg-blue-50 rounded border border-blue-200">
                            <span class="text-blue-700 block text-[11px] font-bold uppercase">Gross Total</span>
                            <span class="text-base font-extrabold text-blue-950">${formatMoney(totals.gross_amount)}</span>
                        </div>
                        <div class="p-3 bg-rose-50 rounded border border-rose-200">
                            <span class="text-rose-700 block text-[11px] font-bold uppercase">Total Deductions</span>
                            <span class="text-base font-extrabold text-rose-950">${formatMoney(totals.total_deductions)}</span>
                        </div>
                        <div class="p-3 bg-emerald-50 rounded border border-emerald-200">
                            <span class="text-emerald-800 block text-[11px] font-bold uppercase">Net Disbursed</span>
                            <span class="text-base font-extrabold text-emerald-950">${formatMoney(totals.net_amount)}</span>
                        </div>
                    </div>

                    <!-- Abstract Table -->
                    <table class="w-full text-xs border border-slate-300 border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                <th class="p-2 border border-slate-300 text-center w-8">Sr.</th>
                                <th class="p-2 border border-slate-300 text-left">Voucher No. &amp; Party Name</th>
                                <th class="p-2 border border-slate-300 text-left">Budget Code &amp; Scheme</th>
                                <th class="p-2 border border-slate-300 text-left">Work Description ${isDetailed ? '&amp; Plot Area' : ''}</th>
                                ${isDetailed ? `
                                    <th class="p-2 border border-slate-300 text-center w-16">Work Dates</th>
                                    <th class="p-2 border border-slate-300 text-right w-16">Qty &amp; Unit</th>
                                    <th class="p-2 border border-slate-300 text-right w-16">Rate (₹)</th>
                                ` : ''}
                                <th class="p-2 border border-slate-300 text-right w-24">Gross (₹)</th>
                                <th class="p-2 border border-slate-300 text-right w-24">Deductions (₹)</th>
                                <th class="p-2 border border-slate-300 text-right w-24 text-emerald-950 font-bold">Net (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${entries.map((e, idx) => {
                                const it = e.items && e.items[0] ? e.items[0] : {};
                                return `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-bold">${idx + 1}</td>
                                        <td class="p-2 border border-slate-300">
                                            <strong class="text-slate-950 block">${e.party_name}</strong>
                                            <span class="text-[11px] text-slate-500 block font-mono">${e.voucher_no}</span>
                                        </td>
                                        <td class="p-2 border border-slate-300">
                                            <strong class="text-emerald-900 block font-mono text-[11px]">${e.budget_code}</strong>
                                            <span class="text-[11px] text-slate-600 block">${e.scheme}</span>
                                        </td>
                                        <td class="p-2 border border-slate-300">
                                            <p class="text-slate-900 leading-relaxed">${it.description || e.party_name}</p>
                                            ${isDetailed ? `<span class="text-[11px] text-blue-700 font-semibold block">📍 ${it.plot_area || e.place}</span>` : ''}
                                        </td>
                                        ${isDetailed ? `
                                            <td class="p-2 border border-slate-300 text-center text-[11px] text-slate-600">${it.work_dates || '-'}</td>
                                            <td class="p-2 border border-slate-300 text-right font-bold">${it.quantity || 1} ${it.unit || ''}</td>
                                            <td class="p-2 border border-slate-300 text-right font-medium">${formatMoney(it.rate || 0)}</td>
                                        ` : ''}
                                        <td class="p-2 border border-slate-300 text-right font-bold text-slate-900">${formatMoney(e.gross_amount)}</td>
                                        <td class="p-2 border border-slate-300 text-right text-rose-700 font-semibold">${formatMoney(e.total_deductions)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-extrabold text-emerald-950">${formatMoney(e.net_amount)}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                <td colspan="${isDetailed ? 7 : 4}" class="p-2 border border-slate-300 text-right uppercase">Total Amount:</td>
                                <td class="p-2 border border-slate-300 text-right font-bold text-slate-950">${formatMoney(totals.gross_amount)}</td>
                                <td class="p-2 border border-slate-300 text-right font-bold text-rose-800">${formatMoney(totals.total_deductions)}</td>
                                <td class="p-2 border border-slate-300 text-right font-extrabold text-emerald-950">${formatMoney(totals.net_amount)}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Department Signatures -->
                    <div class="grid grid-cols-3 gap-4 pt-10 text-center text-xs font-bold text-slate-800">
                        <div><div class="border-t border-slate-400 pt-1.5">Prepared By (Cashier / Clerk)</div></div>
                        <div><div class="border-t border-slate-400 pt-1.5">Checked By (Forester / Accountant)</div></div>
                        <div><div class="border-t border-slate-400 pt-1.5">Approved By (Range Forest Officer)</div></div>
                    </div>
                `;

                previewSheetContainer.innerHTML = html;
            };

            btnPrintCurrentPreview?.addEventListener('click', function () {
                const month = monthSelect.value;
                const docketNo = docketInput.value;
                const mode = printModeInput.value;

                const url = new URL(document.getElementById('abstractPrintModule').dataset.printUrl, window.location.origin);
                url.searchParams.set('month', month);
                url.searchParams.set('docket_no', docketNo);
                url.searchParams.set('mode', mode);

                window.open(url.toString(), '_blank');
            });

            btnClosePreview?.addEventListener('click', function () {
                previewSection.classList.add('hidden');
            });

            window.triggerAbstractPrint = function (month, docketNo, mode) {
                monthSelect.value = month;
                docketInput.value = docketNo;
                printModeInput.value = mode;
                form.submit();
            };
        });
    </script>
</x-layouts.admin>
