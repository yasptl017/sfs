<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->report_type_label }} | Bill Register: {{ $report->bill_register_no }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                font-size: 11px !important;
            }
            .no-print {
                display: none !important;
            }
            .printable-card {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                page-break-inside: auto !important;
            }
            tr {
                page-break-inside: avoid !important;
                page-break-after: auto !important;
            }
            thead {
                display: table-header-group !important;
            }
            tfoot {
                display: table-footer-group !important;
            }
            th, td {
                padding: 4px 6px !important;
            }
            .signatures-block {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                margin-top: 1.5rem !important;
            }
            @page {
                size: A4 portrait;
                margin: 8mm 10mm 10mm 10mm;
            }
        }
    </style>
</head>
<body class="bg-stone-100 text-slate-900 font-sans antialiased p-4 sm:p-6 print:p-0 print:bg-white">
    <div class="max-w-[210mm] mx-auto space-y-4 print:max-w-none print:m-0 print:space-y-0">
        
        <!-- Floating Print Actions (Hidden in Print) -->
        <div class="no-print flex items-center justify-between bg-white rounded-lg border border-emerald-200 p-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                    {{ $report->report_type_label }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">
                    Bill Reg No: <strong>{{ $report->bill_register_no }}</strong> | Advice No: <strong>{{ $report->advice_no }}</strong>
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5 font-bold shadow-sm">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Print / Save as PDF</span>
                </button>
                <button onclick="window.close()" class="secondary-button text-xs py-1.5 px-3">
                    Close Window
                </button>
            </div>
        </div>

        <!-- Printable Document Container (Only this prints!) -->
        <div class="printable-card bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none">
            
            <!-- Official Header -->
            <div class="border-b-2 border-slate-900 pb-3 mb-4 text-center space-y-0.5">
                <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase tracking-wide">GUJARAT FOREST DEPARTMENT</h1>
                <h2 class="text-xs sm:text-sm font-bold text-slate-800">{{ $data['division_name'] ?? 'Forest Division Office, Gujarat State' }}</h2>
                <h3 class="text-xs font-black text-emerald-900 uppercase tracking-wider pt-0.5">{{ $report->report_type_label }}</h3>
                <div class="flex flex-wrap justify-between items-center text-[11px] text-slate-700 pt-2 border-t border-slate-300 mt-2 font-semibold gap-2">
                    <span><strong>Bill Register No:</strong> {{ $report->bill_register_no }}</span>
                    <span><strong>Advice No:</strong> {{ $report->advice_no }}</span>
                    <span><strong>GST Challan:</strong> {{ $data['gst_challan_no'] ?? '-' }}</span>
                    <span><strong>Date:</strong> {{ $report->created_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>

            <!-- Tharav Section (if present) -->
            @if(!empty($data['tharav_descriptions']))
                <div class="mb-4 p-3 rounded-lg border border-emerald-200 bg-emerald-50/40 text-xs">
                    <h4 class="font-bold text-emerald-950 uppercase tracking-wider mb-1.5">
                        ઠરાવ / વહીવટી મંજૂરી સંદર્ભ (Government Resolution References):
                    </h4>
                    <ol class="list-decimal list-inside space-y-0.5 text-slate-800 font-medium leading-relaxed">
                        @foreach($data['tharav_descriptions'] as $t)
                            <li>{{ $t }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <!-- 1. GST REPORT FORMAT -->
            @if($report->report_type === 'gst_report')
                <!-- GST Summary Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
                    <div class="rounded border border-slate-300 bg-slate-50 p-2 text-center">
                        <span class="text-[10px] text-slate-600 font-bold block uppercase">Total Gross Value</span>
                        <span class="text-xs font-extrabold text-slate-950 font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['gross_amount'] ?? 0, 2) }}</span>
                    </div>
                    <div class="rounded border border-blue-200 bg-blue-50/60 p-2 text-center">
                        <span class="text-[10px] text-blue-700 font-bold block uppercase">Total CGST</span>
                        <span class="text-xs font-extrabold text-blue-900 font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['cgst'] ?? 0, 2) }}</span>
                    </div>
                    <div class="rounded border border-blue-200 bg-blue-50/60 p-2 text-center">
                        <span class="text-[10px] text-blue-700 font-bold block uppercase">Total SGST</span>
                        <span class="text-xs font-extrabold text-blue-900 font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['sgst'] ?? 0, 2) }}</span>
                    </div>
                    <div class="rounded border border-emerald-200 bg-emerald-50/60 p-2 text-center">
                        <span class="text-[10px] text-emerald-700 font-bold block uppercase">Total GST Amount</span>
                        <span class="text-xs font-extrabold text-emerald-950 font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['total_gst'] ?? 0, 2) }}</span>
                    </div>
                </div>

                <!-- GST Table -->
                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-10 whitespace-nowrap">Sr.</th>
                            <th class="p-1.5 border border-slate-300 text-left">Party Name</th>
                            <th class="p-1.5 border border-slate-300 text-center w-28 whitespace-nowrap">GSTIN</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap">Taxable Value (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">CGST (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">SGST (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap font-bold text-emerald-900">Total GST (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-center w-24 whitespace-nowrap">Challan No.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['entries'] ?? [] as $index => $entry)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="p-1.5 border border-slate-300 font-semibold text-slate-900">{{ $entry['party_name'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono whitespace-nowrap">{{ $entry['party_gst_no'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-medium whitespace-nowrap">₹ {{ number_format($entry['gross_amount'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($entry['cgst'] ?? 0, 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($entry['sgst'] ?? 0, 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-800 whitespace-nowrap">₹ {{ number_format($entry['total_gst'] ?? 0, 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono text-[11px] whitespace-nowrap">{{ $data['gst_challan_no'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                            <td colspan="3" class="p-1.5 border border-slate-300 text-right uppercase">Total:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-bold font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['gross_amount'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-bold font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['cgst'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-bold font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['sgst'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-bold font-mono text-emerald-950 whitespace-nowrap">₹ {{ number_format($data['totals']['total_gst'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300"></td>
                        </tr>
                    </tfoot>
                </table>

            <!-- 2. BILL REPORTS FORMAT -->
            @elseif($report->report_type === 'bill_report')
                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-10 whitespace-nowrap">Sr.</th>
                            <th class="p-1.5 border border-slate-300 text-left w-36">Range &amp; Budget Head</th>
                            <th class="p-1.5 border border-slate-300 text-left w-44">Party Details</th>
                            <th class="p-1.5 border border-slate-300 text-left">Description</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap">Gross (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Deductions (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap font-bold text-emerald-950 bg-emerald-50">Net Payable (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['entries'] ?? [] as $index => $entry)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="p-1.5 border border-slate-300">
                                    <span class="font-bold text-slate-900 block">{{ $entry['range_name'] }}</span>
                                    <span class="text-[11px] text-slate-600 block font-mono">{{ $entry['budget_code'] }}</span>
                                </td>
                                <td class="p-1.5 border border-slate-300">
                                    <span class="font-bold text-slate-950 block">{{ $entry['party_name'] }}</span>
                                    <span class="text-[11px] text-slate-500 font-mono block">{{ $entry['party_gst_no'] ?? '' }}</span>
                                </td>
                                <td class="p-1.5 border border-slate-300 text-slate-800 leading-relaxed">{{ $entry['description'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-medium whitespace-nowrap">₹ {{ number_format($entry['gross_amount'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono text-rose-700 whitespace-nowrap">₹ {{ number_format($entry['deductions'] ?? $entry['total_deductions'] ?? 0, 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-900 bg-emerald-50/40 whitespace-nowrap">₹ {{ number_format($entry['net_payable'] ?? $entry['net_amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                            <td colspan="4" class="p-1.5 border border-slate-300 text-right uppercase">Grand Total:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-bold font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['gross_amount'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-bold font-mono text-rose-700 whitespace-nowrap">₹ {{ number_format($data['totals']['total_deductions'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-bold font-mono text-emerald-950 whitespace-nowrap">₹ {{ number_format($data['totals']['net_amount'] ?? 0, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

            <!-- 3. DEDUCTION REPORTS FORMAT -->
            @elseif($report->report_type === 'deduction_report')
                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-10 whitespace-nowrap">Sr.</th>
                            <th class="p-1.5 border border-slate-300 text-left">Party Name</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Income Tax (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">GST TDS (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Prof Tax (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Security Dep (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap font-bold text-rose-900 bg-rose-50">Total Deductions (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['entries'] ?? [] as $index => $entry)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="p-1.5 border border-slate-300 font-semibold text-slate-900">{{ $entry['party_name'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($entry['it_deduction'] ?? 0, 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($entry['gst_tds'] ?? 0, 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($entry['pt_deduction'] ?? 0, 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($entry['sd_deduction'] ?? 0, 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-rose-800 bg-rose-50/40 whitespace-nowrap">₹ {{ number_format($entry['deductions'] ?? $entry['total_deductions'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                            <td colspan="2" class="p-1.5 border border-slate-300 text-right uppercase">Total Deductions:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($data['totals']['it_total'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($data['totals']['gst_tds_total'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($data['totals']['pt_total'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($data['totals']['sd_total'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-black text-rose-900 whitespace-nowrap">₹ {{ number_format($data['totals']['total_deductions'] ?? 0, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

            <!-- 4. RANGE REPORTS FORMAT -->
            @elseif($report->report_type === 'range_report')
                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-10 whitespace-nowrap">Sr.</th>
                            <th class="p-1.5 border border-slate-300 text-left">Range Name</th>
                            <th class="p-1.5 border border-slate-300 text-center w-24 whitespace-nowrap">Vouchers Count</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap">Gross Claimed (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Deductions (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap font-bold text-emerald-950 bg-emerald-50">Net Disbursed (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['entries'] ?? [] as $index => $entry)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="p-1.5 border border-slate-300 font-bold text-slate-900">{{ $entry['range_name'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-medium whitespace-nowrap">{{ $entry['voucher_count'] ?? 1 }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-medium whitespace-nowrap">₹ {{ number_format($entry['gross_amount'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono text-rose-700 whitespace-nowrap">₹ {{ number_format($entry['deductions'] ?? $entry['total_deductions'] ?? 0, 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-900 bg-emerald-50/40 whitespace-nowrap">₹ {{ number_format($entry['net_payable'] ?? $entry['net_amount'] ?? 0, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                            <td colspan="3" class="p-1.5 border border-slate-300 text-right uppercase">Total Range Summary:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($data['totals']['gross_amount'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-rose-700 whitespace-nowrap">₹ {{ number_format($data['totals']['total_deductions'] ?? 0, 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-950 whitespace-nowrap">₹ {{ number_format($data['totals']['net_amount'] ?? 0, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif

            <!-- Official Signatures (Compact & Avoid Break) -->
            <div class="signatures-block grid grid-cols-3 gap-6 pt-6 text-center text-xs font-bold text-slate-900">
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>તૈયાર કરનાર (Senior Clerk / Accountant)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] ?? 'Division Office' }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>ચકાસણી કરનાર (Account Officer)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] ?? 'Division Office' }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>મંજૂર કરનાર (Deputy Conservator of Forests)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] ?? 'Division Office' }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Auto trigger print if requested in query
        if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => window.print(), 300);
            });
        }
    </script>
</body>
</html>
