<x-layouts.admin title="Bill &amp; Advice Reports | Forest Inventory" heading="Bill / Advice Reports" subheading="Division Finance System - Generate &amp; Download Bill Reports">
    <style>
        .bill-report-card {
            background: #ffffff !important;
            border-radius: 1.25rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #e2e8f0 !important;
        }
        .bill-report-label {
            color: #003884 !important;
            font-weight: 800 !important;
            font-size: 0.95rem !important;
            text-align: center !important;
            display: block !important;
            margin-bottom: 0.35rem !important;
        }
        .bill-report-select-maroon {
            border: 2px solid #5c0606 !important;
            border-radius: 9999px !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            padding: 0.5rem 1.25rem !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            width: 100% !important;
            text-align: center !important;
            text-align-last: center !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
            transition: all 0.15s ease-in-out !important;
        }
        .bill-report-select-maroon:focus {
            outline: none !important;
            border-color: #7f1d1d !important;
            box-shadow: 0 0 0 3px rgba(92, 6, 6, 0.2) !important;
        }
        .bill-report-multiselect-blue {
            border: 2px solid #0038ff !important;
            border-radius: 0.875rem !important;
            padding: 0.5rem !important;
            background: #ffffff !important;
            font-size: 0.825rem !important;
            font-weight: 600 !important;
            color: #1e293b !important;
            width: 100% !important;
            min-height: 7rem !important;
            max-height: 9.5rem !important;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05) !important;
        }
        .bill-report-multiselect-blue option {
            padding: 0.4rem 0.6rem !important;
            border-radius: 0.375rem !important;
            margin-bottom: 0.2rem !important;
            white-space: normal !important;
            line-height: 1.35 !important;
        }
        .bill-report-multiselect-blue option:checked {
            background: #1877f2 linear-gradient(0deg, #1877f2 0%, #1877f2 100%) !important;
            color: #ffffff !important;
        }
        .bill-report-pill-badge {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.35rem !important;
            padding: 0.3rem 0.85rem !important;
            border-radius: 9999px !important;
            font-size: 0.8rem !important;
            font-weight: 800 !important;
            background-color: #ffffff !important;
            color: #0038ff !important;
            border: 1.5px solid #0038ff !important;
            box-shadow: 0 1px 3px rgba(0, 56, 255, 0.1) !important;
        }
        .bill-report-cyan-box {
            background-color: #dcf6fd !important;
            border: 1.5px solid #80e5f7 !important;
            border-radius: 0.875rem !important;
            color: #083344 !important;
            padding: 0.75rem 1rem !important;
            font-size: 0.8rem !important;
            line-height: 1.45 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04) !important;
        }
        .btn-pill-blue {
            background-color: #1877f2 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            padding: 0.65rem 1.5rem !important;
            border-radius: 9999px !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 4px 6px -1px rgba(24, 119, 242, 0.3), 0 2px 4px -2px rgba(24, 119, 242, 0.2) !important;
            width: 100% !important;
            max-width: 14rem !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .btn-pill-blue:hover {
            background-color: #0d65d9 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 10px -1px rgba(24, 119, 242, 0.4) !important;
        }
        .btn-pill-blue:active {
            transform: scale(0.98) !important;
        }
        .btn-pill-cyan {
            background-color: #00c2cb !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            padding: 0.65rem 1.5rem !important;
            border-radius: 9999px !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 4px 6px -1px rgba(0, 194, 203, 0.3), 0 2px 4px -2px rgba(0, 194, 203, 0.2) !important;
            width: 100% !important;
            max-width: 14rem !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .btn-pill-cyan:hover {
            background-color: #00a9b1 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 10px -1px rgba(0, 194, 203, 0.4) !important;
        }
        .btn-pill-cyan:active {
            transform: scale(0.98) !important;
        }
        @media print {
            body, html {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            aside,
            nav,
            header,
            .app-header,
            .sidebar,
            .nav-section-title,
            .nav-group,
            .nav-submenu,
            #billAdviceReportModule > section:not(#reportViewerCard),
            #reportAlertBox,
            .no-print,
            button,
            .border-b.bg-emerald-50\/40 {
                display: none !important;
            }
            #reportViewerCard {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
                width: 100% !important;
            }
            #printableSheet {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                display: block !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>

    <div class="space-y-6" id="billAdviceReportModule"
        data-details-url="{{ route('division.bill-advice-reports.details') }}"
        data-generate-url="{{ route('division.bill-advice-reports.generate') }}"
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

        <div id="reportAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Main "Generate Bill Reports:" Form Card (Balanced Horizontal & Vertical Layout) -->
        <section class="max-w-3xl lg:max-w-4xl mx-auto">
            <div class="bill-report-card p-5 sm:p-7 text-slate-900 overflow-hidden transition-all">
                
                <!-- Header with Close Button -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-6">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-snug">
                        Generate Bill Reports:
                    </h2>
                    <button type="button" id="closeBillReportFormBtn" title="Close" class="text-slate-500 hover:text-slate-900 text-xl font-bold transition-colors focus:outline-none leading-none">
                        ✕
                    </button>
                </div>

                <!-- Form Body: Balanced 2-Column Responsive Layout -->
                <form id="reportGenerationForm" method="POST" action="{{ route('division.bill-advice-reports.generate') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="report_type" id="selectedReportTypeInput" value="bill_report">

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-stretch">
                        
                        <!-- Left Column: Controls (Bill Register No, Advice No, Tharav Multi-Select, Pill Badge) -->
                        <div class="md:col-span-6 flex flex-col justify-between space-y-4">
                            
                            <!-- 1. Bill Register No -->
                            <div>
                                <label for="bill_register_no" class="bill-report-label">
                                    Bill Register No:
                                </label>
                                <select id="bill_register_no" name="bill_register_no" required class="bill-report-select-maroon cursor-pointer">
                                    <option value="" disabled selected>Select...</option>
                                    @foreach($billRegisterNumbers as $bNo)
                                        <option value="{{ $bNo }}" @selected(old('bill_register_no') == $bNo || $loop->first)>{{ $bNo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 2. Advice No -->
                            <div>
                                <label for="advice_no" class="bill-report-label">
                                    Advice No:
                                </label>
                                <select id="advice_no" name="advice_no" required class="bill-report-select-maroon cursor-pointer">
                                    <option value="" disabled selected>Select...</option>
                                    @foreach($adviceNumbers as $aNo)
                                        <option value="{{ $aNo }}" @selected(old('advice_no') == $aNo || $loop->first)>{{ $aNo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 3. NEW: Tharav Description Multi-Select Box -->
                            <div>
                                <div class="flex items-center justify-center gap-2 mb-1.5">
                                    <span class="bg-[#d9222a] text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider shadow-sm">
                                        NEW:
                                    </span>
                                    <label for="tharav_descriptions" class="bill-report-label !mb-0">
                                        Tharav Description:
                                    </label>
                                </div>
                                <select multiple id="tharav_descriptions" name="tharav_descriptions[]" class="bill-report-multiselect-blue">
                                    @foreach($defaultTharavs as $index => $tharav)
                                        <option value="{{ $tharav }}" @selected($index < 2)>{{ $tharav }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 4. Helper Pill Badge -->
                            <div class="flex justify-center pt-0.5">
                                <span class="bill-report-pill-badge">
                                    ✨ એકથી વધુ સિલેક્ટ કરી શકાય
                                </span>
                            </div>

                        </div>

                        <!-- Right Column: New Feature Notice Box + 4 Action Buttons -->
                        <div class="md:col-span-6 flex flex-col justify-between space-y-5">
                            
                            <!-- 5. Feature Callout Box -->
                            <div class="bill-report-cyan-box">
                                <div class="font-black text-cyan-950 flex items-center gap-1.5 text-xs mb-1.5">
                                    <span>✨</span>
                                    <span>New Feature:</span>
                                </div>
                                <ul class="list-disc list-inside space-y-1.5 pl-0.5 text-cyan-950 font-medium">
                                    <li>અહીંથી પસંદ કરેલ ઠરાવો સીધા ઓફિસ ઓર્ડર રિપોર્ટમાં પ્રિન્ટ થશે.</li>
                                    <li>નવા ઠરાવો ઉમેરવા માટે <strong>Master00 workbook</strong> ના <strong>'DropDown'</strong> શીટમાં રેન્જ <strong>A33:E</strong> માં લખો.</li>
                                </ul>
                            </div>

                            <!-- 6. 4 Action Buttons (Centered Pill Buttons) -->
                            <div class="flex flex-col items-center justify-center space-y-3 pt-1">
                                <button type="button" class="btn-pill-blue btn-generate-report" data-report-type="gst_report">
                                    GST Report
                                </button>

                                <button type="button" class="btn-pill-blue btn-generate-report" data-report-type="bill_report">
                                    Bill Reports
                                </button>

                                <button type="button" class="btn-pill-blue btn-generate-report" data-report-type="deduction_report">
                                    Deduction Reports
                                </button>

                                <button type="button" class="btn-pill-cyan btn-generate-report" data-report-type="range_report">
                                    Reports for Ranges
                                </button>
                            </div>

                        </div>

                    </div>
                </form>
            </div>
        </section>

        <!-- Section 2: Interactive Live Report Viewer & Print Sheet -->
        <section id="reportViewerCard" class="hidden rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-4 bg-emerald-50/40 gap-3">
                <div class="flex items-center gap-3">
                    <span id="activeReportBadge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        Bill Reports
                    </span>
                    <h3 id="activeReportTitle" class="text-base font-semibold text-slate-950">Report Preview</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrintReportSheet" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Print Report</span>
                    </button>
                    <button type="button" id="btnExportCsv" class="secondary-button text-xs py-1.5 px-3">
                        Export CSV
                    </button>
                    <button type="button" id="btnClosePreview" class="text-slate-400 hover:text-slate-700 text-xl font-bold px-2">
                        &times;
                    </button>
                </div>
            </div>

            <!-- Printable Sheet Container -->
            <div id="printableSheet" class="p-6 sm:p-8 bg-white overflow-x-auto print:p-0">
                <!-- Populated by JavaScript according to active report type -->
            </div>
        </section>

        <!-- Section 3: History of Generated Reports -->
        @if($recentReports->isNotEmpty())
            <section class="rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-950">Recently Generated Reports</h2>
                        <p class="mt-1 text-sm text-slate-500">History of Bill, GST, Deduction, and Range report generations</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="data-table w-full text-left">
                        <thead>
                            <tr>
                                <th class="w-14 text-center">Sr. No.</th>
                                <th>Report Type</th>
                                <th>Bill Register No.</th>
                                <th>Advice No.</th>
                                <th>Generated At</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($recentReports as $index => $rep)
                                <tr class="transition-colors hover:bg-emerald-50/40">
                                    <td class="text-center font-bold text-slate-700">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold 
                                            {{ $rep->report_type === 'gst_report' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                                            {{ $rep->report_type === 'bill_report' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : '' }}
                                            {{ $rep->report_type === 'deduction_report' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : '' }}
                                            {{ $rep->report_type === 'range_report' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : '' }}">
                                            {{ $rep->report_type_label }}
                                        </span>
                                    </td>
                                    <td class="font-semibold text-slate-900">{{ $rep->bill_register_no }}</td>
                                    <td class="font-semibold text-slate-800">{{ $rep->advice_no }}</td>
                                    <td class="text-xs text-slate-500">{{ $rep->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="text-right">
                                        <a href="{{ route('division.bill-advice-reports.show', $rep) }}" target="_blank" class="secondary-button min-h-7 px-2.5 py-0.5 text-xs">
                                            View &amp; Print
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>

    <!-- Interactive JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('reportGenerationForm');
            const alertBox = document.getElementById('reportAlertBox');
            const billSelect = document.getElementById('bill_register_no');
            const adviceSelect = document.getElementById('advice_no');
            const tharavSelect = document.getElementById('tharav_descriptions');
            const selectedReportTypeInput = document.getElementById('selectedReportTypeInput');
            const reportViewerCard = document.getElementById('reportViewerCard');
            const printableSheet = document.getElementById('printableSheet');
            const activeReportBadge = document.getElementById('activeReportBadge');
            const activeReportTitle = document.getElementById('activeReportTitle');
            const btnPrintReportSheet = document.getElementById('btnPrintReportSheet');
            const btnExportCsv = document.getElementById('btnExportCsv');
            const btnClosePreview = document.getElementById('btnClosePreview');
            const closeBillReportFormBtn = document.getElementById('closeBillReportFormBtn');

            let billAdviceMap = {};
            try {
                billAdviceMap = JSON.parse(document.getElementById('billAdviceReportModule').dataset.billAdviceMap || '{}');
            } catch (e) {
                billAdviceMap = {};
            }

            let currentReportData = null;
            let currentPrintUrl = null;

            // Auto-update advice numbers on bill change
            billSelect.addEventListener('change', function () {
                const bNo = billSelect.value;
                if (bNo && billAdviceMap[bNo] && billAdviceMap[bNo].length > 0) {
                    const firstAdvice = billAdviceMap[bNo][0].advice_no;
                    if (firstAdvice) {
                        adviceSelect.value = firstAdvice;
                    }
                }
            });

            // Close button resets form and hides preview
            closeBillReportFormBtn.addEventListener('click', function () {
                form.reset();
                reportViewerCard.classList.add('hidden');
            });

            btnClosePreview.addEventListener('click', function () {
                reportViewerCard.classList.add('hidden');
            });

            btnPrintReportSheet.addEventListener('click', function () {
                if (currentPrintUrl) {
                    window.open(currentPrintUrl + '?autoprint=1', '_blank');
                } else {
                    window.print();
                }
            });

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            // Report Generation on Button Clicks
            document.querySelectorAll('.btn-generate-report').forEach(btn => {
                btn.addEventListener('click', async function () {
                    const reportType = this.dataset.reportType;
                    selectedReportTypeInput.value = reportType;

                    const billNo = billSelect.value;
                    const adviceNo = adviceSelect.value;

                    if (!billNo || !adviceNo) {
                        alert('Please select both Bill Register No. and Advice No. first.');
                        return;
                    }

                    const formData = new FormData(form);
                    formData.set('report_type', reportType);

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        if (response.ok) {
                            const res = await response.json();
                            currentReportData = res.data;
                            currentPrintUrl = res.print_url || null;
                            renderReportSheet(reportType, res.data);
                            reportViewerCard.classList.remove('hidden');
                            reportViewerCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else {
                            const err = await response.json();
                            alert(err.message || 'Error generating report.');
                        }
                    } catch (e) {
                        console.error('Report error:', e);
                        alert('Failed to generate report. Please try again.');
                    }
                });
            });

            const renderReportSheet = (type, data) => {
                const bNo = data.bill_register_no;
                const aNo = data.advice_no;
                const totals = data.totals || {};
                const entries = data.entries || [];
                const tharavs = data.tharav_descriptions || [];

                let badgeName = 'Bill Reports';
                if (type === 'gst_report') badgeName = 'GST Report';
                if (type === 'deduction_report') badgeName = 'Deduction Reports';
                if (type === 'range_report') badgeName = 'Reports for Ranges';

                activeReportBadge.textContent = badgeName;
                activeReportTitle.textContent = `${badgeName} - ${bNo} / ${aNo}`;

                let html = '';

                // Header for official report
                const headerHtml = `
                    <div class="border-b-2 border-slate-900 pb-4 mb-6 text-center space-y-1">
                        <h1 class="text-xl font-extrabold text-slate-950 uppercase tracking-wide">GUJARAT FOREST DEPARTMENT</h1>
                        <h2 class="text-base font-bold text-slate-800">${data.division_name}</h2>
                        <h3 class="text-sm font-semibold text-emerald-800 uppercase tracking-wider">${badgeName.toUpperCase()}</h3>
                        <div class="flex flex-wrap justify-between items-center text-xs text-slate-600 pt-2 font-medium">
                            <span><strong>Bill Register No:</strong> ${bNo}</span>
                            <span><strong>Advice No:</strong> ${aNo}</span>
                            <span><strong>GST Challan:</strong> ${data.gst_challan_no || '-'}</span>
                            <span><strong>Date:</strong> ${data.generated_at}</span>
                        </div>
                    </div>
                `;

                if (type === 'gst_report') {
                    html = headerHtml + `
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-center">
                                <span class="text-[11px] text-slate-500 font-semibold block uppercase">Total Gross Value</span>
                                <span class="text-sm font-bold text-slate-950">${formatMoney(totals.gross_amount)}</span>
                            </div>
                            <div class="rounded-lg border border-blue-200 bg-blue-50/60 p-3 text-center">
                                <span class="text-[11px] text-blue-700 font-semibold block uppercase">Total CGST</span>
                                <span class="text-sm font-bold text-blue-900">${formatMoney(totals.cgst)}</span>
                            </div>
                            <div class="rounded-lg border border-blue-200 bg-blue-50/60 p-3 text-center">
                                <span class="text-[11px] text-blue-700 font-semibold block uppercase">Total SGST</span>
                                <span class="text-sm font-bold text-blue-900">${formatMoney(totals.sgst)}</span>
                            </div>
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-center">
                                <span class="text-[11px] text-emerald-700 font-semibold block uppercase">Total GST Amount</span>
                                <span class="text-sm font-bold text-emerald-950">${formatMoney(totals.total_gst)}</span>
                            </div>
                        </div>

                        <table class="w-full text-xs border border-slate-300 border-collapse mb-6">
                            <thead>
                                <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                    <th class="p-2 border border-slate-300 text-center w-12">Sr.</th>
                                    <th class="p-2 border border-slate-300 text-left">Party Name</th>
                                    <th class="p-2 border border-slate-300 text-center">GSTIN</th>
                                    <th class="p-2 border border-slate-300 text-right">Taxable Value (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">CGST (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">SGST (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">Total GST (₹)</th>
                                    <th class="p-2 border border-slate-300 text-center">Challan No.</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${entries.map((e, idx) => `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-medium">${idx + 1}</td>
                                        <td class="p-2 border border-slate-300 font-semibold text-slate-900">${e.party_name}</td>
                                        <td class="p-2 border border-slate-300 text-center font-mono">${e.party_gst_no}</td>
                                        <td class="p-2 border border-slate-300 text-right font-medium">${formatMoney(e.gross_amount)}</td>
                                        <td class="p-2 border border-slate-300 text-right">${formatMoney(e.cgst)}</td>
                                        <td class="p-2 border border-slate-300 text-right">${formatMoney(e.sgst)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-bold text-emerald-800">${formatMoney(e.total_gst)}</td>
                                        <td class="p-2 border border-slate-300 text-center font-mono text-[11px]">${data.gst_challan_no || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                    <td colspan="3" class="p-2 border border-slate-300 text-right uppercase">Total:</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.gross_amount)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.cgst)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.sgst)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold text-emerald-900">${formatMoney(totals.total_gst)}</td>
                                    <td class="p-2 border border-slate-300"></td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                } else if (type === 'bill_report') {
                    html = headerHtml + `
                        <div class="mb-6 p-4 rounded-lg border border-emerald-200 bg-emerald-50/40">
                            <h4 class="text-xs font-bold text-emerald-950 uppercase tracking-wider mb-2">
                                ઠરાવ / વહીવટી મંજૂરી સંદર્ભ (Government Resolution References):
                            </h4>
                            <ol class="list-decimal list-inside space-y-1 text-xs text-slate-800 leading-relaxed font-medium">
                                ${tharavs.map(t => `<li>${t}</li>`).join('')}
                            </ol>
                        </div>

                        <table class="w-full text-xs border border-slate-300 border-collapse mb-6">
                            <thead>
                                <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                    <th class="p-2 border border-slate-300 text-center w-12">Sr.</th>
                                    <th class="p-2 border border-slate-300 text-left">Range &amp; Budget Head</th>
                                    <th class="p-2 border border-slate-300 text-left">Party Name &amp; Bank Details</th>
                                    <th class="p-2 border border-slate-300 text-left">Description / Particulars</th>
                                    <th class="p-2 border border-slate-300 text-right">Gross (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">Deductions (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right font-bold text-emerald-900">Net Payable (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${entries.map((e, idx) => `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-bold">${idx + 1}</td>
                                        <td class="p-2 border border-slate-300">
                                            <span class="font-bold text-slate-900 block">${e.range_name}</span>
                                            <span class="text-[11px] text-slate-600 block">${e.budget_code}</span>
                                        </td>
                                        <td class="p-2 border border-slate-300">
                                            <span class="font-bold text-slate-950 block">${e.party_name}</span>
                                            <span class="text-[11px] text-slate-500 font-mono block">${e.bank_name} - ${e.account_no}</span>
                                        </td>
                                        <td class="p-2 border border-slate-300 text-slate-800 leading-relaxed">${e.description}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono font-medium">${formatMoney(e.gross_amount)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono text-rose-700">${formatMoney(e.deductions)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono font-bold text-emerald-900">${formatMoney(e.net_payable)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                    <td colspan="4" class="p-2 border border-slate-300 text-right uppercase">Grand Total:</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold font-mono">${formatMoney(totals.gross_amount)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold font-mono text-rose-700">${formatMoney(totals.total_deductions)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold font-mono text-emerald-950">${formatMoney(totals.net_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                } else if (type === 'deduction_report') {
                    html = headerHtml + `
                        <table class="w-full text-xs border border-slate-300 border-collapse mb-6">
                            <thead>
                                <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                    <th class="p-2 border border-slate-300 text-center w-12">Sr.</th>
                                    <th class="p-2 border border-slate-300 text-left">Party Name</th>
                                    <th class="p-2 border border-slate-300 text-right">Income Tax (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">GST TDS (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">Prof Tax (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">Security Dep (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right font-bold text-rose-900">Total Deductions (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${entries.map((e, idx) => `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-medium">${idx + 1}</td>
                                        <td class="p-2 border border-slate-300 font-semibold text-slate-900">${e.party_name}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(e.it_deduction)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(e.gst_tds)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(e.pt_deduction)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(e.sd_deduction)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono font-bold text-rose-800">${formatMoney(e.deductions)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                    <td colspan="2" class="p-2 border border-slate-300 text-right uppercase">Total Deductions:</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(totals.it_total)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(totals.gst_tds_total)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(totals.pt_total)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(totals.sd_total)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold text-rose-900">${formatMoney(totals.total_deductions)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                } else if (type === 'range_report') {
                    html = headerHtml + `
                        <table class="w-full text-xs border border-slate-300 border-collapse mb-6">
                            <thead>
                                <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                    <th class="p-2 border border-slate-300 text-center w-12">Sr.</th>
                                    <th class="p-2 border border-slate-300 text-left">Range Name</th>
                                    <th class="p-2 border border-slate-300 text-center">Vouchers Count</th>
                                    <th class="p-2 border border-slate-300 text-right">Gross Claimed (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">Deductions (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right font-bold text-emerald-950">Net Disbursed (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${entries.map((e, idx) => `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-bold">${idx + 1}</td>
                                        <td class="p-2 border border-slate-300 font-bold text-slate-900">${e.range_name}</td>
                                        <td class="p-2 border border-slate-300 text-center font-medium">${e.voucher_count || 1}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono font-medium">${formatMoney(e.gross_amount)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono text-rose-700">${formatMoney(e.deductions)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono font-bold text-emerald-900">${formatMoney(e.net_payable)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                    <td colspan="3" class="p-2 border border-slate-300 text-right uppercase">Total Range Summary:</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold">${formatMoney(totals.gross_amount)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold text-rose-700">${formatMoney(totals.total_deductions)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold text-emerald-950">${formatMoney(totals.net_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                }

                printableSheet.innerHTML = html;
            };

            // CSV Export Handler
            btnExportCsv.addEventListener('click', function () {
                if (!currentReportData || !currentReportData.entries) {
                    alert('No data available to export.');
                    return;
                }
                const rows = currentReportData.entries;
                let csv = 'Sr,Party Name,Gross Amount,Deductions,Net Payable\n';
                rows.forEach((r, idx) => {
                    csv += `"${idx + 1}","${r.party_name || r.range_name || ''}","${r.gross_amount || 0}","${r.deductions || 0}","${r.net_payable || 0}"\n`;
                });
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.setAttribute('href', url);
                a.setAttribute('download', `report_${currentReportData.bill_register_no}_${currentReportData.advice_no}.csv`);
                a.click();
            });
        });
    </script>
</x-layouts.admin>
