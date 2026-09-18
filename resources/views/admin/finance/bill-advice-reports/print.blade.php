<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $report->report_type_label }} | Bill Register: {{ $report->bill_register_no }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            body { background: white; color: black; }
            .no-print { display: none !important; }
            .printable-card { box-shadow: none !important; border: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-stone-100 text-slate-900 font-sans antialiased p-4 sm:p-8">
    <div class="max-w-5xl mx-auto space-y-4">
        <!-- Floating Print Actions -->
        <div class="no-print flex items-center justify-between bg-white rounded-lg border border-emerald-200 p-4 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-emerald-100 text-emerald-800">
                    {{ $report->report_type_label }}
                </span>
                <span class="text-sm font-semibold text-slate-800">Bill: {{ $report->bill_register_no }} | Advice: {{ $report->advice_no }}</span>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="window.print()" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800">
                    Print / Save as PDF
                </button>
                <button onclick="window.close()" class="secondary-button text-xs py-1.5 px-3">
                    Close Window
                </button>
            </div>
        </div>

        <!-- Printable Document Container -->
        <div class="printable-card bg-white rounded-xl border border-slate-200 p-8 shadow-sm">
            <!-- Header -->
            <div class="border-b-2 border-slate-900 pb-4 mb-6 text-center space-y-1">
                <h1 class="text-xl font-extrabold text-slate-950 uppercase tracking-wide">GUJARAT FOREST DEPARTMENT</h1>
                <h2 class="text-base font-bold text-slate-800">{{ $data['division_name'] ?? 'Forest Division Office, Gujarat State' }}</h2>
                <h3 class="text-sm font-semibold text-emerald-800 uppercase tracking-wider">{{ $report->report_type_label }}</h3>
                <div class="flex flex-wrap justify-between items-center text-xs text-slate-600 pt-2 font-medium">
                    <span><strong>Bill Register No:</strong> {{ $report->bill_register_no }}</span>
                    <span><strong>Advice No:</strong> {{ $report->advice_no }}</span>
                    <span><strong>GST Challan No:</strong> {{ $data['gst_challan_no'] ?? '-' }}</span>
                    <span><strong>Date:</strong> {{ $report->created_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>

            <!-- Tharav Section (if present) -->
            @if(!empty($data['tharav_descriptions']))
                <div class="mb-6 p-4 rounded-lg border border-emerald-200 bg-emerald-50/40 text-xs">
                    <h4 class="font-bold text-emerald-950 uppercase tracking-wider mb-2">
                        ઠરાવ / વહીવટી મંજૂરી સંદર્ભ (Government Resolution References):
                    </h4>
                    <ol class="list-decimal list-inside space-y-1 text-slate-800 font-medium leading-relaxed">
                        @foreach($data['tharav_descriptions'] as $t)
                            <li>{{ $t }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif

            <!-- Main Data Table -->
            <table class="w-full text-xs border border-slate-300 border-collapse mb-8">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 font-bold border-b border-slate-300">
                        <th class="p-2 border border-slate-300 text-center w-10">Sr.</th>
                        <th class="p-2 border border-slate-300 text-left">Range &amp; Budget Head</th>
                        <th class="p-2 border border-slate-300 text-left">Party Details</th>
                        <th class="p-2 border border-slate-300 text-left">Description</th>
                        <th class="p-2 border border-slate-300 text-right">Gross (₹)</th>
                        <th class="p-2 border border-slate-300 text-right">Deductions (₹)</th>
                        <th class="p-2 border border-slate-300 text-right font-bold text-emerald-950">Net Payable (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['entries'] ?? [] as $index => $entry)
                        <tr class="border-b border-slate-200">
                            <td class="p-2 border border-slate-300 text-center font-bold">{{ $index + 1 }}</td>
                            <td class="p-2 border border-slate-300">
                                <span class="font-bold text-slate-900 block">{{ $entry['range_name'] }}</span>
                                <span class="text-[11px] text-slate-600 block">{{ $entry['budget_code'] }}</span>
                            </td>
                            <td class="p-2 border border-slate-300">
                                <span class="font-bold text-slate-950 block">{{ $entry['party_name'] }}</span>
                                <span class="text-[11px] text-slate-500 font-mono block">{{ $entry['party_gst_no'] }}</span>
                            </td>
                            <td class="p-2 border border-slate-300 text-slate-800 leading-relaxed">{{ $entry['description'] }}</td>
                            <td class="p-2 border border-slate-300 text-right font-medium">₹ {{ number_format($entry['gross_amount'], 2) }}</td>
                            <td class="p-2 border border-slate-300 text-right text-rose-700">₹ {{ number_format($entry['total_deductions'], 2) }}</td>
                            <td class="p-2 border border-slate-300 text-right font-bold text-emerald-800">₹ {{ number_format($entry['net_amount'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                        <td colspan="4" class="p-2 border border-slate-300 text-right uppercase">Total Amount:</td>
                        <td class="p-2 border border-slate-300 text-right font-bold">₹ {{ number_format($data['totals']['gross_amount'] ?? 0, 2) }}</td>
                        <td class="p-2 border border-slate-300 text-right font-bold text-rose-800">₹ {{ number_format($data['totals']['total_deductions'] ?? 0, 2) }}</td>
                        <td class="p-2 border border-slate-300 text-right font-bold text-emerald-950">₹ {{ number_format($data['totals']['net_amount'] ?? 0, 2) }}</td>
                    </tr>
                </tfoot>
            </table>

            <!-- Official Signatures -->
            <div class="grid grid-cols-3 gap-4 pt-16 text-center text-xs font-bold text-slate-900">
                <div>
                    <div class="border-t border-slate-400 pt-2">Prepared By (Senior Clerk / Accountant)</div>
                </div>
                <div>
                    <div class="border-t border-slate-400 pt-2">Verified By (Account Officer)</div>
                </div>
                <div>
                    <div class="border-t border-slate-400 pt-2">Approved By (Deputy Conservator of Forests)</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
