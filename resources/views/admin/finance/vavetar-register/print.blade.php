<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vavetar Register - {{ $place ?: 'Plantation' }} ({{ $reportType === 'date_wise' ? 'Date Wise' : 'Work Wise' }}) | Forest Inventory</title>
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
            .vavetar-sheet {
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
                size: A4 landscape;
                margin: 8mm 10mm 10mm 10mm;
            }
        }
    </style>
</head>
<body class="bg-stone-100 text-slate-900 font-sans antialiased p-4 sm:p-6 print:p-0 print:bg-white">
    <div class="max-w-[297mm] mx-auto space-y-4 print:max-w-none print:m-0 print:space-y-0">
        
        <!-- Floating Print Bar (Hidden on Print) -->
        <div class="no-print flex items-center justify-between bg-white rounded-lg border border-emerald-200 p-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold {{ $reportType === 'date_wise' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800' }} border border-emerald-200">
                    {{ $reportType === 'date_wise' ? 'Date Wise Vavetar Register' : 'Work Wise Vavetar Register' }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">
                    {{ $round }} > {{ $beat }} > {{ $place }} ({{ $area ?: 'Plot Area' }})
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

        <!-- Printable Sheet -->
        <div class="vavetar-sheet bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none">
            <!-- Header -->
            <div class="border-b-2 border-slate-900 pb-3 mb-4 text-center space-y-0.5">
                <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase tracking-wide">GUJARAT STATE FOREST DEPARTMENT</h1>
                <h2 class="text-xs sm:text-sm font-bold text-slate-800">{{ $data['division_name'] }} - {{ $data['range_name'] }}</h2>
                <h3 class="text-xs font-black text-emerald-900 uppercase tracking-wider pt-0.5">
                    વાવેતર રજીસ્ટર (PLANTATION EXPENDITURE REGISTER) - {{ $reportType === 'date_wise' ? 'તારીખવાર (DATE WISE)' : 'કામગીરીવાર (WORK WISE)' }}
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] text-slate-800 pt-2 border-t border-slate-300 mt-2 font-semibold text-left">
                    <div><strong>Round:</strong> {{ $data['round'] }}</div>
                    <div><strong>Beat:</strong> {{ $data['beat'] }}</div>
                    <div><strong>Plantation Place:</strong> {{ $data['place'] }}</div>
                    <div><strong>Planted Area:</strong> <span class="font-mono text-emerald-900 font-bold">{{ $data['area'] }}</span></div>
                    <div class="sm:col-span-2"><strong>Scheme:</strong> {{ $data['scheme_name'] }}</div>
                    <div><strong>Year of Creation:</strong> {{ $data['year_of_plantation'] }}</div>
                    <div><strong>Generated:</strong> {{ $data['generated_at'] }}</div>
                </div>
            </div>

            @if($reportType === 'date_wise')
                <!-- Date Wise Table -->
                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-8 whitespace-nowrap">Sr.</th>
                            <th class="p-1.5 border border-slate-300 text-center w-20 whitespace-nowrap">Work Date</th>
                            <th class="p-1.5 border border-slate-300 text-center w-20 whitespace-nowrap">Voucher No</th>
                            <th class="p-1.5 border border-slate-300 text-left w-44">Agency / Party Name</th>
                            <th class="p-1.5 border border-slate-300 text-left">Detailed Particulars of Forestry Operations</th>
                            <th class="p-1.5 border border-slate-300 text-right w-20 whitespace-nowrap">Quantity</th>
                            <th class="p-1.5 border border-slate-300 text-right w-20 whitespace-nowrap">Rate (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-24 whitespace-nowrap">Expenditure (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap bg-emerald-50 text-emerald-950 font-bold">Progressive (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['date_wise_rows'] ?? [] as $index => $row)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $index + 1 }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-semibold whitespace-nowrap">{{ $row['date'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono font-bold text-slate-900 whitespace-nowrap">{{ $row['voucher_no'] }}</td>
                                <td class="p-1.5 border border-slate-300 font-medium text-slate-900">{{ $row['party_name'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-slate-800">{{ $row['description'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-semibold whitespace-nowrap">{{ $row['quantity'] }} {{ $row['unit'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($row['rate'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($row['amount'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-extrabold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">
                                    ₹ {{ number_format($row['cumulative_amount'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-4 text-center text-slate-500">No voucher records found for this location.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold text-slate-950 border-t-2 border-slate-900">
                            <td colspan="7" class="p-1.5 border border-slate-300 text-right uppercase">Total Cumulative Plantation Expenditure:</td>
                            <td colspan="2" class="p-1.5 border border-slate-300 text-right font-mono text-emerald-950 text-xs font-black whitespace-nowrap">
                                ₹ {{ number_format($data['total_expenditure'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <!-- Work Wise Groups -->
                <div class="space-y-4 mb-4">
                    @forelse($data['work_wise_groups'] ?? [] as $gIdx => $grp)
                        <div class="border border-slate-400 rounded-lg overflow-hidden">
                            <div class="bg-slate-100 px-3 py-1.5 flex justify-between items-center border-b border-slate-400">
                                <h4 class="font-bold text-xs text-slate-950">{{ $gIdx + 1 }}. {{ $grp['category'] }}</h4>
                                <span class="font-mono font-bold text-xs text-emerald-900 whitespace-nowrap">Sub-Total: ₹ {{ number_format($grp['total_amount'], 2) }}</span>
                            </div>
                            <table class="w-full text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-300">
                                        <th class="p-1.5 text-left">Activity / Operation Description</th>
                                        <th class="p-1.5 text-center w-24 whitespace-nowrap">Voucher No</th>
                                        <th class="p-1.5 text-center w-20 whitespace-nowrap">Date</th>
                                        <th class="p-1.5 text-right w-20 whitespace-nowrap">Quantity</th>
                                        <th class="p-1.5 text-right w-20 whitespace-nowrap">Rate (₹)</th>
                                        <th class="p-1.5 text-right w-28 whitespace-nowrap">Amount (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grp['items'] as $it)
                                        <tr class="border-b border-slate-200">
                                            <td class="p-1.5 font-medium text-slate-900">{{ $it['description'] }}</td>
                                            <td class="p-1.5 text-center font-mono text-[11px] font-bold text-slate-900 whitespace-nowrap">{{ $it['voucher_no'] }}</td>
                                            <td class="p-1.5 text-center text-slate-700 whitespace-nowrap">{{ $it['date'] }}</td>
                                            <td class="p-1.5 text-right font-semibold whitespace-nowrap">{{ $it['quantity'] }} {{ $it['unit'] }}</td>
                                            <td class="p-1.5 text-right font-mono whitespace-nowrap">₹ {{ number_format($it['rate'], 2) }}</td>
                                            <td class="p-1.5 text-right font-mono font-bold text-slate-950 whitespace-nowrap">₹ {{ number_format($it['amount'], 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @empty
                        <div class="p-4 text-center text-slate-500">No activity records found.</div>
                    @endforelse

                    <div class="p-3 bg-emerald-50 rounded border border-emerald-300 flex justify-between items-center">
                        <span class="font-bold text-slate-900 uppercase text-xs">Total Work-Wise Scheme Expenditure:</span>
                        <span class="font-mono text-sm font-black text-emerald-950 whitespace-nowrap">₹ {{ number_format($data['total_expenditure'], 2) }}</span>
                    </div>
                </div>
            @endif

            <!-- Signatures Section (Compact & Avoid Break) -->
            <div class="signatures-block grid grid-cols-3 gap-6 pt-6 text-center text-xs font-bold text-slate-900">
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>વનરક્ષક (Beat Guard)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['beat'] }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>વનપાલ (Forester / Round Officer)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['round'] }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>પરિક્ષેત્ર વન અધિકારી (RFO)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['range_name'] }}</div>
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
