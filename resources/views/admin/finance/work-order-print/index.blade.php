<x-layouts.admin title="Work Order Print | Forest Inventory" heading="Work Order Print" subheading="Range Finance System - Generate and Print Official Forestry Work Orders">
    <style>
        .maroon-border-field {
            border: 2px solid #5c0606 !important;
            border-radius: 0.375rem !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            padding: 0.55rem 0.75rem !important;
            font-size: 0.925rem !important;
            font-weight: 600 !important;
            transition: all 0.15s ease-in-out !important;
            width: 100% !important;
        }
        .maroon-border-field:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.25) !important;
            outline: none !important;
        }
        .maroon-select {
            appearance: none !important;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23334155' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e") !important;
            background-position: right 0.75rem center !important;
            background-repeat: no-repeat !important;
            background-size: 1.25em 1.25em !important;
            padding-right: 2.25rem !important;
        }
        .blue-border-field {
            border: 2px solid #90bdf8 !important;
            border-radius: 0.375rem !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            padding: 0.55rem 0.75rem !important;
            font-size: 0.925rem !important;
            font-weight: 600 !important;
            transition: all 0.15s ease-in-out !important;
            width: 100% !important;
        }
        .blue-border-field:focus {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.25) !important;
            outline: none !important;
        }
        .toggle-switch-checkbox:checked + .toggle-switch-track {
            background-color: #1877f2 !important;
        }
        .toggle-switch-checkbox:checked + .toggle-switch-track .toggle-switch-thumb {
            transform: translateX(1.25rem) !important;
        }
    </style>

    <div class="space-y-6" id="workOrderPrintModule"
        data-dockets-url="{{ route('finance.work-order-print.dockets') }}"
        data-preview-url="{{ route('finance.work-order-print.preview') }}"
        data-print-url="{{ route('finance.work-order-print.print') }}">

        @if(session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-lg font-bold">&times;</button>
            </div>
        @endif

        <div id="workOrderAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Main "Work Order Print:" Form Card (Exact Match to Reference Image) -->
        <section class="max-w-xs sm:max-w-sm mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-lg overflow-hidden transition-all">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 bg-slate-50/80 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-800 tracking-tight">
                        Work Order Print:
                    </h2>
                    <button type="button" id="closeWorkOrderFormBtn" title="Close" class="text-slate-500 hover:text-slate-800 text-xl font-bold transition-colors focus:outline-none leading-none">
                        ✕
                    </button>
                </div>

                <!-- Form Body -->
                <form id="workOrderPrintForm" method="GET" action="{{ route('finance.work-order-print.print') }}" target="_blank" class="p-5 sm:p-6 space-y-4">
                    
                    <!-- 1. Month -->
                    <div>
                        <label for="month" class="block text-sm font-semibold text-slate-800 mb-1.5">
                            Month
                        </label>
                        <select id="month" name="month" required class="maroon-border-field maroon-select cursor-pointer">
                            @foreach($months as $m)
                                <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ $m }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Party wise separate attachments ? -->
                    <div>
                        <label for="attachment_mode" class="block text-sm font-semibold text-slate-800 mb-1.5">
                            Party wise separate attachments ?
                        </label>
                        <select id="attachment_mode" name="attachment_mode" class="blue-border-field maroon-select cursor-pointer">
                            <option value="Separate" @selected($attachmentMode === 'Separate')>Separate</option>
                            <option value="Combined" @selected($attachmentMode === 'Combined')>Combined</option>
                        </select>
                    </div>

                    <!-- 3. No. of days to extend the work end date by -->
                    <div>
                        <label for="extend_days" class="block text-sm font-semibold text-slate-800 mb-1.5">
                            No. of days to extend the work end date by
                        </label>
                        <input type="number" id="extend_days" name="extend_days" min="0" max="365"
                            value="{{ $extendDays }}"
                            class="maroon-border-field">
                    </div>

                    <!-- 4. Print all dockets of the month ? (Toggle) -->
                    <div class="pt-1 flex items-center justify-between">
                        <label for="print_all_dockets" class="text-sm font-semibold text-slate-800 cursor-pointer select-none">
                            Print all dockets of the month ?
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="print_all_dockets" name="print_all_dockets" value="1"
                                class="sr-only toggle-switch-checkbox" @checked($printAllDockets)>
                            <div class="toggle-switch-track w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer transition-colors">
                                <div class="toggle-switch-thumb w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-200 ease-in-out mt-0.5 ml-0.5"></div>
                            </div>
                        </label>
                    </div>

                    <!-- 5. Docket No. -->
                    <div id="docketInputContainer" class="{{ $printAllDockets ? 'opacity-50 pointer-events-none' : '' }}">
                        <label for="docket_no" class="block text-sm font-semibold text-slate-800 mb-1.5">
                            Docket No.
                        </label>
                        <input type="text" id="docket_no" name="docket_no"
                            value="{{ $docketNo }}"
                            placeholder=""
                            class="maroon-border-field">
                    </div>

                    <!-- 6. Print Button -->
                    <div class="pt-1">
                        <button type="submit" id="btnPrintSubmit"
                            class="inline-flex items-center justify-center rounded-md bg-[#1877f2] hover:bg-blue-600 text-white font-bold py-2.5 px-6 text-sm shadow-sm transition-all active:scale-95 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            Print
                        </button>
                    </div>

                    <!-- 7. Footer Notice -->
                    <div class="pt-2 text-xs text-slate-600 leading-relaxed">
                        These work order reports are prepared from voucher entry.
                    </div>
                </form>
            </div>
        </section>

        <!-- Section 2: Online Live Work Order Preview Section -->
        <section id="workOrderPreviewSection" class="hidden rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-4 bg-emerald-50/40 gap-3">
                <div class="flex items-center gap-3">
                    <span id="previewMonthBadge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Aug Work Orders
                    </span>
                    <h3 id="previewTitle" class="text-base font-semibold text-slate-950">Work Order Preview</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrintCurrentPreview" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Print Work Orders</span>
                    </button>
                    <button type="button" id="btnClosePreview" class="text-slate-400 hover:text-slate-700 text-xl font-bold px-2">
                        &times;
                    </button>
                </div>
            </div>

            <!-- Preview Sheets Container -->
            <div id="workOrderSheetsContainer" class="p-6 sm:p-8 bg-stone-50/50 overflow-x-auto space-y-6">
                <!-- Rendered dynamically by JavaScript -->
            </div>
        </section>

        <!-- Section 3: Recent Work Orders Summary Table -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3 bg-white">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Recent Range Work Orders</h2>
                    <p class="mt-1 text-sm text-slate-500">Summary of recent work order batches prepared from recorded range vouchers.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Sr.</th>
                            <th>Month</th>
                            <th>Docket No.</th>
                            <th class="text-center">Work Orders Count</th>
                            <th>Attachment Mode</th>
                            <th class="text-right">Total Sanctioned Amount (₹)</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentWorkOrders as $index => $item)
                            <tr class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-bold text-slate-900">{{ $index + 1 }}</td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        {{ $item['month'] }}
                                    </span>
                                </td>
                                <td class="font-bold text-slate-950 font-mono">{{ $item['docket_no'] }}</td>
                                <td class="text-center font-semibold text-slate-800">{{ $item['count'] }} Orders</td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold {{ $item['mode'] === 'Separate' ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-purple-50 text-purple-800 border border-purple-200' }}">
                                        {{ $item['mode'] }}
                                    </span>
                                </td>
                                <td class="text-right font-extrabold text-emerald-900">₹ {{ number_format($item['total_amount'], 2) }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" class="secondary-button min-h-7 px-2.5 py-0.5 text-xs"
                                            onclick="triggerDirectPrint('{{ $item['month'] }}', '{{ $item['docket_no'] }}', '{{ $item['mode'] }}')">
                                            Print
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <!-- Dynamic JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('workOrderPrintForm');
            const monthSelect = document.getElementById('month');
            const attachmentModeSelect = document.getElementById('attachment_mode');
            const extendDaysInput = document.getElementById('extend_days');
            const printAllDocketsToggle = document.getElementById('print_all_dockets');
            const docketInputContainer = document.getElementById('docketInputContainer');
            const docketInput = document.getElementById('docket_no');
            const closeWorkOrderFormBtn = document.getElementById('closeWorkOrderFormBtn');

            const previewSection = document.getElementById('workOrderPreviewSection');
            const previewSheetsContainer = document.getElementById('workOrderSheetsContainer');
            const previewMonthBadge = document.getElementById('previewMonthBadge');
            const previewTitle = document.getElementById('previewTitle');
            const btnPrintCurrentPreview = document.getElementById('btnPrintCurrentPreview');
            const btnClosePreview = document.getElementById('btnClosePreview');

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            // Toggle handler for "Print all dockets of the month ?"
            printAllDocketsToggle.addEventListener('change', function () {
                if (this.checked) {
                    docketInputContainer.classList.add('opacity-50', 'pointer-events-none');
                    docketInput.value = '';
                } else {
                    docketInputContainer.classList.remove('opacity-50', 'pointer-events-none');
                }
                loadPreviewData();
            });

            // Auto reload preview on field changes
            [monthSelect, attachmentModeSelect, extendDaysInput, docketInput].forEach(el => {
                el.addEventListener('change', () => loadPreviewData());
            });

            // Close button resets or hides preview
            closeWorkOrderFormBtn.addEventListener('click', function () {
                form.reset();
                docketInputContainer.classList.remove('opacity-50', 'pointer-events-none');
                previewSection.classList.add('hidden');
            });

            btnClosePreview.addEventListener('click', function () {
                previewSection.classList.add('hidden');
            });

            btnPrintCurrentPreview.addEventListener('click', function () {
                form.submit();
            });

            // AJAX Preview Fetcher
            async function loadPreviewData() {
                const previewUrl = document.getElementById('workOrderPrintModule').dataset.previewUrl;
                try {
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            month: monthSelect.value,
                            attachment_mode: attachmentModeSelect.value,
                            extend_days: parseInt(extendDaysInput.value) || 0,
                            print_all_dockets: printAllDocketsToggle.checked ? 1 : 0,
                            docket_no: printAllDocketsToggle.checked ? '' : docketInput.value
                        })
                    });

                    if (!response.ok) throw new Error('Preview request failed');
                    const json = await response.json();
                    if (json.success) {
                        renderPreviewSheets(json.data);
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            function renderPreviewSheets(data) {
                previewSection.classList.remove('hidden');
                previewMonthBadge.textContent = `${data.month} Work Orders (${data.work_orders.length})`;
                previewTitle.textContent = `Work Order Preview - Mode: ${data.attachment_mode}`;

                if (!data.work_orders || data.work_orders.length === 0) {
                    previewSheetsContainer.innerHTML = `
                        <div class="text-center py-12 bg-white rounded-xl border border-slate-200">
                            <p class="text-sm font-semibold text-slate-600">No voucher entries found matching the selected month and docket.</p>
                        </div>
                    `;
                    return;
                }

                let html = '';
                data.work_orders.forEach((wo, idx) => {
                    html += `
                        <div class="bg-white rounded-xl border border-slate-300 p-6 sm:p-8 shadow-sm space-y-5">
                            <!-- Official Header -->
                            <div class="border-b-2 border-slate-900 pb-4 text-center space-y-1">
                                <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase">GUJARAT FOREST DEPARTMENT</h1>
                                <h2 class="text-xs sm:text-sm font-bold text-slate-800">${data.division_name} - ${data.range_name}</h2>
                                <h3 class="text-xs font-extrabold text-emerald-900 uppercase tracking-widest pt-1">
                                    કાર્ય હુકમ / WORK ORDER
                                </h3>
                                <div class="flex flex-wrap justify-between items-center text-xs text-slate-700 pt-3 border-t border-slate-200 mt-2 font-semibold gap-2">
                                    <span><strong>Work Order No:</strong> <span class="font-mono text-emerald-900 font-bold">${wo.work_order_no}</span></span>
                                    <span><strong>Outward No:</strong> ${wo.outward_no}</span>
                                    <span><strong>Date:</strong> ${wo.date}</span>
                                </div>
                            </div>

                            <!-- Work Order Details Grid -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs bg-slate-50 p-4 rounded-lg border border-slate-200">
                                <div>
                                    <p class="text-slate-500 font-bold uppercase text-[10px]">Contractor / Party Name &amp; Address:</p>
                                    <p class="text-slate-950 font-bold text-sm mt-0.5">${wo.party_name}</p>
                                    <p class="text-slate-600 mt-0.5">${wo.party_address}</p>
                                    <p class="mt-2 text-slate-700 font-semibold">Voucher Ref: <span class="font-mono text-slate-900">${wo.voucher_no}</span> | Docket: <span class="font-mono text-slate-900">${wo.docket_no}</span></p>
                                </div>
                                <div class="space-y-1.5">
                                    <p class="text-slate-500 font-bold uppercase text-[10px]">Sanction &amp; Location Details:</p>
                                    <p><strong>Budget Code &amp; Scheme:</strong> <span class="font-mono font-bold text-slate-900">${wo.budget_code}</span> (${wo.scheme})</p>
                                    <p><strong>Location / Compartment:</strong> ${wo.place} (${wo.round} / ${wo.beat})</p>
                                    <p class="pt-1">
                                        <strong>Work Duration:</strong> ${wo.start_date} to 
                                        <span class="text-blue-900 font-bold bg-blue-100 px-1.5 py-0.5 rounded">${wo.extended_end_date}</span>
                                        <span class="text-[11px] text-slate-500 font-normal">(Extended by ${wo.extend_days} days)</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Items Table -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs border border-slate-300 border-collapse">
                                    <thead>
                                        <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-300">
                                            <th class="p-2 border border-slate-300 text-center w-8">Sr.</th>
                                            <th class="p-2 border border-slate-300 text-left">Detailed Schedule of Work Operations</th>
                                            <th class="p-2 border border-slate-300 text-left w-36">Location / Plot</th>
                                            <th class="p-2 border border-slate-300 text-right w-20">Quantity</th>
                                            <th class="p-2 border border-slate-300 text-right w-20">Rate (₹)</th>
                                            <th class="p-2 border border-slate-300 text-right w-28">Amount (₹)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${wo.items.map(it => `
                                            <tr class="border-b border-slate-200">
                                                <td class="p-2 border border-slate-300 text-center font-bold">${it.sr_no}</td>
                                                <td class="p-2 border border-slate-300 font-medium text-slate-950">${it.description}</td>
                                                <td class="p-2 border border-slate-300 text-slate-700">${it.plot_area}</td>
                                                <td class="p-2 border border-slate-300 text-right font-semibold">${it.quantity} ${it.unit}</td>
                                                <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(it.rate)}</td>
                                                <td class="p-2 border border-slate-300 text-right font-mono font-bold">${formatMoney(it.amount)}</td>
                                            </tr>
                                        `).join('')}
                                        <tr class="bg-slate-50 font-bold text-slate-950">
                                            <td colspan="5" class="p-2 border border-slate-300 text-right uppercase">Total Sanctioned Work Order Amount:</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono text-emerald-950 text-sm font-extrabold">${formatMoney(wo.total_amount)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <p class="text-xs font-semibold text-slate-800">
                                <strong>Amount in words:</strong> <span class="italic text-emerald-950">${wo.total_amount_words}</span>
                            </p>

                            <!-- Terms & Signatures -->
                            <div class="pt-4 border-t border-slate-200 text-[11px] text-slate-600 space-y-3">
                                <p><strong>નિયમો અને શરતો (Terms &amp; Conditions):</strong></p>
                                <ol class="list-decimal pl-5 space-y-1">
                                    <li>ઉપરોક્ત દર્શાવેલ કામગીરી વન વિભાગના નિર્ધારિત ટેકનિકલ માપદંડ અને અંદાજ મુજબ નિયત સમયમર્યાદા (તારીખ <strong>${wo.extended_end_date}</strong>) સુધીમાં પૂર્ણ કરવાની રહેશે.</li>
                                    <li>કામગીરી પૂર્ણ થયેથી સંબંધિત વનપાલ / વનરક્ષક દ્વારા સ્થળ ચકાસણી અને માપણી પુસ્તિકા (MB) માં નોંધ કરાવી વાઉચર રજૂ કરવાનું રહેશે.</li>
                                </ol>

                                <div class="pt-10 flex justify-between items-end text-center font-bold text-slate-900 text-xs">
                                    <div>
                                        <div class="border-t border-slate-400 pt-1 w-44">એજન્સી / મજૂર મંડળીની સહી</div>
                                    </div>
                                    <div>
                                        <div class="border-t border-slate-400 pt-1 w-44">પરિક્ષેત્ર વન અધિકારી (RFO)<br><span class="text-[10px] font-normal text-slate-600">${data.range_name}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });

                previewSheetsContainer.innerHTML = html;
            }

            // Direct print helper
            window.triggerDirectPrint = function(month, docket, mode) {
                monthSelect.value = month;
                attachmentModeSelect.value = mode;
                if (docket) {
                    printAllDocketsToggle.checked = false;
                    docketInputContainer.classList.remove('opacity-50', 'pointer-events-none');
                    docketInput.value = docket;
                } else {
                    printAllDocketsToggle.checked = true;
                    docketInputContainer.classList.add('opacity-50', 'pointer-events-none');
                    docketInput.value = '';
                }
                form.submit();
            };

            // Trigger initial preview load
            loadPreviewData();
        });
    </script>
</x-layouts.admin>
