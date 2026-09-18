<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['report_title'] }} - {{ $month }} ({{ $paymentMode }}) | Forest Inventory</title>
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
            .monthly-sheet {
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
        
        <!-- Floating Print Control Bar (Hidden on Print) -->
        <div class="no-print flex items-center justify-between bg-white rounded-lg border border-emerald-200 p-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                    {{ $data['report_title'] }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">
                    Month: <strong>{{ $month }}</strong> | Mode: <strong>{{ $paymentMode }}</strong>
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

        <!-- Printable Document -->
        <div class="monthly-sheet bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none">
            <!-- Header -->
            <div class="border-b-2 border-slate-900 pb-3 mb-4 text-center space-y-0.5">
                <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase tracking-wide">GUJARAT STATE FOREST DEPARTMENT</h1>
                <h2 class="text-xs sm:text-sm font-bold text-slate-800">{{ $data['division_name'] }}</h2>
                <h3 class="text-xs font-black text-emerald-900 uppercase tracking-wider pt-0.5">
                    {{ $data['report_title'] }}
                </h3>
                <div class="flex flex-wrap justify-between items-center text-[11px] text-slate-700 pt-2 border-t border-slate-300 mt-2 font-semibold gap-2">
                    <span><strong>Month:</strong> {{ $data['month'] }}</span>
                    <span><strong>Payment Mode:</strong> <span class="text-blue-900 font-bold">{{ $data['payment_mode'] }}</span></span>
                    <span><strong>Filters:</strong> {{ $data['selected_items'] }}</span>
                    <span><strong>Date:</strong> {{ $data['generated_at'] }}</span>
                </div>
            </div>

            <!-- Master Table -->
            <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                <thead>
                    <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                        <th class="p-1.5 border border-slate-300 text-center w-10 whitespace-nowrap">Sr.</th>
                        <th class="p-1.5 border border-slate-300 text-center w-20 whitespace-nowrap">Advice No</th>
                        <th class="p-1.5 border border-slate-300 text-center w-20 whitespace-nowrap">Reg No</th>
                        <th class="p-1.5 border border-slate-300 text-left w-32 whitespace-nowrap">Budget Code</th>
                        <th class="p-1.5 border border-slate-300 text-left">Scheme &amp; Particulars</th>
                        <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap">Gross (₹)</th>
                        <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Deductions (₹)</th>
                        <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap bg-emerald-50 text-emerald-950 font-bold">Net Amount (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['bills'] as $index => $b)
                        <tr class="border-b border-slate-200">
                            <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $index + 1 }}</td>
                            <td class="p-1.5 border border-slate-300 text-center font-mono font-bold text-blue-900 whitespace-nowrap">{{ $b['advice_no'] }}</td>
                            <td class="p-1.5 border border-slate-300 text-center font-mono text-slate-800 whitespace-nowrap">{{ $b['bill_register_no'] }}</td>
                            <td class="p-1.5 border border-slate-300 font-mono text-slate-900 whitespace-nowrap">{{ $b['budget_code'] }}</td>
                            <td class="p-1.5 border border-slate-300">
                                <span class="font-medium text-slate-950 block">{{ $b['scheme'] }}</span>
                                <span class="text-[11px] text-slate-600 block">Type: {{ $b['bill_type'] }} | Outward: {{ $b['order_outward_no'] }}</span>
                            </td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-medium whitespace-nowrap">₹ {{ number_format($b['gross_amount'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono text-rose-700 whitespace-nowrap">₹ {{ number_format($b['total_deductions'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">
                                ₹ {{ number_format($b['net_amount'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-4 text-center text-slate-500">No bill or expenditure records found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-bold text-slate-950 border-t-2 border-slate-900">
                        <td colspan="5" class="p-1.5 border border-slate-300 text-right uppercase">Total Grand Summary:</td>
                        <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($data['totals']['gross'], 2) }}</td>
                        <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-rose-800 whitespace-nowrap">₹ {{ number_format($data['totals']['deductions'], 2) }}</td>
                        <td class="p-1.5 border border-slate-300 text-right font-mono font-black text-emerald-950 whitespace-nowrap">₹ {{ number_format($data['totals']['net'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Deductions Summary Box -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs bg-slate-50 p-3 rounded border border-slate-300 mb-4">
                <div><span class="text-slate-600">GST TDS (2%):</span> <strong class="font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['gst_tds'], 2) }}</strong></div>
                <div><span class="text-slate-600">IT TDS:</span> <strong class="font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['it_tds'], 2) }}</strong></div>
                <div><span class="text-slate-600">Labour Cess (1%):</span> <strong class="font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['labour_cess'], 2) }}</strong></div>
                <div><span class="text-slate-600">Professional Tax:</span> <strong class="font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['pt'], 2) }}</strong></div>
                <div><span class="text-slate-600">GPF Subscription:</span> <strong class="font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['gpf'], 2) }}</strong></div>
                <div><span class="text-slate-600">NPS Contribution:</span> <strong class="font-mono whitespace-nowrap">₹ {{ number_format($data['totals']['nps'], 2) }}</strong></div>
                <div class="sm:col-span-2 font-bold text-emerald-950 border-t sm:border-t-0 pt-1 sm:pt-0">
                    Total Bills: <span class="font-mono">{{ $data['totals']['count'] }}</span> | Net Disbursed: <span class="font-mono whitespace-nowrap font-black">₹ {{ number_format($data['totals']['net'], 2) }}</span>
                </div>
            </div>

            <!-- Official Signatures (Compact & Avoid Break) -->
            <div class="signatures-block grid grid-cols-3 gap-6 pt-6 text-center text-xs font-bold text-slate-900">
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>મુખ્ય હિસાબનીશ (Head Accountant)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>અધીક્ષક (Superintendent)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>નાયબ વન સંરક્ષક (Dy. Conservator of Forests)</div>
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
