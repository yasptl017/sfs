<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order Print - {{ $month }} ({{ $docketNo ?: 'All Dockets' }}) | Forest Inventory</title>
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
            .work-order-sheet {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                padding: 1.25rem !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .page-break-always {
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
    </style>
</head>
<body class="bg-stone-100 text-slate-900 font-sans antialiased p-4 sm:p-6 print:p-0 print:bg-white">
    <div class="max-w-[210mm] mx-auto space-y-4 print:max-w-none print:m-0 print:space-y-0">
        
        <!-- Floating Print Control Bar (Hidden during printing) -->
        <div class="no-print flex items-center justify-between bg-white rounded-lg border border-emerald-200 p-3 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                    Mode: {{ $attachmentMode }}
                </span>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">
                    Month: <strong>{{ $month }}</strong> | Docket: <strong>{{ $docketNo ?: 'All Dockets' }}</strong> | Extend: <strong>{{ $extendDays }} days</strong>
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

        <!-- Work Orders List -->
        @forelse($data['work_orders'] ?? [] as $index => $wo)
            <div class="work-order-sheet bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm space-y-4 print:border-slate-800 print:shadow-none {{ $attachmentMode === 'Separate' && !$loop->last ? 'page-break-always mb-8' : 'mb-4' }}">
                
                <!-- Official Header with Dynamic Logo & Profile -->
                <div class="border-b-2 border-slate-900 pb-3">
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
                                કામગીરી મંજૂરી / કાર્ય હુકમ (WORK ORDER)
                            </h3>
                        </div>
                        <div class="w-16 text-right text-[10px] font-mono text-slate-500 flex-shrink-0">
                            <div>WORK ORDER</div>
                            <div class="font-bold text-slate-800">#{{ str_pad((string)$wo['serial_number'], 3, '0', STR_PAD_LEFT) }}</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-between items-center text-[11px] text-slate-700 pt-2 border-t border-slate-300 mt-2 font-semibold gap-2">
                        <span><strong>Work Order No:</strong> <span class="font-mono text-emerald-950 font-bold">{{ $wo['work_order_no'] }}</span></span>
                        <span><strong>Outward No:</strong> {{ $wo['outward_no'] }}</span>
                        <span><strong>Date of Issue:</strong> {{ $wo['date'] }}</span>
                    </div>
                </div>

                <!-- Recipient & Sanction Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs bg-slate-50 p-3 rounded border border-slate-200">
                    <div>
                        <p class="text-slate-500 font-bold uppercase text-[10px]">પ્રતિશ્રી (To Contractor / Agency):</p>
                        <p class="text-slate-950 font-bold text-xs mt-0.5">{{ $wo['party_name'] }}</p>
                        <p class="text-slate-600 text-[11px] mt-0.5">{{ $wo['party_address'] }}</p>
                        <p class="mt-1 text-slate-700 font-semibold text-[11px]">
                            Voucher Ref: <span class="font-mono text-slate-900 font-bold">{{ $wo['voucher_no'] }}</span> | 
                            Docket: <span class="font-mono text-slate-900">{{ $wo['docket_no'] }}</span>
                        </p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-slate-500 font-bold uppercase text-[10px]">મંજૂરી તેમજ સ્થળ વિગત (Sanction & Location):</p>
                        <p><strong>Budget Code:</strong> <span class="font-mono font-bold text-slate-900">{{ $wo['budget_code'] }}</span></p>
                        <p><strong>Scheme:</strong> {{ $wo['scheme'] }}</p>
                        <p><strong>Location / Beat:</strong> {{ $wo['place'] }} ({{ $wo['round'] }} - {{ $wo['beat'] }})</p>
                        <p class="pt-0.5">
                            <strong>Completion Period:</strong> {{ $wo['start_date'] }} to 
                            <span class="text-blue-900 font-bold bg-blue-100 px-1.5 py-0.5 rounded font-mono">{{ $wo['extended_end_date'] }}</span>
                            @if($wo['extend_days'] > 0)
                                <span class="text-[10px] text-slate-500 font-normal">({{ $wo['extend_days'] }} days ext.)</span>
                            @endif
                        </p>
                    </div>
                </div>

                <p class="text-xs text-slate-800 leading-relaxed">
                    આથી આપને જણાવવાનું કે ગુજરાત વન વિભાગ દ્વારા મંજૂર થયેલ યોજનાકીય અંદાજ મુજબ નીચે દર્શાવેલ વિગતો અને શરતો અનુસાર વન સંરક્ષણ / પ્લાન્ટેશન કામગીરી નિયત સમયમર્યાદામાં પૂર્ણ કરવા આથી કાર્ય હુકમ આપવામાં આવે છે:
                </p>

                <!-- Detailed Items Table -->
                <table class="w-full text-xs border border-slate-400 border-collapse mb-2">
                    <thead>
                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-400">
                            <th class="p-1.5 border border-slate-300 text-center w-8 whitespace-nowrap">Sr.</th>
                            <th class="p-1.5 border border-slate-300 text-left">Detailed Schedule of Work Operations</th>
                            <th class="p-1.5 border border-slate-300 text-left w-36">Plot / Compartment</th>
                            <th class="p-1.5 border border-slate-300 text-right w-20 whitespace-nowrap">Quantity</th>
                            <th class="p-1.5 border border-slate-300 text-right w-20 whitespace-nowrap">Rate (₹)</th>
                            <th class="p-1.5 border border-slate-300 text-right w-28 whitespace-nowrap">Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($wo['items'] as $item)
                            <tr class="border-b border-slate-200">
                                <td class="p-1.5 border border-slate-300 text-center font-bold whitespace-nowrap">{{ $item['sr_no'] }}</td>
                                <td class="p-1.5 border border-slate-300 font-medium text-slate-950">{{ $item['description'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-slate-700">{{ $item['plot_area'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-semibold whitespace-nowrap">{{ $item['quantity'] }} {{ $item['unit'] }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono whitespace-nowrap">₹ {{ number_format($item['rate'], 2) }}</td>
                                <td class="p-1.5 border border-slate-300 text-right font-mono font-bold whitespace-nowrap">₹ {{ number_format($item['amount'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-100 font-bold text-slate-950 border-t-2 border-slate-900">
                            <td colspan="5" class="p-1.5 border border-slate-300 text-right uppercase">Total Sanctioned Amount:</td>
                            <td class="p-1.5 border border-slate-300 text-right font-mono text-emerald-950 text-xs font-black whitespace-nowrap">
                                ₹ {{ number_format($wo['total_amount'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <p class="text-xs font-semibold text-slate-800">
                    <strong>Amount in words:</strong> <span class="italic text-emerald-950">{{ $wo['total_amount_words'] }}</span>
                </p>

                <!-- Terms & Signatures -->
                <div class="pt-2 border-t border-slate-200 text-[11px] text-slate-700 space-y-2">
                    <p class="font-bold text-slate-900">નિયમો અને શરતો (Terms &amp; Conditions):</p>
                    <ol class="list-decimal pl-5 space-y-0.5">
                        <li>ઉપરોક્ત દર્શાવેલ કામગીરી વન વિભાગના નિર્ધારિત ટેકનિકલ માપદંડ અને મંજૂર થયેલ દર મુજબ તારીખ <strong>{{ $wo['extended_end_date'] }}</strong> સુધીમાં પૂર્ણ કરવાની રહેશે.</li>
                        <li>કામગીરી દરમિયાન કોઈપણ પ્રકારનું વન્યજીવ કે વન સંપત્તિને નુકસાન ન થાય તે સુનિશ્ચિત કરવાની જવાબદારી સંબંધિત એજન્સીની રહેશે.</li>
                        <li>કામગીરી પૂર્ણ થયેથી સંબંધિત વનપાલ / વનરક્ષક દ્વારા સ્થળ ચકાસણી અને માપણી પુસ્તિકા (MB) માં નોંધ કરાવી વાઉચર રજૂ કરવાનું રહેશે.</li>
                    </ol>

                    <div class="signatures-block pt-6 flex justify-between items-end text-center font-bold text-slate-900 text-xs">
                        <div class="px-2">
                            <div class="border-t-2 border-slate-800 pt-1.5 w-44">
                                <div>એજન્સી / મંડળીની સહી</div>
                                <div class="text-[11px] font-semibold text-slate-700">(Contractor Signature)</div>
                            </div>
                        </div>
                        <div class="px-2">
                            <div class="border-t-2 border-slate-800 pt-1.5 w-52">
                                <div>{{ $profile->officer_name ?: 'પરિક્ષેત્ર વન અધિકારી (RFO)' }}</div>
                                <div class="text-[11px] font-semibold text-slate-700">{{ $profile->officer_designation ?: ($data['range_name']) }}</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        @empty
            <div class="bg-white rounded-xl border border-slate-300 p-12 text-center">
                <p class="text-slate-600 font-semibold">No work orders found for the selected criteria.</p>
            </div>
        @endforelse
    </div>
</body>
</html>
