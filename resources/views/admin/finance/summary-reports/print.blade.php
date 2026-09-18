<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $data['report_title'] }} - {{ $month }} | Forest Inventory</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                font-size: 10px !important;
            }
            .no-print {
                display: none !important;
            }
            .page-sheet {
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
                padding: 3px 4px !important;
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
        
        <!-- Print Control Bar -->
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

        <div class="page-sheet bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none">
            <!-- Official Header -->
            <div class="border-b-2 border-slate-900 pb-2.5 mb-4 space-y-1">
                <div class="flex items-center justify-between gap-3">
                    <img src="{{ $data['logo_url'] ?? asset('images/gujarat-forest-logo.svg') }}" alt="Logo" class="h-12 w-12 object-contain shrink-0">
                    <div class="text-center flex-1 space-y-0.5">
                        <h1 class="text-xs sm:text-sm font-black text-slate-950 uppercase tracking-widest">
                            GUJARAT STATE FOREST DEPARTMENT
                        </h1>
                        <h2 class="text-sm sm:text-base font-black text-slate-900 uppercase tracking-wide">
                            {{ $data['division_name'] }}
                        </h2>
                        <h3 class="text-xs sm:text-sm font-bold text-emerald-950 uppercase">
                            {{ $data['report_title'] }}
                        </h3>
                        @if(!empty($data['address']))
                            <p class="text-[10px] text-slate-600 font-medium">{{ $data['address'] }}</p>
                        @endif
                    </div>
                    <div class="w-12 shrink-0 hidden sm:block"></div>
                </div>
                <div class="flex justify-between items-center text-[10px] sm:text-xs text-slate-700 pt-1 border-t border-slate-200 mt-1 font-semibold">
                    <span><strong>Month:</strong> {{ $data['month'] }}-{{ date('Y') }}</span>
                    <span><strong>Payment Mode:</strong> {{ $data['payment_mode'] }}</span>
                    <span><strong>Date:</strong> {{ date('d/m/Y') }}</span>
                </div>
            </div>

            @if($reportType === 'summary_abstract')
                <!-- 10-Column Abstract of Summary Table -->
                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1 border border-slate-300 text-left">Scheme</th>
                            <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Sanctioned Grant</th>
                            <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Released Grant</th>
                            <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Expenditure upto last month</th>
                            <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Expenditure of current month</th>
                            <th class="p-1 border border-slate-300 text-right whitespace-nowrap bg-emerald-50 text-emerald-950 font-bold">Total Expenditure</th>
                            <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Remaining Requirement</th>
                            <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Total Requirement</th>
                            <th class="p-1 border border-slate-300 text-center whitespace-nowrap">% of Sanc.</th>
                            <th class="p-1 border border-slate-300 text-center whitespace-nowrap">% of Rel.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['sections'] as $sec)
                            @foreach($sec['schemes'] as $sch)
                                <tr class="border-b border-slate-200">
                                    <td class="p-1 border border-slate-300 font-medium text-slate-900">{{ $sch['name'] }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($sch['sanctioned'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($sch['released'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($sch['last_month'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($sch['current_month'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">{{ number_format($sch['total_exp'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($sch['rem_req'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($sch['total_req'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-center font-mono font-semibold">{{ $sch['pct_sanctioned'] }}</td>
                                    <td class="p-1 border border-slate-300 text-center font-mono font-semibold">{{ $sch['pct_released'] }}</td>
                                </tr>
                            @endforeach

                            <!-- Demand Subtotal -->
                            <tr class="bg-slate-100 font-bold text-slate-950 border-b-2 border-slate-400 text-xs">
                                <td class="p-1 border border-slate-300 text-right uppercase">{{ $sec['demand_key'] }} Total:</td>
                                <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($sec['subtotal']['sanctioned'], 2) }}</td>
                                <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($sec['subtotal']['released'], 2) }}</td>
                                <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($sec['subtotal']['last_month'], 2) }}</td>
                                <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($sec['subtotal']['current_month'], 2) }}</td>
                                <td class="p-1 border border-slate-300 text-right font-mono font-black text-emerald-950 bg-emerald-100/50 whitespace-nowrap">{{ number_format($sec['subtotal']['total_exp'], 2) }}</td>
                                <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($sec['subtotal']['rem_req'], 2) }}</td>
                                <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($sec['subtotal']['total_req'], 2) }}</td>
                                <td class="p-1 border border-slate-300 text-center font-mono font-bold">{{ $sec['subtotal']['pct_sanctioned'] }}</td>
                                <td class="p-1 border border-slate-300 text-center font-mono font-bold">{{ $sec['subtotal']['pct_released'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-emerald-100/80 font-black text-slate-950 border-t-2 border-slate-900 text-xs">
                            <td class="p-1.5 border border-slate-300 text-right uppercase">Grand Total:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($data['grand_totals']['sanctioned'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($data['grand_totals']['released'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($data['grand_totals']['last_month'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($data['grand_totals']['current_month'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono text-emerald-950 whitespace-nowrap">{{ number_format($data['grand_totals']['total_exp'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($data['grand_totals']['rem_req'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($data['grand_totals']['total_req'], 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-center font-mono">{{ $data['grand_totals']['pct_sanctioned'] }}</td>
                            <td class="p-1.5 border border-slate-300 text-center font-mono">{{ $data['grand_totals']['pct_released'] }}</td>
                        </tr>
                    </tfoot>
                </table>

            @else
                <!-- Detailed Summary Groups (Merged with Salary or Only LC) -->
                @foreach($data['groups'] as $g)
                    <div class="mb-4">
                        <div class="bg-slate-100 p-1.5 border border-slate-300 font-bold text-emerald-950 text-xs mb-1">
                            Operating Head: {{ $g['operating_head'] }}
                        </div>
                        <table class="w-full text-xs border border-slate-300 border-collapse mb-2">
                            <thead>
                                <tr class="bg-slate-50 text-slate-800 font-bold border-b border-slate-300">
                                    <th class="p-1 border border-slate-300 text-center w-8">Sr.</th>
                                    <th class="p-1 border border-slate-300 text-left">Item</th>
                                    <th class="p-1 border border-slate-300 text-left">Object Class</th>
                                    <th class="p-1 border border-slate-300 text-left">Model / Sub Head</th>
                                    <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Allotment</th>
                                    <th class="p-1 border border-slate-300 text-right whitespace-nowrap">P_Exp</th>
                                    <th class="p-1 border border-slate-300 text-right whitespace-nowrap">C_Exp</th>
                                    <th class="p-1 border border-slate-300 text-right whitespace-nowrap bg-emerald-50 text-emerald-950">Total_Exp</th>
                                    <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Total Requirement</th>
                                    <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Change In Req</th>
                                    <th class="p-1 border border-slate-300 text-right whitespace-nowrap">Remaining Req</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($g['items'] as $it)
                                    <tr class="border-b border-slate-200">
                                        <td class="p-1 border border-slate-300 text-center font-bold">{{ $it['sr_no'] }}</td>
                                        <td class="p-1 border border-slate-300 font-semibold">{{ $it['item'] }}</td>
                                        <td class="p-1 border border-slate-300">{{ $it['obj_class'] }}</td>
                                        <td class="p-1 border border-slate-300 font-medium">{{ $it['model'] }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($it['allotment'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($it['p_exp'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($it['c_exp'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">{{ number_format($it['total_exp'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($it['tot_req'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($it['req_change'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">{{ number_format($it['rem_req'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-slate-100 font-bold text-slate-950">
                                    <td colspan="4" class="p-1 border border-slate-300 text-right uppercase">Total:</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($g['totals']['allotment'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($g['totals']['p_exp'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($g['totals']['c_exp'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono font-black text-emerald-950 whitespace-nowrap">{{ number_format($g['totals']['total_exp'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($g['totals']['tot_req'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($g['totals']['req_change'], 2) }}</td>
                                    <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">{{ number_format($g['totals']['rem_req'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @endif

            <!-- Signatures Section -->
            <div class="signatures-block flex justify-between items-end pt-6 text-xs font-bold text-slate-900">
                <div class="text-center px-4">
                    <div class="border-t-2 border-slate-800 pt-1 w-56">
                        <div>મુખ્ય હિસાબનીશ</div>
                        <div class="text-[11px] font-medium text-slate-600">Chief Accountant</div>
                    </div>
                </div>
                <div class="text-center px-4">
                    <div class="border-t-2 border-slate-800 pt-1 w-64">
                        @if(!empty($data['officer_name']))
                            <div class="font-bold text-slate-950">{{ $data['officer_name'] }}</div>
                        @endif
                        <div>{{ $data['officer_designation_gujarati'] ?? 'નાયબ વન સંરક્ષક' }}</div>
                        <div class="text-[11px] font-medium text-slate-600">{{ $data['officer_designation'] ?? 'Dy. Conservator of Forests' }}</div>
                        <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] }}</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
