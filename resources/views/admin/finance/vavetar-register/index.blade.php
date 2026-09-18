<x-layouts.admin title="Vavetar Register | Forest Inventory" heading="Vavetar Register" subheading="Range Finance System - Plantation &amp; Afforestation Expenditure Register">
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
        .gray-readonly-field {
            background-color: #e9ecef !important;
            border: 1px solid #ced4da !important;
            border-radius: 0.375rem !important;
            color: #495057 !important;
            padding: 0.55rem 0.75rem !important;
            font-size: 0.925rem !important;
            font-weight: 600 !important;
            width: 100% !important;
            min-height: 2.5rem !important;
        }
        .vavetar-warning-box {
            background-color: #ffc107 !important;
            color: #212529 !important;
            padding: 0.75rem !important;
            border-radius: 0.25rem !important;
            text-align: center !important;
        }
    </style>

    <div class="space-y-6" id="vavetarRegisterModule"
        data-locations-url="{{ route('finance.vavetar-register.locations') }}"
        data-preview-url="{{ route('finance.vavetar-register.preview') }}"
        data-print-url="{{ route('finance.vavetar-register.print') }}">

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

        <div id="vavetarAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Main "Vavetar Register" Form Card (Exact Match to Reference Image) -->
        <section class="max-w-xs sm:max-w-sm mx-auto">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-lg overflow-hidden transition-all">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 bg-slate-50/80 border-b border-slate-100">
                    <h2 class="text-base font-bold text-slate-800 tracking-tight">
                        Vavetar Register
                    </h2>
                    <button type="button" id="closeVavetarFormBtn" title="Close" class="text-slate-500 hover:text-slate-800 text-xl font-bold transition-colors focus:outline-none leading-none">
                        ✕
                    </button>
                </div>

                <!-- Form Body -->
                <form id="vavetarRegisterForm" method="GET" action="{{ route('finance.vavetar-register.print') }}" target="_blank" class="p-5 sm:p-6 space-y-4">
                    <input type="hidden" name="report_type" id="reportTypeInput" value="date_wise">

                    <!-- 1. Round -->
                    <div>
                        <label for="round" class="block text-sm font-semibold text-slate-800 text-center mb-1">
                            Round
                        </label>
                        <select id="round" name="round" class="maroon-border-field maroon-select cursor-pointer text-center">
                            <option value="">Choose...</option>
                            @foreach(array_keys($locationMap) as $r)
                                <option value="{{ $r }}" @selected($selectedRound === $r)>{{ $r }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- 2. Beat -->
                    <div>
                        <label for="beat" class="block text-sm font-semibold text-slate-800 text-center mb-1">
                            Beat
                        </label>
                        <select id="beat" name="beat" class="maroon-border-field maroon-select cursor-pointer text-center">
                            <option value="">First Choose Round</option>
                        </select>
                    </div>

                    <!-- 3. Place -->
                    <div>
                        <label for="place" class="block text-sm font-semibold text-slate-800 text-center mb-1">
                            Place
                        </label>
                        <select id="place" name="place" class="blue-border-field maroon-select cursor-pointer text-center">
                            <option value="">First Choose Beat</option>
                        </select>
                    </div>

                    <!-- 4. Area -->
                    <div>
                        <label for="area" class="block text-sm font-semibold text-slate-800 text-center mb-1">
                            Area
                        </label>
                        <input type="text" id="area" name="area" readonly
                            value="{{ $selectedArea }}"
                            class="gray-readonly-field text-center font-mono"
                            placeholder="">
                    </div>

                    <!-- 5. Date Wise Button -->
                    <div class="pt-2">
                        <button type="button" id="btnDateWise"
                            class="w-full inline-flex items-center justify-center rounded-md bg-[#1877f2] hover:bg-blue-600 text-white font-bold py-2.5 px-4 text-sm shadow-sm transition-all active:scale-95 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            Date Wise
                        </button>
                    </div>

                    <!-- 6. Work Wise Button -->
                    <div>
                        <button type="button" id="btnWorkWise"
                            class="w-full inline-flex items-center justify-center rounded-md bg-[#1877f2] hover:bg-blue-600 text-white font-bold py-2.5 px-4 text-sm shadow-sm transition-all active:scale-95 focus:outline-none focus:ring-4 focus:ring-blue-200">
                            Work Wise
                        </button>
                    </div>

                    <!-- 7. Warning Box (Exact Match to Image) -->
                    <div class="vavetar-warning-box shadow-sm space-y-1 mt-3">
                        <div class="inline-block bg-[#495057] text-white text-[11px] font-bold px-2 py-0.5 rounded">
                            'Warning':
                        </div>
                        <p class="text-xs font-black text-[#1e293b] leading-tight pt-0.5">
                            Report will be generated on the basis of passed voucher entries.
                        </p>
                        <p class="text-[11px] font-bold text-white leading-tight">
                            If any correction is required, please inform us.
                        </p>
                    </div>
                </form>
            </div>
        </section>

        <!-- Section 2: Online Live Vavetar Register Preview Section -->
        <section id="vavetarPreviewSection" class="hidden rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-4 bg-emerald-50/40 gap-3">
                <div class="flex items-center gap-3">
                    <span id="previewReportTypeBadge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        Date Wise Report
                    </span>
                    <h3 id="previewTitle" class="text-base font-semibold text-slate-950">Vavetar Register Preview</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrintCurrentPreview" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Print Register</span>
                    </button>
                    <button type="button" id="btnClosePreview" class="text-slate-400 hover:text-slate-700 text-xl font-bold px-2">
                        &times;
                    </button>
                </div>
            </div>

            <!-- Preview Sheet -->
            <div id="vavetarSheetContainer" class="p-6 sm:p-8 bg-white overflow-x-auto space-y-6">
                <!-- Rendered dynamically by JavaScript -->
            </div>
        </section>

        <!-- Section 3: Recent Vavetar Registers Summary Table -->
        <section class="rounded-lg border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3 bg-white">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Recent Plantation &amp; Vavetar Sites</h2>
                    <p class="mt-1 text-sm text-slate-500">Summary of recent plantation compartments registered with passed vouchers.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Sr.</th>
                            <th>Round</th>
                            <th>Beat</th>
                            <th>Plantation Place / Compartment</th>
                            <th class="text-center">Area (Ha)</th>
                            <th class="text-center">Vouchers Count</th>
                            <th class="text-right">Total Expenditure (₹)</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentRegisters as $index => $item)
                            <tr class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-bold text-slate-900">{{ $index + 1 }}</td>
                                <td class="font-bold text-slate-950">{{ $item['round'] }}</td>
                                <td class="text-slate-700">{{ $item['beat'] }}</td>
                                <td class="font-medium text-slate-900">{{ $item['place'] }}</td>
                                <td class="text-center font-mono font-bold text-blue-900">{{ $item['area'] }}</td>
                                <td class="text-center font-semibold text-slate-800">{{ $item['vouchers_count'] }} Vouchers</td>
                                <td class="text-right font-extrabold text-emerald-900">₹ {{ number_format($item['total_expenditure'], 2) }}</td>
                                <td class="text-right">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" class="secondary-button min-h-7 px-2.5 py-0.5 text-xs"
                                            onclick="triggerRegisterDirectPrint('{{ $item['round'] }}', '{{ $item['beat'] }}', '{{ $item['place'] }}', '{{ $item['area'] }}', 'date_wise')">
                                            Date Wise
                                        </button>
                                        <button type="button" class="secondary-button min-h-7 px-2.5 py-0.5 text-xs bg-cyan-50 border-cyan-200 text-cyan-900"
                                            onclick="triggerRegisterDirectPrint('{{ $item['round'] }}', '{{ $item['beat'] }}', '{{ $item['place'] }}', '{{ $item['area'] }}', 'work_wise')">
                                            Work Wise
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
            const form = document.getElementById('vavetarRegisterForm');
            const roundSelect = document.getElementById('round');
            const beatSelect = document.getElementById('beat');
            const placeSelect = document.getElementById('place');
            const areaInput = document.getElementById('area');
            const reportTypeInput = document.getElementById('reportTypeInput');
            const btnDateWise = document.getElementById('btnDateWise');
            const btnWorkWise = document.getElementById('btnWorkWise');
            const closeVavetarFormBtn = document.getElementById('closeVavetarFormBtn');

            const previewSection = document.getElementById('vavetarPreviewSection');
            const previewSheetContainer = document.getElementById('vavetarSheetContainer');
            const previewReportTypeBadge = document.getElementById('previewReportTypeBadge');
            const previewTitle = document.getElementById('previewTitle');
            const btnPrintCurrentPreview = document.getElementById('btnPrintCurrentPreview');
            const btnClosePreview = document.getElementById('btnClosePreview');

            const locationHierarchy = @json($locationMap);

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            // Cascading Dropdown: Round -> Beat
            roundSelect.addEventListener('change', function () {
                const selectedRound = this.value;
                beatSelect.innerHTML = '<option value="">First Choose Round</option>';
                placeSelect.innerHTML = '<option value="">First Choose Beat</option>';
                areaInput.value = '';

                if (selectedRound && locationHierarchy[selectedRound]) {
                    beatSelect.innerHTML = '<option value="">Choose Beat...</option>';
                    Object.keys(locationHierarchy[selectedRound]).forEach(beat => {
                        const opt = document.createElement('option');
                        opt.value = beat;
                        opt.textContent = beat;
                        beatSelect.appendChild(opt);
                    });
                }
            });

            // Cascading Dropdown: Beat -> Place
            beatSelect.addEventListener('change', function () {
                const selectedRound = roundSelect.value;
                const selectedBeat = this.value;
                placeSelect.innerHTML = '<option value="">First Choose Beat</option>';
                areaInput.value = '';

                if (selectedRound && selectedBeat && locationHierarchy[selectedRound] && locationHierarchy[selectedRound][selectedBeat]) {
                    placeSelect.innerHTML = '<option value="">Choose Place...</option>';
                    locationHierarchy[selectedRound][selectedBeat].forEach(item => {
                        const opt = document.createElement('option');
                        opt.value = item.place;
                        opt.textContent = item.place;
                        opt.dataset.hectares = item.hectares || '';
                        placeSelect.appendChild(opt);
                    });
                }
            });

            // Auto populate Area on Place selection
            placeSelect.addEventListener('change', function () {
                const selectedOption = this.options[this.selectedIndex];
                if (selectedOption && selectedOption.dataset.hectares) {
                    areaInput.value = selectedOption.dataset.hectares + ' Ha';
                } else {
                    areaInput.value = '';
                }
            });

            // Close button resets form and hides preview
            closeVavetarFormBtn.addEventListener('click', function () {
                form.reset();
                beatSelect.innerHTML = '<option value="">First Choose Round</option>';
                placeSelect.innerHTML = '<option value="">First Choose Beat</option>';
                areaInput.value = '';
                previewSection.classList.add('hidden');
            });

            btnClosePreview.addEventListener('click', function () {
                previewSection.classList.add('hidden');
            });

            const triggerReportAction = (type) => {
                reportTypeInput.value = type;
                form.submit();
            };

            btnDateWise.addEventListener('click', () => triggerReportAction('date_wise'));
            btnWorkWise.addEventListener('click', () => triggerReportAction('work_wise'));
            btnPrintCurrentPreview.addEventListener('click', () => form.submit());

            // AJAX Preview Fetcher
            async function loadPreviewData(type = 'date_wise') {
                const previewUrl = document.getElementById('vavetarRegisterModule').dataset.previewUrl;
                try {
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            round: roundSelect.value,
                            beat: beatSelect.value,
                            place: placeSelect.value,
                            area: areaInput.value,
                            report_type: type
                        })
                    });

                    if (!response.ok) throw new Error('Preview request failed');
                    const json = await response.json();
                    if (json.success) {
                        renderPreviewSheet(json.data);
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            function renderPreviewSheet(data) {
                previewSection.classList.remove('hidden');
                const isDateWise = data.report_type === 'date_wise';
                previewReportTypeBadge.textContent = isDateWise ? 'Date Wise Report' : 'Work Wise Report';
                previewTitle.textContent = `Plantation Register: ${data.place} (${data.area})`;

                let contentHtml = '';

                if (isDateWise) {
                    contentHtml = `
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs border border-slate-300 border-collapse">
                                <thead>
                                    <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-300">
                                        <th class="p-2 border border-slate-300 text-center w-8">Sr.</th>
                                        <th class="p-2 border border-slate-300 text-center w-24">Work Date</th>
                                        <th class="p-2 border border-slate-300 text-center w-24">Voucher No</th>
                                        <th class="p-2 border border-slate-300 text-left">Agency / Party</th>
                                        <th class="p-2 border border-slate-300 text-left">Particulars of Forestry Operations</th>
                                        <th class="p-2 border border-slate-300 text-right w-20">Quantity</th>
                                        <th class="p-2 border border-slate-300 text-right w-20">Rate (₹)</th>
                                        <th class="p-2 border border-slate-300 text-right w-28">Expenditure (₹)</th>
                                        <th class="p-2 border border-slate-300 text-right w-28 bg-emerald-50 text-emerald-950 font-bold">Progressive (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.date_wise_rows.map((row, idx) => `
                                        <tr class="border-b border-slate-200">
                                            <td class="p-2 border border-slate-300 text-center font-bold">${idx + 1}</td>
                                            <td class="p-2 border border-slate-300 text-center font-semibold text-slate-800">${row.date}</td>
                                            <td class="p-2 border border-slate-300 text-center font-mono text-[11px] text-blue-900 font-bold">${row.voucher_no}</td>
                                            <td class="p-2 border border-slate-300 font-medium text-slate-950">${row.party_name}</td>
                                            <td class="p-2 border border-slate-300 text-slate-800">${row.description}</td>
                                            <td class="p-2 border border-slate-300 text-right font-semibold">${row.quantity} ${row.unit}</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono">${formatMoney(row.rate)}</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono font-bold">${formatMoney(row.amount)}</td>
                                            <td class="p-2 border border-slate-300 text-right font-mono font-extrabold text-emerald-900 bg-emerald-50/50">${formatMoney(row.cumulative_amount)}</td>
                                        </tr>
                                    `).join('')}
                                    <tr class="bg-slate-100 font-bold text-slate-950">
                                        <td colspan="7" class="p-2 border border-slate-300 text-right uppercase">Total Cumulative Expenditure:</td>
                                        <td colspan="2" class="p-2 border border-slate-300 text-right font-mono text-emerald-950 text-sm font-black">${formatMoney(data.total_expenditure)}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    contentHtml = `
                        <div class="space-y-6">
                            ${data.work_wise_groups.map((grp, gIdx) => `
                                <div class="border border-slate-300 rounded-lg overflow-hidden">
                                    <div class="bg-slate-100 px-4 py-2.5 flex justify-between items-center border-b border-slate-300">
                                        <h4 class="font-bold text-xs sm:text-sm text-slate-950">${gIdx + 1}. ${grp.category}</h4>
                                        <span class="font-mono font-bold text-xs text-emerald-900 bg-white border border-slate-200 px-2.5 py-0.5 rounded">Total: ${formatMoney(grp.total_amount)}</span>
                                    </div>
                                    <table class="w-full text-xs border-collapse">
                                        <thead>
                                            <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200">
                                                <th class="p-2 text-left">Activity / Operation</th>
                                                <th class="p-2 text-center w-24">Voucher No</th>
                                                <th class="p-2 text-center w-24">Date</th>
                                                <th class="p-2 text-right w-20">Quantity</th>
                                                <th class="p-2 text-right w-20">Rate (₹)</th>
                                                <th class="p-2 text-right w-28">Amount (₹)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            ${grp.items.map(it => `
                                                <tr>
                                                    <td class="p-2 font-medium text-slate-900">${it.description}</td>
                                                    <td class="p-2 text-center font-mono text-[11px] text-blue-900">${it.voucher_no}</td>
                                                    <td class="p-2 text-center text-slate-600">${it.date}</td>
                                                    <td class="p-2 text-right font-semibold">${it.quantity} ${it.unit}</td>
                                                    <td class="p-2 text-right font-mono">${formatMoney(it.rate)}</td>
                                                    <td class="p-2 text-right font-mono font-bold text-slate-950">${formatMoney(it.amount)}</td>
                                                </tr>
                                            `).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            `).join('')}
                            <div class="p-4 bg-emerald-50 rounded-lg border border-emerald-200 flex justify-between items-center">
                                <span class="font-bold text-slate-900 uppercase text-xs">Total Work-Wise Scheme Expenditure:</span>
                                <span class="font-mono text-base font-black text-emerald-950">${formatMoney(data.total_expenditure)}</span>
                            </div>
                        </div>
                    `;
                }

                previewSheetContainer.innerHTML = `
                    <div class="border-b-2 border-slate-900 pb-4 text-center space-y-1">
                        <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase">GUJARAT STATE FOREST DEPARTMENT</h1>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-800">${data.division_name} - ${data.range_name}</h2>
                        <h3 class="text-xs font-extrabold text-emerald-900 uppercase tracking-widest pt-1">
                            વાવેતર રજીસ્ટર (PLANTATION EXPENDITURE REGISTER) - ${isDateWise ? 'DATE WISE' : 'WORK WISE'}
                        </h3>
                        <div class="flex flex-wrap justify-between items-center text-xs text-slate-700 pt-3 border-t border-slate-200 mt-2 font-semibold gap-2">
                            <span><strong>Round:</strong> ${data.round}</span>
                            <span><strong>Beat:</strong> ${data.beat}</span>
                            <span><strong>Place / Compartment:</strong> <span class="text-blue-900 font-bold">${data.place}</span></span>
                            <span><strong>Area:</strong> <span class="font-mono text-emerald-950 font-bold">${data.area}</span></span>
                        </div>
                    </div>
                    ${contentHtml}
                `;
            }

            // Direct print helper from history table
            window.triggerRegisterDirectPrint = function(round, beat, place, area, type) {
                roundSelect.value = round;
                roundSelect.dispatchEvent(new Event('change'));
                setTimeout(() => {
                    beatSelect.value = beat;
                    beatSelect.dispatchEvent(new Event('change'));
                    setTimeout(() => {
                        placeSelect.value = place;
                        areaInput.value = area;
                        triggerReportAction(type);
                    }, 50);
                }, 50);
            };

            // Trigger preview on initial page load
            loadPreviewData('date_wise');
        });
    </script>
</x-layouts.admin>
