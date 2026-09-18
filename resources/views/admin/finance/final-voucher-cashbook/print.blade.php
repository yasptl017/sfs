<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['report_title'] }} | {{ $month }} - {{ $paymentMode }}</title>
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
                size: {{ $pageSize === 'Legal' ? 'legal' : 'A4' }} {{ in_array($reportType, ['cashbook', 'cashbook_credit', 'cashbook_debit', 'range_cashbook']) ? 'landscape' : 'portrait' }};
                margin: 8mm 10mm 10mm 10mm;
            }
        }
    </style>
</head>
<body class="bg-stone-100 text-slate-900 font-sans antialiased p-4 sm:p-6 print:p-0 print:bg-white">
    <div class="{{ in_array($reportType, ['cashbook', 'cashbook_credit', 'cashbook_debit', 'range_cashbook']) ? 'max-w-[297mm]' : 'max-w-[210mm]' }} mx-auto space-y-4 print:max-w-none print:m-0 print:space-y-0">
        
        <!-- Floating Actions Bar (Hidden on Print) -->
        <div class="no-print flex items-center justify-between bg-white rounded-lg border border-emerald-200 p-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                    {{ $data['report_title'] }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">
                    Month: <strong>{{ $month }}</strong> | Mode: <strong>{{ $paymentMode }}</strong> | Range: <strong>{{ $data['range_name'] }}</strong>
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

        <!-- Printable Document Sheet -->
        <div class="printable-card bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none">
            
            <!-- Official Department Header -->
            <div class="border-b-2 border-slate-900 pb-3 mb-4 text-center space-y-0.5">
                <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase tracking-wide">GUJARAT STATE FOREST DEPARTMENT</h1>
                <h2 class="text-xs sm:text-sm font-bold text-slate-800">{{ $data['division_name'] }}</h2>
                <h3 class="text-xs font-black text-emerald-900 uppercase tracking-wider pt-0.5">
                    {{ $data['report_title'] }}
                </h3>
                <div class="flex flex-wrap justify-between items-center text-[11px] text-slate-700 pt-2 border-t border-slate-300 mt-2 font-semibold gap-2">
                    <span><strong>Month:</strong> {{ $month }}</span>
                    <span><strong>Payment Mode:</strong> <span class="text-blue-900 font-bold">{{ $paymentMode }}</span></span>
                    <span><strong>Range:</strong> {{ $data['range_name'] }}</span>
                    <span><strong>Date:</strong> {{ $data['generated_at'] }}</span>
                </div>
            </div>

            <!-- Report Format 1: CASH BOOK (Combined, Credit, Debit, Range Cashbook) -->
            @if(in_array($reportType, ['cashbook', 'cashbook_credit', 'cashbook_debit', 'range_cashbook']))
                
                <!-- Cash Book Balance Summary Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4 text-xs">
                    <div class="rounded border border-slate-400 bg-slate-50 p-2 text-center">
                        <span class="text-[10px] text-slate-600 font-bold block uppercase">Opening Balance</span>
                        <span class="text-xs font-black text-slate-950 font-mono whitespace-nowrap">₹ {{ number_format($data['cashbook_summary']['opening_balance'], 2) }}</span>
                    </div>
                    <div class="rounded border border-blue-300 bg-blue-50/60 p-2 text-center">
                        <span class="text-[10px] text-blue-700 font-bold block uppercase">Total Receipts (આવક)</span>
                        <span class="text-xs font-black text-blue-900 font-mono whitespace-nowrap">₹ {{ number_format($data['cashbook_summary']['total_receipts'], 2) }}</span>
                    </div>
                    <div class="rounded border border-rose-300 bg-rose-50/60 p-2 text-center">
                        <span class="text-[10px] text-rose-700 font-bold block uppercase">Total Payments (ખર્ચ)</span>
                        <span class="text-xs font-black text-rose-900 font-mono whitespace-nowrap">₹ {{ number_format($data['cashbook_summary']['total_payments'], 2) }}</span>
                    </div>
                    <div class="rounded border border-emerald-300 bg-emerald-50/60 p-2 text-center">
                        <span class="text-[10px] text-emerald-700 font-bold block uppercase">Closing Balance</span>
                        <span class="text-xs font-black text-emerald-950 font-mono whitespace-nowrap">₹ {{ number_format($data['cashbook_summary']['closing_balance'], 2) }}</span>
                    </div>
                </div>

                <!-- Cash Book Table -->
                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-14 whitespace-nowrap">FV No</th>
                            <th class="p-1.5 border border-slate-300 text-center w-20 whitespace-nowrap">Date</th>
                            <th class="p-1.5 border border-slate-300 text-left">Particulars &amp; Head of Account</th>
                            <th class="p-1.5 border border-slate-300 text-left">Party / Contractor</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap">Gross Value (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap text-rose-800">Deductions (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap font-bold text-emerald-950 bg-emerald-50/50">Net Disbursed (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['entries'] as $entry)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold font-mono text-blue-900 whitespace-nowrap">{{ $entry['voucher_no'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono whitespace-nowrap">{{ $entry['date'] }}</td>
                                <td class="p-1.5 border border-slate-300">
                                    <span class="font-bold text-slate-900 block font-mono text-[11px]">{{ $entry['budget_code'] }}</span>
                                    <span class="text-[11px] text-slate-600 block">{{ $entry['description'] }}</span>
                                </td>
                                <td class="p-1.5 border border-slate-300 font-semibold text-slate-900">{{ $entry['party_name'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-medium whitespace-nowrap">₹ {{ number_format($entry['gross_amount'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono text-rose-700 whitespace-nowrap">₹ {{ number_format($entry['deductions'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">₹ {{ number_format($entry['net_amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                            <td colspan="4" class="p-1.5 border border-slate-300 text-right uppercase">Total Expenditure:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($data['totals']['gross_amount'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-rose-800 whitespace-nowrap">₹ {{ number_format($data['totals']['total_deductions'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-black text-emerald-950 whitespace-nowrap">₹ {{ number_format($data['totals']['net_amount'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

            <!-- Report Format 2: FORM NO. 35 & SORTED VOUCHERS -->
            @else
                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-14 whitespace-nowrap">FV No</th>
                            <th class="p-1.5 border border-slate-300 text-center w-16 whitespace-nowrap">Orig No</th>
                            <th class="p-1.5 border border-slate-300 text-left w-32">Range &amp; Budget Head</th>
                            <th class="p-1.5 border border-slate-300 text-left w-40">Party Details</th>
                            <th class="p-1.5 border border-slate-300 text-left">Description</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap">Gross (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Deductions (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap font-bold text-emerald-950 bg-emerald-50">Net Payable (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['entries'] as $entry)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold font-mono text-blue-900 whitespace-nowrap">{{ $entry['voucher_no'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono text-slate-600 whitespace-nowrap">{{ $entry['original_voucher_no'] }}</td>
                                <td class="p-1.5 border border-slate-300">
                                    <span class="font-bold text-slate-900 block">{{ $entry['range_name'] }}</span>
                                    <span class="text-[11px] text-slate-600 block font-mono">{{ $entry['budget_code'] }}</span>
                                </td>
                                <td class="p-1.5 border border-slate-300 font-semibold text-slate-900">{{ $entry['party_name'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-slate-800">{{ $entry['description'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-medium whitespace-nowrap">₹ {{ number_format($entry['gross_amount'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono text-rose-700 whitespace-nowrap">₹ {{ number_format($entry['deductions'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">₹ {{ number_format($entry['net_amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                            <td colspan="5" class="p-1.5 border border-slate-300 text-right uppercase">GRAND TOTAL VOUCHERS:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($data['totals']['gross_amount'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-rose-800 whitespace-nowrap">₹ {{ number_format($data['totals']['total_deductions'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-black text-emerald-950 whitespace-nowrap">₹ {{ number_format($data['totals']['net_amount'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            @endif

            <!-- Official Signatures (Compact & Break-Inside Avoid) -->
            <div class="signatures-block grid grid-cols-3 gap-6 pt-6 text-center text-xs font-bold text-slate-900">
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>તૈયાર કરનાર (Senior Clerk / Accountant)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>ચકાસણી કરનાર (Account Officer)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>નાયબ વન સંરક્ષક (Deputy Conservator of Forests)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => window.print(), 300);
            });
        }
    </script>
</body>
</html>
