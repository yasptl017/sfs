<x-layouts.admin title="Monthly Reports | Forest Inventory" heading="Monthly Reports" subheading="Division Finance System - Generate &amp; Download Statutory Monthly Accounts">
    <style>
        .monthly-blue-card {
            background-color: #5db6eb !important;
            border-radius: 1.25rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        }
        .monthly-green-card {
            background-color: #6bcc6f !important;
            border-radius: 1rem !important;
        }
        .monthly-label-blue {
            color: #003884 !important;
            font-weight: 800 !important;
            font-size: 0.95rem !important;
            text-align: center !important;
            display: block !important;
            margin-bottom: 0.35rem !important;
            text-decoration: underline !important;
            text-decoration-color: #003884 !important;
        }
        .monthly-input-field {
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
        .monthly-input-field:focus {
            outline: none !important;
            border-color: #003884 !important;
            box-shadow: 0 0 0 3px rgba(0, 56, 132, 0.25) !important;
        }
        .monthly-multiselect {
            min-height: 5.5rem !important;
            max-height: 8rem !important;
            border: 2px solid #ffffff !important;
            border-radius: 0.5rem !important;
            padding: 0.35rem !important;
            background: #ffffff !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            width: 100% !important;
        }
        .monthly-multiselect option {
            padding: 0.35rem 0.5rem !important;
            border-radius: 0.25rem !important;
            margin-bottom: 0.15rem !important;
        }
        .monthly-multiselect option:checked {
            background: #1877f2 linear-gradient(0deg, #1877f2 0%, #1877f2 100%) !important;
            color: #ffffff !important;
        }
        .monthly-tip-box {
            background-color: #f0fdf4 !important;
            border: 1.5px solid #86efac !important;
            border-radius: 0.625rem !important;
            color: #14532d !important;
            padding: 0.65rem 0.75rem !important;
            font-size: 0.8rem !important;
            font-weight: 700 !important;
            text-align: center !important;
            line-height: 1.4 !important;
        }
        .btn-report-blue {
            background-color: #1877f2 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            padding: 0.6rem 1rem !important;
            border-radius: 0.5rem !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            width: 100% !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .btn-report-blue:hover {
            background-color: #0d65d9 !important;
            transform: translateY(-1px) !important;
        }
        .btn-report-blue:active {
            transform: scale(0.98) !important;
        }
        .btn-report-dark {
            background-color: #212529 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            padding: 0.6rem 1rem !important;
            border-radius: 0.5rem !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
            width: 100% !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .btn-report-dark:hover {
            background-color: #000000 !important;
            transform: translateY(-1px) !important;
        }
        .btn-report-dark:active {
            transform: scale(0.98) !important;
        }
    </style>

    <div class="space-y-6" id="monthlyReportModule"
        data-schemes-url="{{ route('division.monthly-reports.schemes') }}"
        data-preview-url="{{ route('division.monthly-reports.preview') }}"
        data-print-url="{{ route('division.monthly-reports.print') }}">

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

        <div id="monthlyAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Main "Generate and Download Monthly Reports:" Form Card (Balanced Horizontal & Vertical Layout) -->
        <section class="max-w-3xl lg:max-w-4xl mx-auto">
            <!-- Outer Sky-Blue Card -->
            <div class="monthly-blue-card p-5 sm:p-6 text-slate-900 overflow-hidden transition-all">
                <!-- Header -->
                <div class="flex items-center justify-between pb-3 border-b border-sky-300/60 mb-5">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-snug">
                        Generate and Download Monthly Reports:
                    </h2>
                    <button type="button" id="closeMonthlyFormBtn" title="Close" class="text-slate-700 hover:text-slate-950 text-xl font-bold transition-colors focus:outline-none leading-none">
                        ✕
                    </button>
                </div>

                <!-- Form Body -->
                <form id="monthlyReportForm" method="GET" action="{{ route('division.monthly-reports.print') }}" target="_blank" class="space-y-4">
                    <input type="hidden" name="report_type" id="reportTypeInput" value="form_53_abstract">

                    <!-- 1. Month Field (Centered in Sky Blue Area) -->
                    <div class="max-w-xs mx-auto mb-2">
                        <label for="month" class="monthly-label-blue">
                            Month:
                        </label>
                        <select id="month" name="month" class="monthly-input-field cursor-pointer text-center">
                            @foreach($months as $m)
                                <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Inner Green Card Container: Balanced 2-Column Grid -->
                    <div class="monthly-green-card p-4 sm:p-5 shadow-sm border border-green-500/30">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-stretch">
                            
                            <!-- Left Column: Controls (Payment Mode, Multi-Select, Tip Box) -->
                            <div class="md:col-span-5 flex flex-col justify-between space-y-3.5">
                                <!-- 2. Payment Mode -->
                                <div>
                                    <label for="payment_mode" class="monthly-label-blue">
                                        Payment Mode:
                                    </label>
                                    <select id="payment_mode" name="payment_mode" class="monthly-input-field cursor-pointer text-center">
                                        <option value="" @selected(empty($selectedPaymentMode))>Select...</option>
                                        <option value="IFMS" @selected($selectedPaymentMode === 'IFMS')>IFMS</option>
                                        <option value="SNA" @selected($selectedPaymentMode === 'SNA')>SNA</option>
                                    </select>
                                </div>

                                <!-- 3. Dynamic Multi-Select Box (Bill Type for IFMS / Scheme for SNA) -->
                                <div>
                                    <label id="dynamicFieldLabel" for="dynamic_select" class="monthly-label-blue">
                                        Bill Type:
                                    </label>
                                    <select multiple id="dynamic_select" name="items[]" class="monthly-multiselect">
                                        <!-- Populated dynamically based on Payment Mode -->
                                    </select>
                                </div>

                                <!-- 4. Multi-Select Gujarati Tip Box -->
                                <div class="monthly-tip-box shadow-sm">
                                    ✨ એકથી વધુ સિલેક્ટ કરી શકો છો, તો તેનો ભેગો રિપોર્ટ ડાઉનલોડ થશે.
                                </div>
                            </div>

                            <!-- Right Column: 8 Action Buttons in a 2-Column Grid -->
                            <div class="md:col-span-7 flex flex-col justify-center">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3">
                                    <button type="button" class="btn-report-blue min-h-[44px]" onclick="triggerReportDownload('form_53_abstract')">
                                        Form-53 Abstract
                                    </button>

                                    <button type="button" class="btn-report-blue min-h-[44px]" onclick="triggerReportDownload('deduction_reports')">
                                        Deduction Reports
                                    </button>

                                    <button type="button" class="btn-report-blue min-h-[44px]" onclick="triggerReportDownload('form_53_new')">
                                        Form-53 New
                                    </button>

                                    <button type="button" class="btn-report-blue min-h-[44px]" onclick="triggerReportDownload('deduction_reports_yearly')">
                                        Deduction Reports Yearly (વાર્ષિક)
                                    </button>

                                    <button type="button" class="btn-report-blue min-h-[44px]" onclick="triggerReportDownload('reconciliation')">
                                        Reconciliation
                                    </button>

                                    <button type="button" class="btn-report-blue min-h-[44px]" onclick="triggerReportDownload('gpf_nps_reports')">
                                        GPF &amp; NPS Reports
                                    </button>

                                    <button type="button" class="btn-report-dark min-h-[44px]" onclick="triggerReportDownload('epayment_report')">
                                        ePayment Report
                                    </button>

                                    <button type="button" class="btn-report-blue min-h-[44px]" onclick="triggerReportDownload('range_reports')">
                                        Range Reports
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- 6. Action Buttons (Outside in Sky-Blue Area - Side by Side) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-md mx-auto pt-2">
                        <button type="button" class="btn-report-dark min-h-[44px]" onclick="triggerReportDownload('challan_report')">
                            Challan Report
                        </button>

                        <button type="button" class="btn-report-dark min-h-[44px]" onclick="triggerReportDownload('pay_slips')">
                            Pay Slips
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- Section 2: Online Live Monthly Report Preview Section -->
        <section id="monthlyPreviewSection" class="hidden rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-4 bg-emerald-50/40 gap-3">
                <div class="flex items-center gap-3">
                    <span id="previewReportBadge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        Form-53 Abstract
                    </span>
                    <h3 id="previewTitle" class="text-base font-semibold text-slate-950">Monthly Report Preview</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrintCurrentPreview" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Print Full Report</span>
                    </button>
                    <button type="button" id="btnClosePreview" class="text-slate-400 hover:text-slate-700 text-xl font-bold px-2">
                        &times;
                    </button>
                </div>
            </div>

            <!-- Preview Sheet Container -->
            <div id="monthlySheetContainer" class="p-6 sm:p-8 bg-white overflow-x-auto space-y-6">
                <!-- Rendered dynamically by JavaScript -->
            </div>
        </section>

        <!-- Section 3: Recent Reports Generated Summary Table -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3 bg-white">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Recent Monthly Statements &amp; Reports</h2>
                    <p class="mt-1 text-sm text-slate-500">Summary of recent monthly finance reports compiled for division treasury.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Sr.</th>
                            <th>Month</th>
                            <th>Mode</th>
                            <th>Report Type</th>
                            <th class="text-center">Bills Count</th>
                            <th class="text-right">Gross Claimed (₹)</th>
                            <th class="text-right">Net Disbursed (₹)</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentReports as $index => $item)
                            <tr class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-bold text-slate-900">{{ $index + 1 }}</td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        {{ $item['month'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $item['mode'] === 'IFMS' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                        {{ $item['mode'] }}
                                    </span>
                                </td>
                                <td class="font-semibold text-slate-950">{{ $item['type'] }}</td>
                                <td class="text-center font-semibold text-slate-800">{{ $item['bills_count'] }} Bills</td>
                                <td class="text-right font-semibold text-slate-900">₹ {{ number_format($item['gross'], 2) }}</td>
                                <td class="text-right font-extrabold text-emerald-900">₹ {{ number_format($item['net'], 2) }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" class="secondary-button min-h-7 px-2.5 py-0.5 text-xs"
                                            onclick="triggerDirectMonthlyPrint('{{ $item['month'] }}', '{{ $item['mode'] }}', 'form_53_abstract')">
                                            Print
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
            const form = document.getElementById('monthlyReportForm');
            const monthSelect = document.getElementById('month');
            const paymentModeSelect = document.getElementById('payment_mode');
            const dynamicFieldLabel = document.getElementById('dynamicFieldLabel');
            const dynamicSelect = document.getElementById('dynamic_select');
            const reportTypeInput = document.getElementById('reportTypeInput');
            const closeMonthlyFormBtn = document.getElementById('closeMonthlyFormBtn');

            const previewSection = document.getElementById('monthlyPreviewSection');
            const previewSheetContainer = document.getElementById('monthlySheetContainer');
            const previewReportBadge = document.getElementById('previewReportBadge');
            const previewTitle = document.getElementById('previewTitle');
            const btnPrintCurrentPreview = document.getElementById('btnPrintCurrentPreview');
            const btnClosePreview = document.getElementById('btnClosePreview');

            const ifmsOptions = @json($ifmsBillTypes);
            const snaOptions = @json($snaSchemes);

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            // Dynamic Option Renderer based on Payment Mode
            function updateDynamicOptions() {
                const mode = paymentModeSelect.value;
                dynamicSelect.innerHTML = '';

                if (mode === 'IFMS') {
                    dynamicFieldLabel.textContent = 'Bill Type:';
                    ifmsOptions.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt;
                        option.textContent = opt;
                        option.selected = true;
                        dynamicSelect.appendChild(option);
                    });
                } else if (mode === 'SNA') {
                    dynamicFieldLabel.textContent = 'Scheme:';
                    snaOptions.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt;
                        option.textContent = opt;
                        option.selected = true;
                        dynamicSelect.appendChild(option);
                    });
                } else {
                    dynamicFieldLabel.textContent = 'Bill Type:';
                }
            }

            paymentModeSelect.addEventListener('change', function () {
                updateDynamicOptions();
                loadPreviewData(reportTypeInput.value);
            });

            monthSelect.addEventListener('change', function () {
                loadPreviewData(reportTypeInput.value);
            });

            // Close button resets form and hides preview
            closeMonthlyFormBtn.addEventListener('click', function () {
                form.reset();
                updateDynamicOptions();
                previewSection.classList.add('hidden');
            });

            btnClosePreview.addEventListener('click', function () {
                previewSection.classList.add('hidden');
            });

            btnPrintCurrentPreview.addEventListener('click', function () {
                form.submit();
            });

            // Action Trigger for 10 report types
            window.triggerReportDownload = function (type) {
                reportTypeInput.value = type;
                form.submit();
            };

            // AJAX Preview Fetcher
            async function loadPreviewData(type = 'form_53_abstract') {
                const previewUrl = document.getElementById('monthlyReportModule').dataset.previewUrl;
                const selectedItems = Array.from(dynamicSelect.selectedOptions).map(o => o.value);

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
                            payment_mode: paymentModeSelect.value || 'IFMS',
                            bill_types: selectedItems,
                            schemes: selectedItems,
                            report_type: type
                        })
                    });

                    if (!response.ok) throw new Error('Preview request failed');
                    const json = await response.json();
                    if (json.success) {
                        renderPreviewSheet(json.data);
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            function renderPreviewSheet(data) {
                previewSection.classList.remove('hidden');
                previewReportBadge.textContent = data.report_title;
                previewTitle.textContent = `${data.report_title} (${data.month} - ${data.payment_mode})`;

                let tableRows = '';
                data.bills.forEach((b, idx) => {
                    tableRows += `
                        <tr class="border-b border-slate-200">
                            <td class="p-2 border border-slate-300 text-center font-bold">${idx + 1}</td>
                            <td class="p-2 border border-slate-300 font-semibold font-mono text-[11px] text-blue-900">${b.advice_no}</td>
                            <td class="p-2 border border-slate-300 font-mono text-slate-800">${b.bill_register_no}</td>
                            <td class="p-2 border border-slate-300 font-mono text-slate-900">${b.budget_code}</td>
                            <td class="p-2 border border-slate-300 font-medium text-slate-900">${b.scheme} (${b.bill_type})</td>
                            <td class="p-2 border border-slate-300 text-right font-mono font-bold">${formatMoney(b.gross_amount)}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono text-rose-700">${formatMoney(b.total_deductions)}</td>
                            <td class="p-2 border border-slate-300 text-right font-mono font-extrabold text-emerald-950 bg-emerald-50/50">${formatMoney(b.net_amount)}</td>
                        </tr>
                    `;
                });

                previewSheetContainer.innerHTML = `
                    <div class="border-b-2 border-slate-900 pb-4 text-center space-y-1">
                        <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase">GUJARAT STATE FOREST DEPARTMENT</h1>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-800">${data.division_name}</h2>
                        <h3 class="text-xs font-extrabold text-emerald-900 uppercase tracking-widest pt-1">
                            ${data.report_title} - ${data.month.toUpperCase()}
                        </h3>
                        <div class="flex flex-wrap justify-between items-center text-xs text-slate-700 pt-3 border-t border-slate-200 mt-2 font-semibold gap-2">
                            <span><strong>Month:</strong> ${data.month}</span>
                            <span><strong>Payment Mode:</strong> <span class="text-blue-900 font-bold">${data.payment_mode}</span></span>
                            <span><strong>Filter:</strong> ${data.selected_items}</span>
                            <span><strong>Date:</strong> ${data.generated_at}</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs border border-slate-300 border-collapse">
                            <thead>
                                <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-300">
                                    <th class="p-2 border border-slate-300 text-center w-8">Sr.</th>
                                    <th class="p-2 border border-slate-300 text-center w-24">Advice No</th>
                                    <th class="p-2 border border-slate-300 text-center w-24">Reg No</th>
                                    <th class="p-2 border border-slate-300 text-left w-32">Budget Code</th>
                                    <th class="p-2 border border-slate-300 text-left">Scheme &amp; Classification</th>
                                    <th class="p-2 border border-slate-300 text-right w-28">Gross (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right w-28">Deductions (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right w-28 bg-emerald-50 text-emerald-950 font-bold">Net (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${tableRows}
                                <tr class="bg-slate-100 font-bold text-slate-950">
                                    <td colspan="5" class="p-2 border border-slate-300 text-right uppercase">Total Grand Summary:</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono text-slate-950 font-black">${formatMoney(data.totals.gross)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono text-rose-800 font-black">${formatMoney(data.totals.deductions)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono text-emerald-950 text-sm font-black">${formatMoney(data.totals.net)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                `;
            }

            // Direct print helper
            window.triggerDirectMonthlyPrint = function (month, mode, type) {
                monthSelect.value = month;
                paymentModeSelect.value = mode;
                updateDynamicOptions();
                triggerReportDownload(type);
            };

            // Initialize options and preview
            updateDynamicOptions();
            loadPreviewData('form_53_abstract');
        });
    </script>
</x-layouts.admin>
