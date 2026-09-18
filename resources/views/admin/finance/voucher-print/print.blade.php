<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher Print - {{ $entryType }} | Forest Inventory</title>
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
            .voucher-page {
                box-shadow: none !important;
                border: 1px solid #000000 !important;
                padding: 1rem !important;
                margin-bottom: 0 !important;
                page-break-after: always !important;
            }
            .voucher-page.fit-page {
                page-break-inside: avoid !important;
                min-height: 98vh !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
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
                margin-top: 1rem !important;
            }
            @page {
                size: A4 portrait;
                margin: 8mm 10mm 10mm 10mm;
            }
        }
        @media screen {
            .voucher-page {
                margin-bottom: 1.5rem;
            }
        }
    </style>
</head>
<body class="bg-stone-100 text-slate-900 font-sans antialiased p-4 sm:p-6 print:p-0 print:bg-white">
    <div class="max-w-[210mm] mx-auto space-y-4 print:max-w-none print:m-0 print:space-y-0">
        
        <!-- Floating Print Actions (Hidden in Print) -->
        <div class="no-print flex items-center justify-between bg-white rounded-lg border border-emerald-200 p-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    {{ $entryType }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">Total <strong>{{ count($vouchers) }}</strong> Vouchers to Print</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5 font-bold shadow-sm">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Print All Vouchers</span>
                </button>
                <button onclick="window.close()" class="secondary-button text-xs py-1.5 px-3">
                    Close Window
                </button>
            </div>
        </div>

        <!-- Vouchers List -->
        @forelse($vouchers as $index => $v)
            <div class="voucher-page bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm space-y-4 print:border-slate-800 print:shadow-none {{ $fitToPage ? 'fit-page' : '' }}">
                
                <!-- Header -->
                <div class="border-b-2 border-slate-900 pb-3 text-center space-y-0.5">
                    <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase tracking-wide">GUJARAT FOREST DEPARTMENT</h1>
                    <h2 class="text-xs sm:text-sm font-bold text-slate-800">{{ $v['division_name'] }} - {{ $v['range_name'] }}</h2>
                    <h3 class="text-xs font-black text-emerald-900 uppercase tracking-wider pt-0.5">{{ $v['entry_type'] }} VOUCHER (FORM NO. 35)</h3>
                    <div class="flex flex-wrap justify-between items-center text-[11px] text-slate-700 pt-2 border-t border-slate-300 mt-2 font-semibold gap-2">
                        <span><strong>Voucher No:</strong> <span class="font-mono text-blue-900 font-bold">{{ $v['voucher_no'] }}</span> (Sr: {{ $v['serial_number'] }})</span>
                        <span><strong>Docket:</strong> {{ $v['docket_no'] }}</span>
                        <span><strong>Month:</strong> {{ $v['month'] }}</span>
                        <span><strong>Date:</strong> {{ $v['date'] }}</span>
                    </div>
                </div>

                <!-- Budget & Location -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs bg-slate-50 p-2 rounded border border-slate-200">
                    <div><span class="text-slate-500 block text-[10px]">Budget Code:</span><strong class="text-slate-900 font-mono">{{ $v['budget_code'] }}</strong></div>
                    <div><span class="text-slate-500 block text-[10px]">Scheme:</span><strong class="text-slate-900">{{ $v['scheme'] }}</strong></div>
                    <div><span class="text-slate-500 block text-[10px]">Round / Beat:</span><strong class="text-slate-900">{{ $v['round'] }} / {{ $v['beat'] }}</strong></div>
                    <div><span class="text-slate-500 block text-[10px]">Place / Survey:</span><strong class="text-slate-900">{{ $v['place'] }}</strong></div>
                </div>

                <!-- Payee & Approval Details -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs border border-slate-200 p-2.5 rounded">
                    <div>
                        <span class="text-slate-500 block text-[10px] font-bold uppercase">Payee / Party Details:</span>
                        <p class="text-xs font-bold text-slate-950">{{ $v['party_name'] }}</p>
                        <p class="text-slate-600 text-[11px]">{{ $v['party_address'] }}</p>
                        <p class="text-slate-600 font-mono text-[10px]">PAN: {{ $v['pan_card_no'] }} | GSTIN: {{ $v['gst_no'] }}</p>
                        <p class="text-slate-600 font-mono text-[10px]">{{ $v['bank_name'] }} - A/C: {{ $v['account_no'] }} (IFSC: {{ $v['ifsc'] }})</p>
                    </div>
                    <div class="space-y-1 sm:border-l sm:border-slate-200 sm:pl-3">
                        <div><span class="text-slate-500 block text-[10px] font-bold uppercase">Approval / Letter No:</span><strong class="text-emerald-900 font-mono text-xs">{{ $v['approval_no'] ?: 'None' }}</strong></div>
                        @if($withWorkOrder)
                            <div><span class="text-slate-500 block text-[10px] font-bold uppercase">Work Order No:</span><strong class="text-blue-900 font-mono text-xs">{{ $v['work_order_no'] }} (Dt. {{ $v['work_order_date'] }})</strong></div>
                        @endif
                    </div>
                </div>

                <!-- Measurement Table -->
                <table class="w-full text-xs border border-slate-400 border-collapse mb-2">
                    <thead>
                        <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-8 whitespace-nowrap">Sr.</th>
                            <th class="p-1.5 border border-slate-300 text-left">Description of Works &amp; Operations</th>
                            <th class="p-1.5 border border-slate-300 text-center w-10 whitespace-nowrap">L</th>
                            <th class="p-1.5 border border-slate-300 text-center w-10 whitespace-nowrap">B</th>
                            <th class="p-1.5 border border-slate-300 text-center w-10 whitespace-nowrap">D</th>
                            <th class="p-1.5 border border-slate-300 text-right w-14 whitespace-nowrap">Qty</th>
                            <th class="p-1.5 border border-slate-300 text-center w-12 whitespace-nowrap">Unit</th>
                            <th class="p-1.5 border border-slate-300 text-right w-20 whitespace-nowrap">Rate (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap font-bold">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($v['items'] as $it)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $it['sr_no'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-slate-900 leading-relaxed">{{ $it['description'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono whitespace-nowrap">{{ $it['length'] ?: '-' }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono whitespace-nowrap">{{ $it['breadth'] ?: '-' }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono whitespace-nowrap">{{ $it['depth'] ?: '-' }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-bold whitespace-nowrap">{{ $it['quantity'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-center whitespace-nowrap">{{ $it['unit'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($it['rate'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($it['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50 font-bold border-t-2 border-slate-800">
                            <td colspan="8" class="p-1.5 border border-slate-300 text-right uppercase">Gross Total (કુલ રકમ):</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-slate-950 whitespace-nowrap">₹ {{ number_format($v['gross_amount'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Deductions & Net -->
                <div class="flex flex-col sm:flex-row justify-between items-start gap-3 text-xs border-t border-slate-200 pt-2">
                    <div class="w-full sm:w-1/2 space-y-0.5 bg-slate-50 p-2 rounded border border-slate-200">
                        <span class="font-bold text-slate-800 block uppercase text-[10px] mb-0.5">Deductions (કપાત વિગત):</span>
                        <div class="flex justify-between text-slate-600"><span>SGST:</span><span class="font-mono whitespace-nowrap">₹ {{ number_format($v['deductions']['sgst'], 2) }}</span></div>
                        <div class="flex justify-between text-slate-600"><span>CGST:</span><span class="font-mono whitespace-nowrap">₹ {{ number_format($v['deductions']['cgst'], 2) }}</span></div>
                        <div class="flex justify-between text-slate-600"><span>Labour Cess:</span><span class="font-mono whitespace-nowrap">₹ {{ number_format($v['deductions']['labour_cess'], 2) }}</span></div>
                        <div class="flex justify-between text-slate-600"><span>Security Deposit:</span><span class="font-mono whitespace-nowrap">₹ {{ number_format($v['deductions']['deposit'], 2) }}</span></div>
                        <div class="flex justify-between text-slate-600"><span>TDS / IT:</span><span class="font-mono whitespace-nowrap">₹ {{ number_format($v['deductions']['tds'], 2) }}</span></div>
                        <div class="flex justify-between font-bold text-rose-800 border-t border-slate-200 pt-0.5"><span>Total Deductions:</span><span class="font-mono whitespace-nowrap">₹ {{ number_format($v['deductions']['total'], 2) }}</span></div>
                    </div>
                    <div class="w-full sm:w-1/2 space-y-1 text-right">
                        <div class="p-2 bg-emerald-50 rounded border border-emerald-200">
                            <span class="text-[10px] text-emerald-800 uppercase font-bold block">Net Payable Amount (ચુકવવાપાત્ર ચોખ્ખી રકમ):</span>
                            <span class="text-base font-extrabold text-emerald-950 block font-mono whitespace-nowrap">₹ {{ number_format($v['net_amount'], 2) }}</span>
                            <span class="text-[11px] text-slate-700 italic block mt-0.5">{{ $v['net_amount_in_words'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- Signatures (Compact & Avoid Break) -->
                <div class="signatures-block grid grid-cols-4 gap-2 pt-4 text-center text-xs font-bold text-slate-800 border-t border-slate-300">
                    <div class="px-1"><div class="border-t border-slate-400 pt-1">Beat Guard / Forester</div></div>
                    <div class="px-1"><div class="border-t border-slate-400 pt-1">Cashier / Clerk</div></div>
                    <div class="px-1"><div class="border-t border-slate-400 pt-1">Range Forest Officer (RFO)</div></div>
                    <div class="px-1"><div class="border-t border-slate-400 pt-1">Payee Signature</div></div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-500">
                No vouchers found to print.
            </div>
        @endforelse
    </div>
</body>
</html>
