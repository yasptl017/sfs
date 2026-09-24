<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOR Limit Report - {{ $data['range_name'] }} ({{ strtoupper($reportMode) }}) | Gujarat Forest Department</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                font-size: 10.5px !important;
            }
            .no-print {
                display: none !important;
            }
            .sor-sheet {
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
                padding: 4px 5px !important;
            }
            .signatures-block {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                margin-top: 1.5rem !important;
            }
            @page {
                size: A4 landscape;
                margin: 8mm 8mm 8mm 8mm;
            }
        }
    </style>
</head>
<body class="bg-stone-100 text-slate-900 font-sans antialiased p-4 sm:p-6 print:p-0 print:bg-white">
    <div class="max-w-[297mm] mx-auto space-y-4 print:max-w-none print:m-0 print:space-y-0">
        
        <!-- Floating Print Bar (Hidden on Print) -->
        <div class="no-print flex items-center justify-between bg-white rounded-lg border border-emerald-200 p-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold {{ $reportMode === 'violations' ? 'bg-rose-100 text-rose-800' : ($reportMode === 'summary' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800') }} border border-slate-200">
                    {{ strtoupper($reportMode) }} SOR REPORT
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">
                    {{ $data['range_name'] }} &bull; {{ $month }} {{ $year }} &bull; Budget: {{ $budgetCode ?: 'All' }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5 font-bold shadow-sm text-white rounded">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Print / Save as PDF</span>
                </button>
                <button onclick="window.close()" class="secondary-button text-xs py-1.5 px-3 border border-slate-300 rounded bg-white hover:bg-slate-50">
                    Close Window
                </button>
            </div>
        </div>

        <!-- Printable Sheet -->
        <div class="sor-sheet bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none">
            
            <!-- Header with Dynamic Logo & Office Profile -->
            <div class="border-b-2 border-slate-900 pb-3 mb-3">
                <div class="flex items-center justify-between gap-4">
                    <div class="w-16 h-16 flex-shrink-0 flex items-center justify-center">
                        <img src="{{ $profile->logo_url }}" alt="Logo" class="max-h-16 max-w-16 object-contain">
                    </div>
                    <div class="flex-1 text-center space-y-0.5">
                        <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase tracking-wide">GUJARAT STATE FOREST DEPARTMENT</h1>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-800">{{ $profile->office_name ?: ($data['division_name'] . ' - ' . $data['range_name']) }}</h2>
                        @if($profile->office_name_gujarati)
                            <p class="text-xs font-bold text-emerald-950">{{ $profile->office_name_gujarati }}</p>
                        @endif
                        @if($profile->formatted_address)
                            <p class="text-[10px] text-slate-600 leading-tight">{{ $profile->formatted_address }} @if($profile->phone)| Ph: {{ $profile->phone }}@endif @if($profile->email)| {{ $profile->email }}@endif</p>
                        @endif
                        <h3 class="text-xs font-black text-emerald-900 uppercase tracking-wider pt-0.5">
                            એસ.ઓ.આર. લિમિટ ચકાસણી અને ખર્ચ મોનિટરિંગ રિપોર્ટ (SOR LIMIT & EXPENDITURE VERIFICATION REPORT)
                        </h3>
                    </div>
                    <div class="w-20 text-right text-[10px] font-mono text-slate-500 flex-shrink-0">
                        <div>REPORT TYPE</div>
                        <div class="font-bold text-slate-800 uppercase">{{ $reportMode }}</div>
                    </div>
                </div>

                <!-- Parameters Meta Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] text-slate-800 pt-2 border-t border-slate-300 mt-2 font-semibold text-left">
                    <div><strong>Budget Code:</strong> <span class="font-mono text-emerald-900 font-bold">{{ $data['budget_code'] }}</span></div>
                    <div><strong>Month / Year:</strong> {{ $data['month'] }} {{ $data['year'] }}</div>
                    <div><strong>Round:</strong> {{ $data['round'] }}</div>
                    <div><strong>Beat:</strong> {{ $data['beat'] }}</div>
                    <div><strong>Site / Place:</strong> {{ $data['place'] }}</div>
                    <div><strong>SOR Code:</strong> {{ $data['sor_code'] }}</div>
                    <div class="sm:col-span-2"><strong>Scheme:</strong> {{ $data['scheme_name'] }}</div>
                </div>
            </div>

            <!-- Executive Summary Metric Strip -->
            <div class="grid grid-cols-4 gap-2 mb-3 text-center text-xs">
                <div class="p-2 rounded bg-slate-50 border border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Total Sanctioned Limit</span>
                    <span class="font-mono font-black text-slate-900 text-sm">₹ {{ number_format($data['totals']['total_sanctioned_limit'], 2) }}</span>
                </div>
                <div class="p-2 rounded bg-slate-50 border border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Actual Billed Expenditure</span>
                    <span class="font-mono font-black text-emerald-700 text-sm">₹ {{ number_format($data['totals']['total_actual_expenditure'], 2) }}</span>
                </div>
                <div class="p-2 rounded bg-slate-50 border border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Savings / Balance Limit</span>
                    <span class="font-mono font-black {{ $data['totals']['total_savings_balance'] >= 0 ? 'text-blue-700' : 'text-rose-700' }} text-sm">₹ {{ number_format($data['totals']['total_savings_balance'], 2) }}</span>
                </div>
                <div class="p-2 rounded bg-slate-50 border border-slate-200">
                    <span class="text-[10px] uppercase font-bold text-slate-500 block">Limits Exceeded / Compliance</span>
                    <span class="font-mono font-black {{ $data['totals']['total_violations_count'] > 0 ? 'text-rose-700' : 'text-emerald-700' }} text-sm">
                        {{ $data['totals']['total_violations_count'] }} Exceeded ({{ $data['totals']['compliance_rate'] }}%)
                    </span>
                </div>
            </div>

            <!-- Report Tables -->
            @if($reportMode === 'summary')
                <!-- Consolidated SOR Summary Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[11px] border border-slate-300">
                        <thead class="bg-slate-100 text-slate-800 uppercase font-bold text-[10px] border-b border-slate-300">
                            <tr>
                                <th class="p-1.5 text-center border-r border-slate-300 w-8">#</th>
                                <th class="p-1.5 border-r border-slate-300 w-16">SOR Code</th>
                                <th class="p-1.5 border-r border-slate-300">Activity / SOR Work Description</th>
                                <th class="p-1.5 text-center border-r border-slate-300 w-12">Unit</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-20">SOR Rate (₹)</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-20">Limit Qty</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-24">Sanctioned Limit (₹)</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-20">Exec Qty</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-20">Billed Rate (₹)</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-24">Billed Amount (₹)</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-24">Balance (₹)</th>
                                <th class="p-1.5 text-center border-r border-slate-300 w-16">Util %</th>
                                <th class="p-1.5 text-center w-24">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($data['summary_rows'] as $idx => $row)
                                <tr class="{{ $row['has_violation'] ? 'bg-rose-50/50' : ($idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/30') }}">
                                    <td class="p-1.5 text-center font-mono border-r border-slate-200">{{ $idx + 1 }}</td>
                                    <td class="p-1.5 font-mono font-bold text-emerald-900 border-r border-slate-200">{{ $row['sor_code'] }}</td>
                                    <td class="p-1.5 font-medium text-slate-900 border-r border-slate-200">{{ $row['work_description'] }}</td>
                                    <td class="p-1.5 text-center text-slate-700 border-r border-slate-200">{{ $row['unit'] }}</td>
                                    <td class="p-1.5 text-right font-mono border-r border-slate-200">{{ number_format($row['sanctioned_rate'], 2) }}</td>
                                    <td class="p-1.5 text-right font-mono border-r border-slate-200">{{ number_format($row['sanctioned_qty'], 0) }}</td>
                                    <td class="p-1.5 text-right font-mono font-bold border-r border-slate-200">{{ number_format($row['sanctioned_limit'], 2) }}</td>
                                    <td class="p-1.5 text-right font-mono border-r border-slate-200">{{ number_format($row['executed_qty'], 0) }}</td>
                                    <td class="p-1.5 text-right font-mono border-r border-slate-200">{{ number_format($row['actual_rate'], 2) }}</td>
                                    <td class="p-1.5 text-right font-mono font-bold text-emerald-900 border-r border-slate-200">{{ number_format($row['actual_amount'], 2) }}</td>
                                    <td class="p-1.5 text-right font-mono font-bold {{ $row['balance_amount'] >= 0 ? 'text-blue-900' : 'text-rose-700' }} border-r border-slate-200">{{ number_format($row['balance_amount'], 2) }}</td>
                                    <td class="p-1.5 text-center font-mono border-r border-slate-200">{{ $row['utilization_percent'] }}%</td>
                                    <td class="p-1.5 text-center font-bold text-[10px]">
                                        <span class="{{ $row['status'] === 'Exceeded Limit' ? 'text-rose-700' : ($row['status'] === 'Critical (90%+)' ? 'text-amber-700' : 'text-emerald-700') }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="p-4 text-center text-slate-500 font-semibold">No SOR summary records found for this criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-400">
                            <tr>
                                <td colspan="6" class="p-2 text-right uppercase text-[10px]">Grand Total (₹):</td>
                                <td class="p-2 text-right font-mono font-black text-slate-900">{{ number_format($data['totals']['total_sanctioned_limit'], 2) }}</td>
                                <td colspan="2" class="p-2 text-right uppercase text-[10px]">Total Billed:</td>
                                <td class="p-2 text-right font-mono font-black text-emerald-900">{{ number_format($data['totals']['total_actual_expenditure'], 2) }}</td>
                                <td class="p-2 text-right font-mono font-black {{ $data['totals']['total_savings_balance'] >= 0 ? 'text-blue-900' : 'text-rose-700' }}">{{ number_format($data['totals']['total_savings_balance'], 2) }}</td>
                                <td colspan="2" class="p-2 text-center text-[10px] text-slate-700">Verified By SOR Master</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <!-- Detailed / Violations Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-[11px] border border-slate-300">
                        <thead class="bg-slate-100 text-slate-800 uppercase font-bold text-[10px] border-b border-slate-300">
                            <tr>
                                <th class="p-1.5 text-center border-r border-slate-300 w-8">#</th>
                                <th class="p-1.5 border-r border-slate-300 w-20">Voucher No</th>
                                <th class="p-1.5 border-r border-slate-300 w-16">Date</th>
                                <th class="p-1.5 border-r border-slate-300 w-44">Party / Agency Name</th>
                                <th class="p-1.5 border-r border-slate-300 w-14">SOR</th>
                                <th class="p-1.5 border-r border-slate-300">Work Description</th>
                                <th class="p-1.5 text-center border-r border-slate-300 w-10">Unit</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-16">SOR Rate</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-16">Bill Rate</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-14">Sanct Qty</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-14">Bill Qty</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-20">Sanct Limit (₹)</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-20">Billed (₹)</th>
                                <th class="p-1.5 text-right border-r border-slate-300 w-20">Variance (₹)</th>
                                <th class="p-1.5 text-center w-20">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($data['detailed_rows'] as $idx => $row)
                                <tr class="{{ $row['is_violation'] ? 'bg-rose-50/50' : ($idx % 2 === 0 ? 'bg-white' : 'bg-slate-50/30') }}">
                                    <td class="p-1.5 text-center font-mono border-r border-slate-200">{{ $idx + 1 }}</td>
                                    <td class="p-1.5 font-mono font-bold text-slate-900 border-r border-slate-200 whitespace-nowrap">{{ $row['voucher_no'] }}</td>
                                    <td class="p-1.5 text-slate-700 border-r border-slate-200 whitespace-nowrap">{{ $row['date'] }}</td>
                                    <td class="p-1.5 font-medium text-slate-800 border-r border-slate-200">{{ $row['party_name'] }}</td>
                                    <td class="p-1.5 font-mono font-bold text-emerald-900 border-r border-slate-200">{{ $row['sor_code'] }}</td>
                                    <td class="p-1.5 text-slate-900 border-r border-slate-200">{{ $row['work_description'] }}</td>
                                    <td class="p-1.5 text-center text-slate-700 border-r border-slate-200">{{ $row['unit'] }}</td>
                                    <td class="p-1.5 text-right font-mono border-r border-slate-200">{{ number_format($row['sanctioned_rate'], 2) }}</td>
                                    <td class="p-1.5 text-right font-mono border-r border-slate-200 {{ $row['actual_rate'] > $row['sanctioned_rate'] ? 'text-rose-700 font-bold' : '' }}">{{ number_format($row['actual_rate'], 2) }}</td>
                                    <td class="p-1.5 text-right font-mono border-r border-slate-200">{{ number_format($row['sanctioned_qty'], 0) }}</td>
                                    <td class="p-1.5 text-right font-mono border-r border-slate-200">{{ number_format($row['actual_qty'], 0) }}</td>
                                    <td class="p-1.5 text-right font-mono font-bold border-r border-slate-200">{{ number_format($row['sanctioned_limit'], 2) }}</td>
                                    <td class="p-1.5 text-right font-mono font-bold text-emerald-900 border-r border-slate-200">{{ number_format($row['actual_amount'], 2) }}</td>
                                    <td class="p-1.5 text-right font-mono font-bold {{ $row['variance'] >= 0 ? 'text-blue-900' : 'text-rose-700' }} border-r border-slate-200">{{ number_format($row['variance'], 2) }}</td>
                                    <td class="p-1.5 text-center font-bold text-[10px]">
                                        <span class="{{ $row['is_violation'] ? 'text-rose-700 font-black' : ($row['status'] === 'Critical (90%+)' ? 'text-amber-700' : 'text-emerald-700') }}">
                                            {{ $row['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="p-4 text-center text-slate-500 font-semibold">
                                        {{ $reportMode === 'violations' ? 'No limit violations or rate exceedances detected.' : 'No detailed entries found for this criteria.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-slate-100 font-bold border-t-2 border-slate-400">
                            <tr>
                                <td colspan="11" class="p-2 text-right uppercase text-[10px]">Grand Total (₹):</td>
                                <td class="p-2 text-right font-mono font-black text-slate-900">{{ number_format($data['totals']['total_sanctioned_limit'], 2) }}</td>
                                <td class="p-2 text-right font-mono font-black text-emerald-900">{{ number_format($data['totals']['total_actual_expenditure'], 2) }}</td>
                                <td class="p-2 text-right font-mono font-black {{ $data['totals']['total_savings_balance'] >= 0 ? 'text-blue-900' : 'text-rose-700' }}">{{ number_format($data['totals']['total_savings_balance'], 2) }}</td>
                                <td class="p-2 text-center text-[10px] text-slate-700">{{ $data['totals']['total_items_count'] }} Items</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif

            <!-- Signatures Section (3-Tier Range Forest Officer Structure) -->
            <div class="signatures-block grid grid-cols-3 gap-6 pt-6 text-center text-xs font-bold text-slate-900">
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>વનરક્ષક / વનપાલ (Beat Guard / Forester)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['beat'] }} / {{ $data['round'] }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>એકાઉન્ટન્ટ / કેશિયર (Accountant / Cashier)</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['range_name'] }}</div>
                    </div>
                </div>
                <div class="px-2">
                    <div class="border-t-2 border-slate-800 pt-1.5">
                        <div>{{ $profile->officer_name ?: 'પરિક્ષેત્ર વન અધિકારી (Range Forest Officer)' }}</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $profile->officer_designation ?: ($data['range_name']) }}</div>
                    </div>
                </div>
            </div>

            <div class="text-right text-[9px] text-slate-400 pt-4">
                Generated: {{ $data['generated_at'] }} &bull; SFS Forest Accounting &amp; SOR Limit Verification Subsystem
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
