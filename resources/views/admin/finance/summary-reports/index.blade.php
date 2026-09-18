<x-layouts.admin title="Summary Reports | Forest Inventory" heading="Summary Reports" subheading="Division Finance System - Scheme &amp; Grant vs Expenditure Analysis">
    <style>
        .summary-card {
            background-color: #5db6eb !important;
            border-radius: 1.25rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        }
        .summary-inner-card {
            background-color: #6bcc6f !important;
            border-radius: 1rem !important;
        }
        .summary-label {
            color: #003884 !important;
            font-weight: 800 !important;
            font-size: 0.95rem !important;
            text-align: center !important;
            display: block !important;
            margin-bottom: 0.35rem !important;
            text-decoration: underline !important;
            text-decoration-color: #003884 !important;
        }
        .summary-input {
            border: 2px solid #ffffff !important;
            border-radius: 0.5rem !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.925rem !important;
            font-weight: 600 !important;
            width: 100% !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
        }
        .btn-summary-blue {
            background-color: #1877f2 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            padding: 0.75rem 1rem !important;
            border-radius: 0.5rem !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            width: 100% !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .btn-summary-blue:hover {
            background-color: #0d65d9 !important;
            transform: translateY(-1px) !important;
        }
        .btn-summary-dark {
            background-color: #212529 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            padding: 0.75rem 1rem !important;
            border-radius: 0.5rem !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            width: 100% !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .btn-summary-dark:hover {
            background-color: #000000 !important;
            transform: translateY(-1px) !important;
        }
    </style>

    <div class="space-y-6" id="summaryReportModule"
        data-preview-url="{{ route('division.summary-reports.preview') }}"
        data-print-url="{{ route('division.summary-reports.print') }}">

        <!-- Control Form Card -->
        <section class="max-w-3xl lg:max-w-4xl mx-auto">
            <div class="summary-card p-5 sm:p-6 text-slate-900 overflow-hidden">
                <div class="flex items-center justify-between pb-3 border-b border-sky-300/60 mb-5">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-snug">
                        Generate and Download Summary Reports:
                    </h2>
                    <span class="px-3 py-1 bg-white/80 border border-white rounded-full text-xs font-bold text-slate-800">
                        {{ $divisionUser->name }}
                    </span>
                </div>

                <form id="summaryReportForm" method="GET" action="{{ route('division.summary-reports.print') }}" target="_blank" class="space-y-4">
                    <input type="hidden" name="report_type" id="reportTypeInput" value="{{ $selectedReportType }}">

                    <!-- Month and Payment Mode Filters -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-lg mx-auto mb-3">
                        <div>
                            <label for="month" class="summary-label">Month:</label>
                            <select id="month" name="month" class="summary-input cursor-pointer text-center">
                                @foreach($months as $m)
                                    <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ $m }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="payment_mode" class="summary-label">Payment Mode:</label>
                            <select id="payment_mode" name="payment_mode" class="summary-input cursor-pointer text-center">
                                @foreach($paymentModes as $pm)
                                    <option value="{{ $pm }}" @selected($selectedPaymentMode === $pm)>{{ $pm }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Inner Green Card with 3 Big Action Buttons -->
                    <div class="summary-inner-card p-5 sm:p-6 shadow-sm border border-green-500/30">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                            <button type="button" class="btn-summary-blue" onclick="triggerSummaryDownload('summary_abstract')">
                                <span class="text-center font-bold">1. Abstract of Summary Report</span>
                            </button>

                            <button type="button" class="btn-summary-blue" onclick="triggerSummaryDownload('summary_merged_salary')">
                                <span class="text-center font-bold">2. Summary (Merged with Salary)</span>
                            </button>

                            <button type="button" class="btn-summary-dark" onclick="triggerSummaryDownload('summary_only_lc')">
                                <span class="text-center font-bold">3. Summary (Only LC - Work)</span>
                            </button>
                        </div>
                        <p class="text-center text-xs font-bold text-emerald-950 mt-4">
                            ✨ ઉપરના કોઈપણ રિપોર્ટ પર ક્લિક કરીને ઓનલાઇન લાઈવ પ્રિવ્યુ જુઓ અથવા પ્રિન્ટ / પીડીએફ સેવ કરો.
                        </p>
                    </div>
                </form>
            </div>
        </section>

        <!-- Live Preview Section -->
        <section id="summaryPreviewSection" class="rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-4 bg-emerald-50/40 gap-3">
                <div class="flex items-center gap-3">
                    <span id="previewReportBadge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        Summary Abstract
                    </span>
                    <h3 id="previewTitle" class="text-base font-semibold text-slate-950">Summary Report Preview</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrintCurrentPreview" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5 font-bold shadow-sm">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Print Full Report</span>
                    </button>
                </div>
            </div>

            <div id="summarySheetContainer" class="p-6 sm:p-8 bg-white overflow-x-auto space-y-6">
                <!-- Rendered dynamically -->
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('summaryReportForm');
            const monthSelect = document.getElementById('month');
            const paymentModeSelect = document.getElementById('payment_mode');
            const reportTypeInput = document.getElementById('reportTypeInput');

            const previewSection = document.getElementById('summaryPreviewSection');
            const previewSheetContainer = document.getElementById('summarySheetContainer');
            const previewReportBadge = document.getElementById('previewReportBadge');
            const previewTitle = document.getElementById('previewTitle');
            const btnPrintCurrentPreview = document.getElementById('btnPrintCurrentPreview');

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            window.triggerSummaryDownload = function (type) {
                reportTypeInput.value = type;
                loadPreviewData(type);
            };

            btnPrintCurrentPreview.addEventListener('click', function () {
                form.submit();
            });

            monthSelect.addEventListener('change', () => loadPreviewData(reportTypeInput.value));
            paymentModeSelect.addEventListener('change', () => loadPreviewData(reportTypeInput.value));

            async function loadPreviewData(type = 'summary_abstract') {
                const previewUrl = document.getElementById('summaryReportModule').dataset.previewUrl;

                try {
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            month: monthSelect.value,
                            payment_mode: paymentModeSelect.value,
                            report_type: type
                        })
                    });

                    if (!response.ok) throw new Error('Preview failed');
                    const json = await response.json();
                    if (json.success) {
                        renderPreview(json.data);
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            function renderPreview(data) {
                previewSection.classList.remove('hidden');
                previewReportBadge.textContent = data.report_type;
                previewTitle.textContent = `${data.report_title} (${data.month} - ${data.payment_mode})`;

                if (data.report_type === 'summary_abstract') {
                    let sectionsHtml = '';
                    data.sections.forEach(sec => {
                        let rows = '';
                        sec.schemes.forEach(sch => {
                            rows += `
                                <tr class="border-b border-slate-200 hover:bg-slate-50/50 text-xs">
                                    <td class="p-2 border border-slate-300 font-medium text-slate-900">${sch.name}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(sch.sanctioned)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(sch.released)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(sch.last_month)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(sch.current_month)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">${formatMoney(sch.total_exp)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(sch.rem_req)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(sch.total_req)}</td>
                                    <td class="p-2 border border-slate-300 text-center font-mono font-semibold">${sch.pct_sanctioned}%</td>
                                    <td class="p-2 border border-slate-300 text-center font-mono font-semibold">${sch.pct_released}%</td>
                                </tr>
                            `;
                        });

                        rows += `
                            <tr class="bg-slate-100 font-bold text-slate-950 border-b-2 border-slate-400 text-xs">
                                <td class="p-2 border border-slate-300 text-right uppercase">${sec.demand_key} Total:</td>
                                <td class="p-2 border border-slate-300 text-right font-mono font-black whitespace-nowrap">${formatMoney(sec.subtotal.sanctioned)}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono font-black whitespace-nowrap">${formatMoney(sec.subtotal.released)}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono font-black whitespace-nowrap">${formatMoney(sec.subtotal.last_month)}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono font-black whitespace-nowrap">${formatMoney(sec.subtotal.current_month)}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono font-black text-emerald-950 bg-emerald-100/50 whitespace-nowrap">${formatMoney(sec.subtotal.total_exp)}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono font-black whitespace-nowrap">${formatMoney(sec.subtotal.rem_req)}</td>
                                <td class="p-2 border border-slate-300 text-right font-mono font-black whitespace-nowrap">${formatMoney(sec.subtotal.total_req)}</td>
                                <td class="p-2 border border-slate-300 text-center font-mono font-black">${sec.subtotal.pct_sanctioned}%</td>
                                <td class="p-2 border border-slate-300 text-center font-mono font-black">${sec.subtotal.pct_released}%</td>
                            </tr>
                        `;

                        sectionsHtml += rows;
                    });

                    previewSheetContainer.innerHTML = `
                        <div class="border-b-2 border-slate-900 pb-3 mb-4 text-center">
                            <h2 class="text-base sm:text-lg font-black text-slate-950 uppercase">${data.division_name}</h2>
                            <h3 class="text-sm font-extrabold text-emerald-900">${data.report_title}</h3>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs border border-slate-300 border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                                        <th class="p-2 border border-slate-300 text-left">Scheme</th>
                                        <th class="p-2 border border-slate-300 text-right">Sanctioned Grant</th>
                                        <th class="p-2 border border-slate-300 text-right">Released Grant</th>
                                        <th class="p-2 border border-slate-300 text-right">Expenditure Upto Last Month</th>
                                        <th class="p-2 border border-slate-300 text-right">Expenditure of Current Month</th>
                                        <th class="p-2 border border-slate-300 text-right bg-emerald-50 text-emerald-950">Total Expenditure</th>
                                        <th class="p-2 border border-slate-300 text-right">Remaining Requirement</th>
                                        <th class="p-2 border border-slate-300 text-right">Total Requirement</th>
                                        <th class="p-2 border border-slate-300 text-center">% of Sanc.</th>
                                        <th class="p-2 border border-slate-300 text-center">% of Rel.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${sectionsHtml}
                                    <tr class="bg-emerald-100/70 font-black text-slate-950 border-t-2 border-slate-900 text-xs">
                                        <td class="p-2.5 border border-slate-300 text-right uppercase">Grand Total:</td>
                                        <td class="p-2.5 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(data.grand_totals.sanctioned)}</td>
                                        <td class="p-2.5 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(data.grand_totals.released)}</td>
                                        <td class="p-2.5 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(data.grand_totals.last_month)}</td>
                                        <td class="p-2.5 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(data.grand_totals.current_month)}</td>
                                        <td class="p-2.5 border border-slate-300 text-right font-mono text-emerald-950 whitespace-nowrap">${formatMoney(data.grand_totals.total_exp)}</td>
                                        <td class="p-2.5 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(data.grand_totals.rem_req)}</td>
                                        <td class="p-2.5 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(data.grand_totals.total_req)}</td>
                                        <td class="p-2.5 border border-slate-300 text-center font-mono">${data.grand_totals.pct_sanctioned}%</td>
                                        <td class="p-2.5 border border-slate-300 text-center font-mono">${data.grand_totals.pct_released}%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    let groupsHtml = '';
                    data.groups.forEach(g => {
                        let rows = '';
                        g.items.forEach(it => {
                            rows += `
                                <tr class="border-b border-slate-200 text-xs">
                                    <td class="p-2 border border-slate-300 text-center font-bold">${it.sr_no}</td>
                                    <td class="p-2 border border-slate-300 font-semibold">${it.item}</td>
                                    <td class="p-2 border border-slate-300">${it.obj_class}</td>
                                    <td class="p-2 border border-slate-300 font-medium">${it.model}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(it.allotment)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(it.p_exp)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(it.c_exp)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">${formatMoney(it.total_exp)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(it.tot_req)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(it.req_change)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono whitespace-nowrap">${formatMoney(it.rem_req)}</td>
                                </tr>
                            `;
                        });

                        groupsHtml += `
                            <div class="mb-6">
                                <div class="bg-slate-100 p-2 border border-slate-300 font-bold text-emerald-950 text-xs mb-1">
                                    Operating Head: ${g.operating_head}
                                </div>
                                <table class="w-full text-xs border border-slate-300 border-collapse mb-2">
                                    <thead>
                                        <tr class="bg-slate-50 text-slate-800 font-bold border-b border-slate-300">
                                            <th class="p-2 border border-slate-300 text-center w-8">Sr.</th>
                                            <th class="p-2 border border-slate-300 text-left">Item</th>
                                            <th class="p-2 border border-slate-300 text-left">Object Class</th>
                                            <th class="p-2 border border-slate-300 text-left">Model / Sub Head</th>
                                            <th class="p-2 border border-slate-300 text-right">Allotment</th>
                                            <th class="p-2 border border-slate-300 text-right">P_Exp</th>
                                            <th class="p-2 border border-slate-300 text-right">C_Exp</th>
                                            <th class="p-2 border border-slate-300 text-right bg-emerald-50 text-emerald-950">Total_Exp</th>
                                            <th class="p-2 border border-slate-300 text-right">Total Requirement</th>
                                            <th class="p-2 border border-slate-300 text-right">Change In Req</th>
                                            <th class="p-2 border border-slate-300 text-right">Remaining Req</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rows}
                                        <tr class="bg-slate-100 font-bold text-slate-950">
                                            <td colspan="4" class="p-2 border border-slate-300 text-right uppercase">Total:</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">${formatMoney(g.totals.allotment)}</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">${formatMoney(g.totals.p_exp)}</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">${formatMoney(g.totals.c_exp)}</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono font-black text-emerald-950 whitespace-nowrap">${formatMoney(g.totals.total_exp)}</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">${formatMoney(g.totals.tot_req)}</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">${formatMoney(g.totals.req_change)}</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">${formatMoney(g.totals.rem_req)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        `;
                    });

                    previewSheetContainer.innerHTML = `
                        <div class="border-b-2 border-slate-900 pb-3 mb-4 text-center">
                            <h2 class="text-base sm:text-lg font-black text-slate-950 uppercase">${data.division_name}</h2>
                            <h3 class="text-sm font-extrabold text-emerald-900">${data.report_title}</h3>
                        </div>
                        ${groupsHtml}
                    `;
                }
            }

            // Initial preview load
            loadPreviewData('summary_abstract');
        });
    </script>
</x-layouts.admin>
