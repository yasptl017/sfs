<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abstract Print - {{ $month }} ({{ $docketNo ?: 'All Dockets' }}) | Forest Inventory</title>
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
            .abstract-sheet {
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
                size: A4 {{ $mode === 'detailed' ? 'landscape' : 'portrait' }};
                margin: 8mm 10mm 10mm 10mm;
            }
        }
    </style>
</head>
<body class="bg-stone-100 text-slate-900 font-sans antialiased p-4 sm:p-6 print:p-0 print:bg-white">
    <div class="{{ $mode === 'detailed' ? 'max-w-[297mm]' : 'max-w-[210mm]' }} mx-auto space-y-4 print:max-w-none print:m-0 print:space-y-0">
        
        <!-- Floating Print Header (Hidden on Print) -->
        <div class="no-print flex items-center justify-between bg-white rounded-lg border border-emerald-200 p-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    {{ $mode === 'detailed' ? 'Detailed Abstract (With Rate & Qty)' : 'Summary Abstract' }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">
                    Month: <strong>{{ $month }}</strong> | Docket: <strong>{{ $docketNo ?: 'All' }}</strong>
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5 font-bold shadow-sm">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Print / Save as PDF</span>
                </button>
                <button onclick="window.close()" class="secondary-button text-xs py-1.5 px-3">
                    Close Window
                </button>
            </div>
        </div>

        <!-- Printable Abstract Document -->
        <div class="abstract-sheet bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none">
            <!-- Header with Dynamic Logo & Office Profile -->
            <div class="border-b-2 border-slate-900 pb-3 mb-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="w-16 h-16 flex-shrink-0 flex items-center justify-center">
                        <img src="{{ $profile->logo_url }}" alt="Logo" class="max-h-16 max-w-16 object-contain">
                    </div>
                    <div class="flex-1 text-center space-y-0.5">
                        <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase tracking-wide">GUJARAT FOREST DEPARTMENT</h1>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-800">{{ $profile->office_name ?: ($data['division_name'] . ' - ' . $data['range_name']) }}</h2>
                        @if($profile->office_name_gujarati)
                            <p class="text-xs font-bold text-emerald-950">{{ $profile->office_name_gujarati }}</p>
                        @endif
                        @if($profile->formatted_address)
                            <p class="text-[10px] text-slate-600 leading-tight">{{ $profile->formatted_address }} @if($profile->phone)| Ph: {{ $profile->phone }}@endif @if($profile->email)| {{ $profile->email }}@endif</p>
                        @endif
                        <h3 class="text-xs font-black text-emerald-900 uppercase tracking-wider pt-0.5">
                            MONTHLY VOUCHER ABSTRACT (ગોશવારો / એબ્સ્ટ્રેક્ટ) - {{ strtoupper($month) }}
                        </h3>
                    </div>
                    <div class="w-16 text-right text-[10px] font-mono text-slate-500 flex-shrink-0">
                        <div>ABSTRACT</div>
                        <div class="font-bold text-slate-800">{{ strtoupper($month) }}</div>
                    </div>
                </div>
                <div class="flex flex-wrap justify-between items-center text-[11px] text-slate-700 pt-2 border-t border-slate-300 mt-2 font-semibold gap-2">
                    <span><strong>Month:</strong> {{ $month }}</span>
                    <span><strong>Docket No:</strong> {{ $docketNo ?: 'All Dockets' }}</span>
                    <span><strong>Print Mode:</strong> {{ $mode === 'detailed' ? 'With Rate & Quantity' : 'Summary' }}</span>
                    <span><strong>Date:</strong> {{ $data['generated_at'] }}</span>
                </div>
            </div>

            <!-- Master Abstract Table -->
            <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                <thead>
                    <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                        <th class="p-1.5 border border-slate-300 text-center w-8 whitespace-nowrap">Sr.</th>
                        <th class="p-1.5 border border-slate-300 text-left w-40">Voucher No. &amp; Party Name</th>
                        <th class="p-1.5 border border-slate-300 text-left w-36">Budget Code &amp; Scheme</th>
                        <th class="p-1.5 border border-slate-300 text-left">Work Particulars {{ $mode === 'detailed' ? '& Plot Area' : '' }}</th>
                        @if($mode === 'detailed')
                            <th class="p-1.5 border border-slate-300 text-center w-20 whitespace-nowrap">Work Dates</th>
                            <th class="p-1.5 border border-slate-300 text-right w-16 whitespace-nowrap">Quantity</th>
                            <th class="p-1.5 border border-slate-300 text-right w-16 whitespace-nowrap">Rate (₹)</th>
                        @endif
                        <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap">Gross (₹)</th>
                        <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Deductions (₹)</th>
                        <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap text-emerald-950 font-bold bg-emerald-50">Net (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['entries'] ?? [] as $index => $entry)
                        @php $it = $entry['items'][0] ?? []; @endphp
                        <tr class="border-b border-slate-200">
                            <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $index + 1 }}</td>
                            <td class="p-1.5 border border-slate-300">
                                <strong class="text-slate-950 block">{{ $entry['party_name'] }}</strong>
                                <span class="text-[11px] text-slate-600 block font-mono">{{ $entry['voucher_no'] }}</span>
                            </td>
                            <td class="p-1.5 border border-slate-300">
                                <strong class="text-emerald-900 block font-mono text-[11px]">{{ $entry['budget_code'] }}</strong>
                                <span class="text-[11px] text-slate-600 block">{{ $entry['scheme'] }}</span>
                            </td>
                            <td class="p-1.5 border border-slate-300">
                                <p class="text-slate-900 leading-relaxed">{{ $it['description'] ?? $entry['party_name'] }}</p>
                                @if($mode === 'detailed' && !empty($it['plot_area']))
                                    <span class="text-[11px] text-blue-800 font-semibold block">📍 {{ $it['plot_area'] }}</span>
                                @endif
                            </td>
                            @if($mode === 'detailed')
                                <td class="p-1.5 border border-slate-300 text-center text-[11px] text-slate-700 whitespace-nowrap">{{ $it['work_dates'] ?? '-' }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-bold whitespace-nowrap">{{ $it['quantity'] ?? 1 }} {{ $it['unit'] ?? '' }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($it['rate'] ?? 0, 2) }}</td>
                            @endif
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-medium whitespace-nowrap">₹ {{ number_format($entry['gross_amount'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono text-rose-700 whitespace-nowrap">₹ {{ number_format($entry['total_deductions'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">₹ {{ number_format($entry['net_amount'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                        <td colspan="{{ $mode === 'detailed' ? 7 : 4 }}" class="p-1.5 border border-slate-300 text-right uppercase">Grand Total (કુલ રકમ):</td>
                        <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($data['totals']['gross_amount'] ?? 0, 2) }}</td>
                        <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-rose-800 whitespace-nowrap">₹ {{ number_format($data['totals']['total_deductions'] ?? 0, 2) }}</td>
                        <td class="p-1.5 border border-slate-300 text-right font-mono font-black text-emerald-950 whitespace-nowrap">₹ {{ number_format($data['totals']['net_amount'] ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Budget Head Wise Summary Table -->
            @if(!empty($data['budget_breakdown']))
                <div class="space-y-2 pt-2 mb-4">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                        Budget Head Wise Allocation Summary (બજેટ હેડ વાઇઝ વિગત):
                    </h4>
                    <table class="w-full text-xs border border-slate-400 border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-slate-800 font-semibold border-b border-slate-400">
                                <th class="p-1.5 border border-slate-300 text-left w-36 whitespace-nowrap">Budget Code</th>
                                <th class="p-1.5 border border-slate-300 text-left">Scheme</th>
                                <th class="p-1.5 border border-slate-300 text-center w-20 whitespace-nowrap">Vouchers</th>
                                <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap">Gross (₹)</th>
                                <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Deductions (₹)</th>
                                <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap font-bold text-emerald-950 bg-emerald-50">Net (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['budget_breakdown'] as $b)
                                <tr class="border-b border-slate-200">
                                    <td class="p-1.5 border border-slate-300 font-mono font-bold text-emerald-900 whitespace-nowrap">{{ $b['budget_code'] }}</td>
                                    <td class="p-1.5 border border-slate-300 text-slate-700">{{ $b['scheme'] }}</td>
                                    <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $b['vouchers_count'] }}</td>
                                    <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($b['gross_amount'], 2) }}</td>
                                    <td class="p-1.5 border border-slate-300 text-right font-mono text-rose-700 whitespace-nowrap">₹ {{ number_format($b['total_deductions'], 2) }}</td>
                                    <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">₹ {{ number_format($b['net_amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- Signatures (Compact & Avoid Break) -->
            <div class="signatures-block grid grid-cols-3 gap-6 pt-6 text-center text-xs font-bold text-slate-900">
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>તૈયાર કરનાર</div>
                        <div class="text-[11px] font-semibold text-slate-700">(Cashier / Clerk)</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>ચકાસણી કરનાર</div>
                        <div class="text-[11px] font-semibold text-slate-700">(Forester / Accountant)</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>{{ $profile->officer_name ?: 'મંજૂર કરનાર' }}</div>
                        <div class="text-[11px] font-semibold text-slate-700">({{ $profile->officer_designation ?: 'Range Forest Officer' }})</div>
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
