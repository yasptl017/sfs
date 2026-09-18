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
            .monthly-sheet, .d36-section {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .d36-page-break {
                page-break-after: always !important;
                break-after: page !important;
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
                padding: 3px 5px !important;
            }
            .signatures-block {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                margin-top: 1.25rem !important;
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

        @if($reportType === 'form_53_abstract' && !empty($data['d36_sections']['demands']))
            <!-- ========================================== -->
            <!-- FORMAT 1: OFFICIAL D-36 FORM-53 ABSTRACT   -->
            <!-- ========================================== -->
            @foreach($data['d36_sections']['demands'] as $dIndex => $demand)
                <div class="d36-section bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none mb-6 {{ !$loop->last ? 'd36-page-break' : '' }}">
                    
                    <!-- Official D-36 Header Box -->
                    <div class="border-b-2 border-slate-900 pb-2.5 mb-3 space-y-1">
                        <div class="flex items-center justify-between gap-3">
                            <img src="{{ $data['logo_url'] ?? asset('images/gujarat-forest-logo.svg') }}" alt="Logo" class="h-12 w-12 object-contain shrink-0">
                            <div class="text-center flex-1 space-y-0.5">
                                <div class="flex justify-between items-center text-xs font-black text-slate-800">
                                    <span class="px-2 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-[11px]">D-36</span>
                                    <span class="uppercase tracking-wide text-xs sm:text-sm font-extrabold text-slate-900">ABSTRACT: {{ $demand['demand_title'] }}</span>
                                    <span class="text-[11px] font-mono font-semibold">{{ $month }}-{{ date('Y') }}</span>
                                </div>
                                <h2 class="text-xs sm:text-sm font-bold text-slate-800">
                                    Statement showing the abstract of expenditure figures for the month {{ $month }}-{{ date('Y') }}
                                </h2>
                                <h3 class="text-xs font-bold text-emerald-950">
                                    Office of {{ $data['officer_designation'] ?? 'Dy. Conservator of Forests' }}, {{ $data['division_name'] }}
                                </h3>
                                @if(!empty($data['address']))
                                    <p class="text-[10px] text-slate-600 font-medium">{{ $data['address'] }}</p>
                                @endif
                            </div>
                            <div class="w-12 shrink-0 hidden sm:block"></div>
                        </div>

                        <!-- D-36 Classification Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-0.5 text-left text-[11px] text-slate-800 pt-2 border-t border-slate-300 mt-2 font-medium">
                            <div><strong>Demand No.:</strong> {{ $demand['demand_no'] }}</div>
                            <div><strong>Major Head:</strong> {{ $demand['major_head'] }}</div>
                            <div><strong>Sector:</strong> {{ $demand['sector'] }}</div>
                            <div><strong>Sub Major Head:</strong> {{ $demand['sub_major_head'] }}</div>
                            <div><strong>Sub Sector:</strong> {{ $demand['sub_sector'] }}</div>
                            <div><strong>Payment Mode:</strong> {{ $paymentMode }}</div>
                        </div>
                    </div>

                    <!-- D-36 Master Abstract Table -->
                    <table class="w-full text-xs border border-slate-400 border-collapse mb-3">
                        <thead>
                            <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                                <th class="p-1 border border-slate-300 text-center w-10 whitespace-nowrap">Sr. No.</th>
                                <th class="p-1 border border-slate-300 text-left w-64">Sub Head</th>
                                <th class="p-1 border border-slate-300 text-left">Object Head</th>
                                <th class="p-1 border border-slate-300 text-right w-28 whitespace-nowrap">Up to the Last Month (₹)</th>
                                <th class="p-1 border border-slate-300 text-right w-28 whitespace-nowrap">During the Month (₹)</th>
                                <th class="p-1 border border-slate-300 text-right w-28 whitespace-nowrap font-bold text-emerald-950 bg-emerald-50">Progressive Total (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($demand['minor_heads'] as $minor)
                                <!-- Minor Head Banner -->
                                <tr class="bg-slate-50/80 font-bold text-slate-900 border-t border-b border-slate-300">
                                    <td colspan="3" class="p-1 border border-slate-300 font-semibold text-emerald-950 pl-2">
                                        {{ $minor['name'] }}
                                    </td>
                                    <td colspan="3" class="p-1 border border-slate-300 bg-slate-50/50"></td>
                                </tr>

                                @foreach($minor['sub_heads'] as $sub)
                                    @foreach($sub['objects'] as $oIdx => $obj)
                                        <tr class="border-b border-slate-200">
                                            @if($oIdx === 0)
                                                <td rowspan="{{ count($sub['objects']) }}" class="p-1 border border-slate-300 text-center font-bold align-top whitespace-nowrap">
                                                    {{ $sub['sr_no'] }}
                                                </td>
                                                <td rowspan="{{ count($sub['objects']) }}" class="p-1 border border-slate-300 font-semibold text-slate-900 align-top">
                                                    {{ $sub['name'] }}
                                                </td>
                                            @endif
                                            <td class="p-1 border border-slate-300 text-slate-800">
                                                {{ $obj['name'] }}
                                            </td>
                                            <td class="p-1 border border-slate-300 text-right font-mono font-medium whitespace-nowrap">
                                                {{ number_format($obj['last_month'], 2) }}
                                            </td>
                                            <td class="p-1 border border-slate-300 text-right font-mono font-medium whitespace-nowrap">
                                                {{ number_format($obj['during_month'], 2) }}
                                            </td>
                                            <td class="p-1 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/30 whitespace-nowrap">
                                                {{ number_format($obj['progressive'], 2) }}
                                            </td>
                                        </tr>
                                    @endforeach

                                    <!-- Sub-Head Subtotal -->
                                    <tr class="bg-slate-50/60 font-semibold text-slate-900 border-b border-slate-300 text-[11px]">
                                        <td colspan="3" class="p-1 border border-slate-300 text-right pr-2">
                                            <span class="font-bold">Total of Sub-Head: {{ $sub['name'] }}</span>
                                            <span class="block text-[10px] font-mono text-slate-600">{{ $sub['code'] }}</span>
                                        </td>
                                        <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">
                                            {{ number_format($sub['total_last_month'], 2) }}
                                        </td>
                                        <td class="p-1 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">
                                            {{ number_format($sub['total_during_month'], 2) }}
                                        </td>
                                        <td class="p-1 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">
                                            {{ number_format($sub['total_progressive'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Minor-Head Subtotal -->
                                <tr class="bg-slate-100 font-bold text-slate-950 border-b-2 border-slate-400 text-[11px]">
                                    <td colspan="3" class="p-1.5 border border-slate-300 text-right pr-2">
                                        <span>Total of {{ $minor['name'] }}</span>
                                        <span class="block text-[10px] font-mono text-slate-600">{{ $minor['code'] }}</span>
                                    </td>
                                    <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">
                                        {{ number_format($minor['total_last_month'], 2) }}
                                    </td>
                                    <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">
                                        {{ number_format($minor['total_during_month'], 2) }}
                                    </td>
                                    <td class="p-1.5 border border-slate-300 text-right font-mono font-black text-emerald-950 bg-emerald-50/60 whitespace-nowrap">
                                        {{ number_format($minor['total_progressive'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-200 font-bold text-slate-950 border-t-2 border-slate-900 text-xs">
                                <td colspan="3" class="p-1.5 border border-slate-300 text-right uppercase">
                                    Total of {{ $demand['demand_title'] }}:
                                </td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-black whitespace-nowrap">
                                    {{ number_format($demand['total_last_month'], 2) }}
                                </td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-black whitespace-nowrap">
                                    {{ number_format($demand['total_during_month'], 2) }}
                                </td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-black text-emerald-950 bg-emerald-100/60 whitespace-nowrap">
                                    {{ number_format($demand['total_progressive'], 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- D-36 Footer & Official Signature -->
                    <div class="signatures-block flex justify-between items-end pt-4 text-xs font-bold text-slate-900">
                        <div class="text-[11px] text-slate-700">
                            <strong>Date:</strong> {{ date('d/m/Y') }}
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
            @endforeach

        @elseif($reportType === 'epayment_report')
            <!-- ========================================== -->
            <!-- FORMAT: E-PAYMENT FORM NO. 63 SCHEDULE     -->
            <!-- ========================================== -->
            <div class="monthly-sheet bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none">
                <div class="border-b-2 border-slate-900 pb-2 mb-3 text-center space-y-0.5">
                    <h1 class="text-sm font-black text-slate-950 uppercase">{{ $data['division_name'] }} (D-36)</h1>
                    <h2 class="text-xs sm:text-sm font-extrabold text-slate-900">Form: 63 : Schedule of E-Payment during the Month of {{ $data['month'] }}-{{ date('Y') }}</h2>
                    <h3 class="text-[11px] font-mono font-bold text-emerald-950">Head: 8782 00 103 12 Forest Cheques Issued (New- E-Payment)</h3>
                </div>

                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1 border border-slate-300 text-center w-8">Sr.</th>
                            <th class="p-1 border border-slate-300 text-left w-36">Head</th>
                            <th class="p-1 border border-slate-300 text-center w-14">Bill No</th>
                            <th class="p-1 border border-slate-300 text-center w-16">Treasury Vr</th>
                            <th class="p-1 border border-slate-300 text-center w-20">Approval Date</th>
                            <th class="p-1 border border-slate-300 text-left w-28">ePayment Code</th>
                            <th class="p-1 border border-slate-300 text-right w-24">Payable Total</th>
                            <th class="p-1 border border-slate-300 text-right w-24 font-bold text-emerald-950 bg-emerald-50">Net Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['epayment_items'] as $ep)
                            <tr class="border-b border-slate-200">
                                <td class="p-1 border border-slate-300 text-center font-bold">{{ $ep['sr_no'] }}</td>
                                <td class="p-1 border border-slate-300 font-mono text-[11px]">{{ $ep['head'] }}</td>
                                <td class="p-1 border border-slate-300 text-center font-mono">{{ $ep['bill_no'] }}</td>
                                <td class="p-1 border border-slate-300 text-center font-mono font-bold text-blue-900">{{ $ep['treasury_vr_no'] }}</td>
                                <td class="p-1 border border-slate-300 text-center font-mono">{{ $ep['approval_date'] }}</td>
                                <td class="p-1 border border-slate-300 font-mono font-bold text-slate-800">{{ $ep['epayment_code'] }}</td>
                                <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($ep['payable_total'], 2) }}</td>
                                <td class="p-1 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">₹ {{ number_format($ep['net_amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                            <td colspan="6" class="p-1.5 border border-slate-300 text-right uppercase">Total E-Payment Amount:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format(array_sum(array_column($data['epayment_items'], 'payable_total')), 2) }}</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-black text-emerald-950 whitespace-nowrap">₹ {{ number_format(array_sum(array_column($data['epayment_items'], 'net_amount')), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="signatures-block flex justify-between items-end pt-4 text-xs font-bold text-slate-900">
                    <div class="text-[11px] text-slate-700"><strong>Date:</strong> {{ date('d/m/Y') }}</div>
                    <div class="text-center px-4">
                        <div class="border-t-2 border-slate-800 pt-1 w-56">
                            <div>Dy. Conservator of Forests</div>
                            <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($reportType === 'challan_report')
            <!-- ========================================== -->
            <!-- FORMAT: 0406 TREASURY REMITTANCE CHALLAN   -->
            <!-- ========================================== -->
            <div class="monthly-sheet bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm print:border-none print:shadow-none print:p-0 print:rounded-none">
                <div class="border-b-2 border-slate-900 pb-2 mb-3 text-center space-y-0.5">
                    <h1 class="text-sm font-black text-slate-950 uppercase">{{ $data['division_name'] }} (D-36)</h1>
                    <h2 class="text-xs sm:text-sm font-extrabold text-slate-900">Schedule of 0406 01 800 05 Remittance Remitted into Treasury</h2>
                    <h3 class="text-[11px] font-mono font-bold text-emerald-950">For the Month of - {{ $data['month'] }}-{{ date('Y') }}</h3>
                </div>

                <table class="w-full text-xs border border-slate-400 border-collapse mb-4">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-8">Sr.</th>
                            <th class="p-1.5 border border-slate-300 text-center w-24">Date</th>
                            <th class="p-1.5 border border-slate-300 text-center w-24">Challan No</th>
                            <th class="p-1.5 border border-slate-300 text-left w-48">Treasury / Bank</th>
                            <th class="p-1.5 border border-slate-300 text-left">Details of Recovery</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 font-bold text-emerald-950 bg-emerald-50">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['challan_items'] as $ch)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold">{{ $ch['sr_no'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono">{{ $ch['date'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-center font-mono font-bold text-blue-900">{{ $ch['challan_no'] }}</td>
                                <td class="p-1.5 border border-slate-300 font-medium text-slate-900">{{ $ch['treasury'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-slate-800">{{ $ch['recovery_details'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40 whitespace-nowrap">₹ {{ number_format($ch['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                            <td colspan="5" class="p-1.5 border border-slate-300 text-right uppercase">Total Remittance:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono font-black text-emerald-950 whitespace-nowrap">₹ {{ number_format(array_sum(array_column($data['challan_items'], 'amount')), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="signatures-block flex justify-between items-end pt-4 text-xs font-bold text-slate-900">
                    <div class="text-[11px] text-slate-700"><strong>Date:</strong> {{ date('d/m/Y') }}</div>
                    <div class="text-center px-4">
                        <div class="border-t-2 border-slate-800 pt-1 w-56">
                            <div>Dy. Conservator of Forests</div>
                            <div class="text-[11px] font-semibold text-slate-700">{{ $data['division_name'] }}</div>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($reportType === 'pay_slips')
            <!-- ========================================== -->
            <!-- FORMAT: MONTHLY PAY / WAGE DISBURSEMENT SLIPS -->
            <!-- ========================================== -->
            <div class="space-y-4">
                <div class="text-center pb-2 border-b border-slate-300 no-print">
                    <h2 class="text-base font-bold text-slate-900">Monthly Wage Disbursement Slips - {{ $data['month'] }}-{{ date('Y') }}</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($data['pay_slips_list'] as $slip)
                        <div class="border-2 border-slate-800 rounded-lg p-4 bg-white space-y-2 text-xs">
                            <div class="border-b border-slate-300 pb-1 text-center">
                                <h3 class="font-bold text-slate-950 uppercase">{{ $data['division_name'] }}</h3>
                                <div class="text-[11px] font-semibold text-emerald-950">Daily Wager Payment Slip ({{ $slip['month'] }})</div>
                            </div>
                            <div class="grid grid-cols-2 gap-1 text-[11px]">
                                <div><strong>Name:</strong> {{ $slip['employee_name'] }}</div>
                                <div><strong>Range:</strong> {{ $slip['range'] }}</div>
                                <div><strong>Days Worked:</strong> {{ $slip['days_worked'] }} days</div>
                                <div><strong>Daily Rate:</strong> ₹ {{ number_format($slip['daily_rate'], 2) }}</div>
                                <div><strong>Gross Wage:</strong> <span class="font-mono font-bold">₹ {{ number_format($slip['gross_wage'], 2) }}</span></div>
                                <div><strong>Voucher Ref:</strong> {{ $slip['voucher_no'] }}</div>
                            </div>
                            <div class="border-t border-b border-slate-200 py-1 grid grid-cols-3 gap-1 text-[10px] text-slate-700">
                                <div>GPF: ₹ {{ number_format($slip['gpf'], 2) }}</div>
                                <div>NPS: ₹ {{ number_format($slip['nps'], 2) }}</div>
                                <div>PT: ₹ {{ number_format($slip['pt'], 2) }}</div>
                            </div>
                            <div class="flex justify-between items-center pt-1 font-bold text-xs">
                                <div>Total Deductions: <span class="text-rose-700 font-mono">₹ {{ number_format($slip['total_deductions'], 2) }}</span></div>
                                <div class="text-emerald-950">Net Paid: <span class="font-mono text-sm">₹ {{ number_format($slip['net_payable'], 2) }}</span></div>
                            </div>
                            <div class="flex justify-between items-end pt-3 text-[10px] text-slate-600 font-semibold">
                                <div>Signature of Payee</div>
                                <div>Range Forest Officer</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        @else
            <!-- ========================================== -->
            <!-- FORMAT: STANDARD MASTER MONTHLY REPORT     -->
            <!-- ========================================== -->
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

                @if(in_array($reportType, ['deduction_reports', 'deduction_reports_yearly']))
                    <!-- Itemized Statutory Deductions Register (TDS / Labour Cess / PT) -->
                    <div class="space-y-4 mb-4">
                        <div class="border-b border-slate-300 pb-1">
                            <h4 class="text-xs font-bold text-slate-900 uppercase">1. Schedule of Income Tax (T.D.S.) - Head 8658 00 112 00</h4>
                        </div>
                        <table class="w-full text-xs border border-slate-300 border-collapse mb-3">
                            <thead>
                                <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-300">
                                    <th class="p-1 border border-slate-300 text-center w-8">Sr.</th>
                                    <th class="p-1 border border-slate-300 text-left">Party Name &amp; City</th>
                                    <th class="p-1 border border-slate-300 text-center w-28">PAN Number</th>
                                    <th class="p-1 border border-slate-300 text-right w-28">Taxable Amount (₹)</th>
                                    <th class="p-1 border border-slate-300 text-right w-28 font-bold text-rose-800">IT TDS Recovered (₹)</th>
                                    <th class="p-1 border border-slate-300 text-left w-36">Voucher Numbers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['tds_items'] as $tds)
                                    <tr class="border-b border-slate-200">
                                        <td class="p-1 border border-slate-300 text-center font-bold">{{ $tds['sr_no'] }}</td>
                                        <td class="p-1 border border-slate-300 font-medium">{{ $tds['party_name'] }} ({{ $tds['resident'] }})</td>
                                        <td class="p-1 border border-slate-300 text-center font-mono font-bold">{{ $tds['pan'] }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($tds['taxable_amount'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono font-bold text-rose-800 whitespace-nowrap">₹ {{ number_format($tds['tds_amount'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-[10px] text-slate-600">{{ $tds['voucher_no'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="border-b border-slate-300 pb-1 pt-2">
                            <h4 class="text-xs font-bold text-slate-900 uppercase">2. Schedule of 1% Labour Welfare Cess - Head 0230</h4>
                        </div>
                        <table class="w-full text-xs border border-slate-300 border-collapse mb-3">
                            <thead>
                                <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-300">
                                    <th class="p-1 border border-slate-300 text-center w-8">Sr.</th>
                                    <th class="p-1 border border-slate-300 text-left">Party Name &amp; City</th>
                                    <th class="p-1 border border-slate-300 text-center w-28">PAN Number</th>
                                    <th class="p-1 border border-slate-300 text-right w-28">Taxable Amount (₹)</th>
                                    <th class="p-1 border border-slate-300 text-right w-28 font-bold text-rose-800">Labour Cess (₹)</th>
                                    <th class="p-1 border border-slate-300 text-left w-36">Voucher Numbers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['labour_cess_items'] as $lc)
                                    <tr class="border-b border-slate-200">
                                        <td class="p-1 border border-slate-300 text-center font-bold">{{ $lc['sr_no'] }}</td>
                                        <td class="p-1 border border-slate-300 font-medium">{{ $lc['party_name'] }} ({{ $lc['resident'] }})</td>
                                        <td class="p-1 border border-slate-300 text-center font-mono font-bold">{{ $lc['pan'] }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($lc['taxable_amount'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-right font-mono font-bold text-rose-800 whitespace-nowrap">₹ {{ number_format($lc['cess_amount'], 2) }}</td>
                                        <td class="p-1 border border-slate-300 text-[10px] text-slate-600">{{ $lc['voucher_no'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <!-- Master Bills Table -->
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
                @endif

                <!-- Official Signatures -->
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
        @endif

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
