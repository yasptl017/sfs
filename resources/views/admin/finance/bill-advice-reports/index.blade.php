<x-layouts.admin title="Bill &amp; Advice Reports | Forest Inventory" heading="Bill / Advice Reports" subheading="Division Finance System - Generate Bill &amp; Advice Reports">
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

        <!-- Section 1: Generate Bill Reports Form (Themed matching Party Registration) -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-emerald-100 px-5 py-4 flex flex-wrap items-center justify-between gap-3 bg-white">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Generate Bill Reports</h2>
                    <p class="mt-1 text-sm text-slate-500">Select Bill Register No., Advice No., and applicable Tharav descriptions to generate official statements.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-500">Module:</span>
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 border border-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                        <span>Bill / Advice Reports</span>
                    </span>
                </div>
            </div>

            <form id="reportGenerationForm" method="POST" action="{{ route('division.bill-advice-reports.generate') }}" class="p-6 space-y-6">
                @csrf
                <input type="hidden" name="report_type" id="selectedReportTypeInput" value="bill_report">

                <!-- Row 1: Bill Register No & Advice No -->
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="bill_register_no" class="form-label font-semibold text-slate-800">
                            Bill Register No: <span class="text-red-500">*</span>
                        </label>
                        <select id="bill_register_no" name="bill_register_no" required class="form-select font-bold text-slate-900">
                            <option value="" disabled selected>Choose Bill Register No...</option>
                            @foreach($billRegisterNumbers as $bNo)
                                <option value="{{ $bNo }}" @selected(old('bill_register_no') == $bNo || $loop->first)>{{ $bNo }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="advice_no" class="form-label font-semibold text-slate-800">
                            Advice No: <span class="text-red-500">*</span>
                        </label>
                        <select id="advice_no" name="advice_no" required class="form-select font-bold text-slate-900">
                            <option value="" disabled selected>Choose Advice No...</option>
                            @foreach($adviceNumbers as $aNo)
                                <option value="{{ $aNo }}" @selected(old('advice_no') == $aNo || $loop->first)>{{ $aNo }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Row 2: Tharav Description (Multi-select resolutions) -->
                <div class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-extrabold bg-rose-500 text-white uppercase tracking-wide">NEW:</span>
                            <label for="tharav_descriptions" class="text-sm font-bold text-slate-900">
                                Tharav Description (ઠરાવ વિગત):
                            </label>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            ✨ એકથી વધુ સિલેક્ટ કરી શકાય (Multi-Selectable)
                        </span>
                    </div>

                    <!-- Preset Tharav Pills -->
                    <div class="space-y-1.5">
                        <p class="text-xs font-medium text-slate-500">Click resolutions below to quickly add/remove from report:</p>
                        <div class="flex flex-wrap gap-2" id="tharavPillsContainer">
                            @foreach($defaultTharavs as $index => $tharav)
                                <button type="button" 
                                    class="tharav-pill text-xs font-medium text-left px-3 py-1.5 rounded-lg border transition-all duration-150 cursor-pointer border-slate-200 bg-white hover:border-emerald-300 hover:bg-emerald-50/50 text-slate-700"
                                    data-text="{{ $tharav }}">
                                    + {{ $tharav }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Tharav Custom Textarea -->
                    <div>
                        <textarea id="tharav_descriptions" name="tharav_descriptions" rows="4"
                            class="form-textarea text-xs sm:text-sm font-medium leading-relaxed"
                            placeholder="Selected resolutions will appear here. You can also type or modify custom resolutions...">{{ old('tharav_descriptions', implode("\n", array_slice($defaultTharavs, 0, 2))) }}</textarea>
                    </div>

                    <!-- Feature Callout Notice Box (Themed) -->
                    <div class="rounded-lg border border-cyan-200 bg-cyan-50/70 p-4 space-y-1.5 text-xs text-cyan-950">
                        <div class="flex items-center gap-2 font-bold text-cyan-900">
                            <span class="text-base">✨</span>
                            <span>New Feature:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 pl-1 text-cyan-900/90 leading-relaxed">
                            <li>અહીંથી પસંદ કરેલ ઠરાવો સીધા ઓફિસ ઓર્ડર રિપોર્ટમાં પ્રિન્ટ થશે. (Selected resolutions will be printed directly in the Office Order report).</li>
                            <li>નવા ઠરાવો ઉમેરવા માટે Master00 workbook ના 'DropDown' શીટમાં રેન્જ A33:E માં લખો અથવા ઉપર આપેલા બોક્સમાં સીધા ટાઈપ કરો.</li>
                        </ul>
                    </div>
                </div>

                <!-- Row 3: 4 Report Generation Action Buttons -->
                <div class="pt-4 border-t border-emerald-50 flex flex-col items-center gap-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 text-center">
                        Select a report type to generate &amp; preview:
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 w-full max-w-4xl">
                        <!-- 1. GST Report Button -->
                        <button type="button" class="btn-generate-report w-full primary-button bg-blue-600 hover:bg-blue-700 active:scale-95 text-white py-2.5 px-4 font-bold text-sm shadow-sm flex items-center justify-center gap-2"
                            data-report-type="gst_report">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" />
                            </svg>
                            <span>GST Report</span>
                        </button>

                        <!-- 2. Bill Reports Button -->
                        <button type="button" class="btn-generate-report w-full primary-button bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white py-2.5 px-4 font-bold text-sm shadow-sm flex items-center justify-center gap-2"
                            data-report-type="bill_report">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm2 10a1 1 0 100 2h4a1 1 0 100-2H8zm0-3a1 1 0 100 2h4a1 1 0 100-2H8z" clip-rule="evenodd" />
                            </svg>
                            <span>Bill Reports</span>
                        </button>

                        <!-- 3. Deduction Reports Button -->
                        <button type="button" class="btn-generate-report w-full primary-button bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white py-2.5 px-4 font-bold text-sm shadow-sm flex items-center justify-center gap-2"
                            data-report-type="deduction_report">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11 4a1 1 0 10-2 0v4a1 1 0 102 0V7zm-3 1a1 1 0 10-2 0v3a1 1 0 102 0V8zM8 9a1 1 0 00-2 0v2a1 1 0 102 0V9z" clip-rule="evenodd" />
                            </svg>
                            <span>Deduction Reports</span>
                        </button>

                        <!-- 4. Reports for Ranges Button -->
                        <button type="button" class="btn-generate-report w-full primary-button bg-cyan-600 hover:bg-cyan-700 active:scale-95 text-white py-2.5 px-4 font-bold text-sm shadow-sm flex items-center justify-center gap-2"
                            data-report-type="range_report">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                            </svg>
                            <span>Reports for Ranges</span>
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <!-- Section 2: Interactive Live Report Viewer & Print Sheet (Initially Generated on selection) -->
        <section id="reportViewerCard" class="hidden rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-4 bg-emerald-50/40 gap-3">
                <div class="flex items-center gap-3">
                    <span id="activeReportBadge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Bill Reports
                    </span>
                    <h3 id="activeReportTitle" class="text-base font-semibold text-slate-950">Report Preview</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrintReportSheet" class="primary-button text-xs py-1.5 px-3 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5">
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
                <!-- Populated by JavaScript according to the active report type -->
            </div>
        </section>

        <!-- Section 3: History of Generated Reports -->
        @if($recentReports->isNotEmpty())
            <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden">
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
                                    <td class="text-center font-medium text-slate-600">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold 
                                            {{ $rep->report_type === 'gst_report' ? 'bg-blue-50 text-blue-700' : '' }}
                                            {{ $rep->report_type === 'bill_report' ? 'bg-emerald-50 text-emerald-700' : '' }}
                                            {{ $rep->report_type === 'deduction_report' ? 'bg-indigo-50 text-indigo-700' : '' }}
                                            {{ $rep->report_type === 'range_report' ? 'bg-cyan-50 text-cyan-700' : '' }}">
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
            const tharavTextarea = document.getElementById('tharav_descriptions');
            const selectedReportTypeInput = document.getElementById('selectedReportTypeInput');
            const reportViewerCard = document.getElementById('reportViewerCard');
            const printableSheet = document.getElementById('printableSheet');
            const activeReportBadge = document.getElementById('activeReportBadge');
            const activeReportTitle = document.getElementById('activeReportTitle');
            const btnPrintReportSheet = document.getElementById('btnPrintReportSheet');
            const btnExportCsv = document.getElementById('btnExportCsv');
            const btnClosePreview = document.getElementById('btnClosePreview');

            let billAdviceMap = {};
            try {
                billAdviceMap = JSON.parse(document.getElementById('billAdviceReportModule').dataset.billAdviceMap || '{}');
            } catch (e) {
                billAdviceMap = {};
            }

            let currentReportData = null;

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

            // Tharav Pills Interactive Toggle
            const pills = document.querySelectorAll('.tharav-pill');
            const updatePillStyles = () => {
                const currentText = tharavTextarea.value;
                pills.forEach(pill => {
                    const t = pill.dataset.text;
                    if (currentText.includes(t)) {
                        pill.classList.remove('border-slate-200', 'bg-white', 'text-slate-700');
                        pill.classList.add('border-emerald-500', 'bg-emerald-50', 'text-emerald-900', 'font-bold');
                        pill.innerHTML = '✓ ' + t;
                    } else {
                        pill.classList.remove('border-emerald-500', 'bg-emerald-50', 'text-emerald-900', 'font-bold');
                        pill.classList.add('border-slate-200', 'bg-white', 'text-slate-700');
                        pill.innerHTML = '+ ' + t;
                    }
                });
            };

            pills.forEach(pill => {
                pill.addEventListener('click', function () {
                    const text = this.dataset.text;
                    let lines = tharavTextarea.value.split("\n").map(l => l.trim()).filter(Boolean);
                    const idx = lines.indexOf(text);
                    if (idx > -1) {
                        lines.splice(idx, 1);
                    } else {
                        lines.push(text);
                    }
                    tharavTextarea.value = lines.join("\n");
                    updatePillStyles();
                });
            });

            tharavTextarea.addEventListener('input', updatePillStyles);
            updatePillStyles();

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
                            <span><strong>GST Challan:</strong> ${data.gst_challan_no}</span>
                            <span><strong>Date:</strong> ${data.generated_at}</span>
                        </div>
                    </div>
                `;

                if (type === 'gst_report') {
                    html = headerHtml + `
                        <!-- Summary Cards -->
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

                        <!-- GST Table -->
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
                                        <td class="p-2 border border-slate-300 text-center font-mono text-[11px]">${data.gst_challan_no}</td>
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
                        <!-- Tharav (Resolutions) section on Bill Reports -->
                        <div class="mb-6 p-4 rounded-lg border border-emerald-200 bg-emerald-50/40">
                            <h4 class="text-xs font-bold text-emerald-950 uppercase tracking-wider mb-2">
                                ઠરાવ / વહીવટી મંજૂરી સંદર્ભ (Government Resolution References):
                            </h4>
                            <ol class="list-decimal list-inside space-y-1 text-xs text-slate-800 leading-relaxed font-medium">
                                ${tharavs.map(t => `<li>${t}</li>`).join('')}
                            </ol>
                        </div>

                        <!-- Master Bill Table -->
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
                                        <td class="p-2 border border-slate-300 text-right font-medium">${formatMoney(e.gross_amount)}</td>
                                        <td class="p-2 border border-slate-300 text-right text-rose-700">${formatMoney(e.total_deductions)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-bold text-emerald-800">${formatMoney(e.net_amount)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                    <td colspan="4" class="p-2 border border-slate-300 text-right uppercase">Total Amount:</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.gross_amount)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold text-rose-800">${formatMoney(totals.total_deductions)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold text-emerald-950">${formatMoney(totals.net_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                } else if (type === 'deduction_report') {
                    html = headerHtml + `
                        <!-- Deduction Summary Cards -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-center">
                                <span class="text-[11px] text-slate-500 font-semibold block uppercase">Gross Total</span>
                                <span class="text-sm font-bold text-slate-950">${formatMoney(totals.gross_amount)}</span>
                            </div>
                            <div class="rounded-lg border border-rose-200 bg-rose-50/60 p-3 text-center">
                                <span class="text-[11px] text-rose-700 font-semibold block uppercase">Total Deductions</span>
                                <span class="text-sm font-bold text-rose-900">${formatMoney(totals.total_deductions)}</span>
                            </div>
                            <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-3 text-center">
                                <span class="text-[11px] text-amber-700 font-semibold block uppercase">Security Deposit</span>
                                <span class="text-sm font-bold text-amber-900">${formatMoney(totals.deposit)}</span>
                            </div>
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-center">
                                <span class="text-[11px] text-emerald-700 font-semibold block uppercase">Net Disbursed</span>
                                <span class="text-sm font-bold text-emerald-950">${formatMoney(totals.net_amount)}</span>
                            </div>
                        </div>

                        <!-- Deductions Table -->
                        <table class="w-full text-xs border border-slate-300 border-collapse mb-6">
                            <thead>
                                <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300 text-center">
                                    <th class="p-2 border border-slate-300 w-10">Sr.</th>
                                    <th class="p-2 border border-slate-300 text-left">Party Name</th>
                                    <th class="p-2 border border-slate-300 text-right">Gross (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">SGST (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">CGST (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">Labour Cess (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">Deposit (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">TDS (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">Total Ded. (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right font-bold text-emerald-900">Net Payable (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${entries.map((e, idx) => `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-bold">${idx + 1}</td>
                                        <td class="p-2 border border-slate-300 font-semibold text-slate-900">${e.party_name}</td>
                                        <td class="p-2 border border-slate-300 text-right font-medium">${formatMoney(e.gross_amount)}</td>
                                        <td class="p-2 border border-slate-300 text-right">${formatMoney(e.sgst)}</td>
                                        <td class="p-2 border border-slate-300 text-right">${formatMoney(e.cgst)}</td>
                                        <td class="p-2 border border-slate-300 text-right">${formatMoney(e.labour_cess)}</td>
                                        <td class="p-2 border border-slate-300 text-right">${formatMoney(e.deposit)}</td>
                                        <td class="p-2 border border-slate-300 text-right">${formatMoney(e.tds)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-bold text-rose-700">${formatMoney(e.total_deductions)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-bold text-emerald-800">${formatMoney(e.net_amount)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                    <td colspan="2" class="p-2 border border-slate-300 text-right uppercase">Total:</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.gross_amount)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.sgst)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.cgst)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.labour_cess)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.deposit)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.tds)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold text-rose-800">${formatMoney(totals.total_deductions)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold text-emerald-950">${formatMoney(totals.net_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                } else if (type === 'range_report') {
                    const rangeSummary = data.range_summary || [];
                    html = headerHtml + `
                        <!-- Range Summary Table -->
                        <table class="w-full text-xs border border-slate-300 border-collapse mb-6">
                            <thead>
                                <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                                    <th class="p-2 border border-slate-300 text-center w-12">Sr.</th>
                                    <th class="p-2 border border-slate-300 text-left">Range Name</th>
                                    <th class="p-2 border border-slate-300 text-left">Budget Code &amp; Scheme</th>
                                    <th class="p-2 border border-slate-300 text-center w-20">Bills Count</th>
                                    <th class="p-2 border border-slate-300 text-right">Gross Claimed (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right">Total Ded. (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right font-bold text-emerald-900">Net Disbursed (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rangeSummary.map((r, idx) => `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-bold">${idx + 1}</td>
                                        <td class="p-2 border border-slate-300 font-bold text-slate-900">${r.range_name}</td>
                                        <td class="p-2 border border-slate-300">
                                            <span class="font-semibold text-slate-800 block">${r.budget_code}</span>
                                            <span class="text-[11px] text-slate-500 block">${r.scheme}</span>
                                        </td>
                                        <td class="p-2 border border-slate-300 text-center font-bold">${r.count}</td>
                                        <td class="p-2 border border-slate-300 text-right font-medium">${formatMoney(r.gross_amount)}</td>
                                        <td class="p-2 border border-slate-300 text-right text-rose-700">${formatMoney(r.total_deductions)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-bold text-emerald-800">${formatMoney(r.net_amount)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                    <td colspan="3" class="p-2 border border-slate-300 text-right uppercase">Total:</td>
                                    <td class="p-2 border border-slate-300 text-center font-bold">${totals.count}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold">${formatMoney(totals.gross_amount)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold text-rose-800">${formatMoney(totals.total_deductions)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-bold text-emerald-950">${formatMoney(totals.net_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                }

                // Official Signatures Footer
                html += `
                    <div class="grid grid-cols-3 gap-4 pt-12 text-center text-xs font-bold text-slate-900">
                        <div>
                            <div class="border-t border-slate-400 pt-2">Prepared By (Senior Clerk / Accountant)</div>
                        </div>
                        <div>
                            <div class="border-t border-slate-400 pt-2">Verified By (Account Officer)</div>
                        </div>
                        <div>
                            <div class="border-t border-slate-400 pt-2">Approved By (Deputy Conservator of Forests)</div>
                        </div>
                    </div>
                `;

                printableSheet.innerHTML = html;
            };

            btnPrintReportSheet?.addEventListener('click', function () {
                window.print();
            });

            btnClosePreview?.addEventListener('click', function () {
                reportViewerCard.classList.add('hidden');
            });

            btnExportCsv?.addEventListener('click', function () {
                if (!currentReportData || !currentReportData.entries) return;
                const rows = [
                    ['Sr No', 'Range', 'Budget Code', 'Party Name', 'GSTIN', 'Gross Amount', 'SGST', 'CGST', 'Labour Cess', 'Deposit', 'TDS', 'Total Deductions', 'Net Amount']
                ];
                currentReportData.entries.forEach((e, idx) => {
                    rows.push([
                        idx + 1,
                        `"${e.range_name}"`,
                        `"${e.budget_code}"`,
                        `"${e.party_name}"`,
                        `"${e.party_gst_no}"`,
                        e.gross_amount,
                        e.sgst,
                        e.cgst,
                        e.labour_cess,
                        e.deposit,
                        e.tds,
                        e.total_deductions,
                        e.net_amount
                    ]);
                });

                const csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
                const encodedUri = encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", `Report_${currentReportData.report_type}_${currentReportData.bill_register_no}.csv`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            });
        });
    </script>
</x-layouts.admin>
