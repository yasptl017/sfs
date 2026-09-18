<x-layouts.admin title="Final Voucher Nos &amp; Form No. 35 &amp; Cashbook | Forest Inventory" heading="Final Voucher Nos &amp; Form No. 35 &amp; Cashbook" subheading="Division Finance System - Assign Final Voucher Numbers, Form No. 35, and Cash Book Ledgers">
    <style>
        .final-voucher-card {
            background: #ffffff !important;
            border-radius: 1.25rem !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            border: 1px solid #e2e8f0 !important;
        }
        .fv-section-card {
            background: #f8fafc !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 1rem !important;
            padding: 1.25rem !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            height: 100% !important;
        }
        .fv-label-blue {
            color: #003884 !important;
            font-weight: 800 !important;
            font-size: 0.925rem !important;
            text-align: center !important;
            display: block !important;
            margin-bottom: 0.35rem !important;
        }
        .fv-select-field {
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 0.5rem !important;
            color: #0f172a !important;
            background-color: #ffffff !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            width: 100% !important;
            text-align: center !important;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
            transition: all 0.15s ease-in-out !important;
        }
        .fv-select-field:focus {
            outline: none !important;
            border-color: #003884 !important;
            box-shadow: 0 0 0 3px rgba(0, 56, 132, 0.2) !important;
        }
        .btn-fv-blue {
            background-color: #1877f2 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            padding: 0.6rem 1rem !important;
            border-radius: 0.5rem !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 2px 4px rgba(24, 119, 242, 0.25) !important;
            width: 100% !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .btn-fv-blue:hover {
            background-color: #0d65d9 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(24, 119, 242, 0.35) !important;
        }
        .btn-fv-blue:active {
            transform: scale(0.98) !important;
        }
        .btn-fv-cyan {
            background-color: #00c2cb !important;
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 0.875rem !important;
            padding: 0.6rem 1rem !important;
            border-radius: 0.5rem !important;
            transition: all 0.15s ease-in-out !important;
            box-shadow: 0 2px 4px rgba(0, 194, 203, 0.25) !important;
            width: 100% !important;
            text-align: center !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .btn-fv-cyan:hover {
            background-color: #00a9b1 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 8px rgba(0, 194, 203, 0.35) !important;
        }
        .btn-fv-cyan:active {
            transform: scale(0.98) !important;
        }
        .merge-box-blue {
            border: 1.5px solid #60a5fa !important;
            border-radius: 0.625rem !important;
            background: #ffffff !important;
            padding: 0.65rem 0.75rem !important;
            cursor: pointer !important;
            transition: all 0.15s ease-in-out !important;
        }
        .merge-box-blue:hover {
            border-color: #2563eb !important;
            background: #eff6ff !important;
            transform: translateY(-1px) !important;
        }
        .merge-box-gray {
            border: 1.5px solid #cbd5e1 !important;
            border-radius: 0.625rem !important;
            background: #ffffff !important;
            padding: 0.65rem 0.75rem !important;
            cursor: pointer !important;
            transition: all 0.15s ease-in-out !important;
        }
        .merge-box-gray:hover {
            border-color: #64748b !important;
            background: #f8fafc !important;
            transform: translateY(-1px) !important;
        }
        @media print {
            body, html {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            aside,
            nav,
            header,
            .app-header,
            .sidebar,
            .nav-section-title,
            .nav-group,
            .nav-submenu,
            #finalVoucherModule > section:not(#fvPreviewSection),
            #fvAlertBox,
            .no-print,
            button,
            .border-b.bg-emerald-50\/40 {
                display: none !important;
            }
            #fvPreviewSection {
                border: none !important;
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
                width: 100% !important;
            }
            #fvSheetContainer {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                display: block !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>

    <div class="space-y-6" id="finalVoucherModule"
        data-assign-url="{{ route('division.final-voucher-cashbook.assign') }}"
        data-preview-url="{{ route('division.final-voucher-cashbook.preview') }}"
        data-print-url="{{ route('division.final-voucher-cashbook.print') }}">

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

        <div id="fvAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Section 1: Main "Assign Final Voucher Nos and Form No. 35 and Cashbook:" Form Card (Proportionate Balanced Layout) -->
        <section class="max-w-5xl lg:max-w-6xl mx-auto">
            <div class="final-voucher-card p-5 sm:p-7 text-slate-900 overflow-hidden transition-all">
                
                <!-- Header with Close Button -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-200 mb-6">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight leading-snug">
                        Assign Final Voucher Nos and Form No. 35 and Cashbook:
                    </h2>
                    <button type="button" id="closeFvFormBtn" title="Close" class="text-slate-500 hover:text-slate-900 text-xl font-bold transition-colors focus:outline-none leading-none">
                        ✕
                    </button>
                </div>

                <!-- Form Controls & Action Grid -->
                <form id="finalVoucherForm" method="GET" action="{{ route('division.final-voucher-cashbook.print') }}" target="_blank" class="space-y-6">
                    <input type="hidden" name="report_type" id="fvReportTypeInput" value="form_35">

                    <!-- Top Row: Configuration & Assignment Controls (Balanced 4-Column Grid) -->
                    <div class="rounded-xl bg-slate-50 p-4 sm:p-5 border border-slate-200 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                            
                            <!-- 1. Payment Mode / Scheme -->
                            <div class="md:col-span-3">
                                <label for="payment_mode" class="fv-label-blue">
                                    Payment Mode / Scheme:
                                </label>
                                <select id="payment_mode" name="payment_mode" class="fv-select-field cursor-pointer">
                                    @foreach($paymentModes as $pm)
                                        <option value="{{ $pm }}">{{ $pm }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 2. Scheme Merging Action Cards -->
                            <div class="md:col-span-5 grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <div class="merge-box-blue flex flex-col justify-center text-center" onclick="openMergeModal()">
                                    <span class="text-xs font-bold text-blue-700 leading-tight">Merge schemes for Form 35 &amp; Cashbook</span>
                                    <span class="text-[11px] text-slate-600 font-medium pt-1 leading-snug">જો તમારે ૨ કે વધારે સ્કીમોને ભેગી કરીને ફોર્મ નં. ૩૫ અને કેશબુક ડાઉનલોડ કરવી હોય તો અહીં ક્લિક કરો.</span>
                                </div>
                                <div class="merge-box-gray flex flex-col justify-center text-center" onclick="openGroupsModal()">
                                    <span class="text-xs font-bold text-slate-800 leading-tight">Manage merged groups</span>
                                    <span class="text-[11px] text-slate-600 font-medium pt-1 leading-snug">ભેગી કરેલી સ્કીમોના જૂથ મેનેજ કરો</span>
                                </div>
                            </div>

                            <!-- 3. Month -->
                            <div class="md:col-span-2">
                                <label for="month" class="fv-label-blue">
                                    Month:
                                </label>
                                <select id="month" name="month" class="fv-select-field cursor-pointer">
                                    @foreach($months as $m)
                                        <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- 4. Scale & Page Size -->
                            <div class="md:col-span-2 grid grid-cols-2 gap-2">
                                <div>
                                    <label for="scale" class="fv-label-blue text-[12px]">
                                        Scale
                                    </label>
                                    <select id="scale" name="scale" class="fv-select-field cursor-pointer text-xs px-1">
                                        @foreach($scales as $k => $val)
                                            <option value="{{ $k }}">{{ $val }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="page_size" class="fv-label-blue text-[12px]">
                                        Page Size
                                    </label>
                                    <select id="page_size" name="page_size" class="fv-select-field cursor-pointer text-xs px-1">
                                        @foreach($pageSizes as $ps)
                                            <option value="{{ $ps }}">{{ $ps }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                        </div>

                        <!-- Main Assignment Action Button -->
                        <div class="pt-2 flex justify-center">
                            <button type="button" id="btnAssignVouchers" class="btn-fv-blue max-w-sm py-2.5 font-bold shadow-md">
                                Assign Final Voucher Nos
                            </button>
                        </div>
                    </div>

                    <!-- Bottom Grid: 3 Distinct Feature Sections Spreading Proportionately -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-stretch">
                        
                        <!-- Panel 1: Form-35 & Sorted Vouchers -->
                        <div class="fv-section-card space-y-3">
                            <div class="space-y-2 text-center">
                                <div class="flex justify-center">
                                    <span class="inline-flex items-center px-4 py-1 rounded bg-[#1877f2] text-white text-xs font-extrabold uppercase tracking-wide shadow-sm">
                                        Sorted
                                    </span>
                                </div>
                                <div class="border-t border-slate-200 pt-2">
                                    <p class="text-xs font-semibold text-slate-800 leading-snug">
                                        Download Form-35 and voucher numbers sorted as per Form 35
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-3 pt-1">
                                <button type="button" class="btn-fv-blue" onclick="triggerReport('form_35')">
                                    Print Form No. 35 &amp; Vouchers Sorted
                                </button>

                                <div class="border-t border-slate-200 pt-2 text-center">
                                    <p class="text-xs font-semibold text-slate-800 leading-snug pb-2">
                                        Download only voucher numbers sorted as per Form 35
                                    </p>
                                    <button type="button" class="btn-fv-cyan" onclick="triggerReport('vouchers_sorted')">
                                        Vouchers sorted as per Form 35
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 2: Cash Book Reports -->
                        <div class="fv-section-card space-y-3">
                            <div class="space-y-2 text-center">
                                <h3 class="text-xs font-extrabold text-slate-900 uppercase tracking-wider">
                                    Cash Book Reports
                                </h3>
                                <button type="button" class="btn-fv-blue" onclick="triggerReport('cashbook')">
                                    Prepare Cash Book
                                </button>
                            </div>

                            <div class="border-t border-slate-200 pt-2 space-y-2.5 text-center">
                                <div>
                                    <p class="text-xs font-bold text-slate-900">
                                        Download Credit and Debit Part Separately
                                    </p>
                                    <p class="text-[11px] text-slate-500 font-medium leading-snug pt-0.5">
                                        If maximum execution time exceeds, then download Credit Part and Debit Part separately.
                                    </p>
                                </div>

                                <div class="space-y-2 pt-1">
                                    <button type="button" class="btn-fv-blue" onclick="triggerReport('cashbook_credit')">
                                        Download Credit Part
                                    </button>
                                    <button type="button" class="btn-fv-blue" onclick="triggerReport('cashbook_debit')">
                                        Download Debit Part
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 3: Range Reports -->
                        <div class="fv-section-card space-y-3">
                            <div>
                                <p class="text-[11px] font-bold text-slate-800 leading-relaxed text-center pb-2">
                                    ફોર્મ-૩૫ અથવા કેશબૂક રિપોર્ટ જો દરેક રેન્જમાં મોકલવો હોય (કાયમી ફાઇલ કરવા માટે), તો નીચે રેન્જ પસંદ કરીને તેની નીચેનાં બટન પર ક્લિક કરો.
                                </p>

                                <div class="py-1">
                                    <label for="range_id" class="fv-label-blue">
                                        Range
                                    </label>
                                    <select id="range_id" name="range_id" class="fv-select-field cursor-pointer">
                                        <option value="">Choose...</option>
                                        @foreach($ranges as $rng)
                                            <option value="{{ $rng->id }}">{{ $rng->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-2.5 pt-1">
                                <button type="button" class="btn-fv-blue" onclick="triggerReport('range_form_35')">
                                    Form-35 for range
                                </button>

                                <button type="button" class="btn-fv-blue" onclick="triggerReport('range_cashbook')">
                                    Cashbook for range
                                </button>

                                <div class="text-center pt-1">
                                    <span class="text-xs font-bold text-amber-600 block">
                                        આ રિપોર્ટ નવો હોવાથી ચેક કરવો.
                                    </span>
                                    <span class="text-[11px] font-semibold text-amber-700 block">
                                        (Cashbook for range)
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </section>

        <!-- Section 2: Online Live Preview Sheet -->
        <section id="fvPreviewSection" class="hidden rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-wrap items-center justify-between border-b border-emerald-100 px-5 py-4 bg-emerald-50/40 gap-3">
                <div class="flex items-center gap-3">
                    <span id="fvPreviewBadge" class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                        Form-35
                    </span>
                    <h3 id="fvPreviewTitle" class="text-base font-semibold text-slate-950">Report Preview</h3>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" id="btnPrintFvCurrent" class="primary-button text-xs py-1.5 px-4 bg-emerald-700 hover:bg-emerald-800 flex items-center gap-1.5 font-bold">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Print Full Report</span>
                    </button>
                    <button type="button" id="btnExportFvCsv" class="secondary-button text-xs py-1.5 px-3 font-semibold">
                        Export CSV
                    </button>
                    <button type="button" id="btnCloseFvPreview" class="text-slate-400 hover:text-slate-700 text-xl font-bold px-2">
                        &times;
                    </button>
                </div>
            </div>

            <!-- Preview Sheet Container -->
            <div id="fvSheetContainer" class="p-6 sm:p-8 bg-white overflow-x-auto space-y-6">
                <!-- Populated dynamically via JS -->
            </div>
        </section>

        <!-- Section 3: Recent Final Voucher Numbers & Cashbook Generations Summary -->
        <section class="rounded-xl border border-emerald-100 bg-white shadow-sm overflow-hidden mt-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-emerald-100 px-5 py-4 gap-3 bg-white">
                <div>
                    <h2 class="text-base font-semibold text-slate-950">Recent Final Voucher Assignments</h2>
                    <p class="mt-1 text-sm text-slate-500">History of final voucher sequencing and Form No. 35 / Cashbook compilations.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="data-table w-full text-left">
                    <thead>
                        <tr>
                            <th class="w-16 text-center">Sr.</th>
                            <th>Month</th>
                            <th>Mode</th>
                            <th>Scheme / Group</th>
                            <th class="text-center">Assigned Vouchers</th>
                            <th class="text-right">Total Amount (₹)</th>
                            <th class="text-center">Status</th>
                            <th>Assigned At</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentVouchers as $idx => $v)
                            <tr class="transition-colors hover:bg-emerald-50/40">
                                <td class="text-center font-bold text-slate-700">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                        {{ $v['month'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $v['mode'] === 'IFMS' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                                        {{ $v['mode'] }}
                                    </span>
                                </td>
                                <td class="font-semibold text-slate-900">{{ $v['scheme'] }}</td>
                                <td class="text-center font-bold text-slate-900">{{ $v['assigned_count'] }} Vouchers</td>
                                <td class="text-right font-bold text-slate-900 font-mono">₹ {{ number_format($v['total_amount'], 2) }}</td>
                                <td class="text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        {{ $v['status'] }}
                                    </span>
                                </td>
                                <td class="text-xs text-slate-500">{{ $v['assigned_at'] }}</td>
                                <td class="text-right">
                                    <button type="button" class="secondary-button min-h-7 px-2.5 py-0.5 text-xs font-bold"
                                        onclick="triggerDirectPrint('{{ $v['month'] }}', '{{ $v['mode'] }}', 'form_35')">
                                        Print
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

    </div>

    <!-- Merge Schemes Modal -->
    <div id="mergeModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <h3 class="text-base font-bold text-slate-900">Merge Schemes for Form 35 &amp; Cashbook</h3>
                <button type="button" onclick="closeMergeModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
            </div>
            <div class="space-y-3">
                <div>
                    <label class="form-label font-bold text-slate-800">New Group Name:</label>
                    <input type="text" id="mergeGroupNameInput" class="form-input text-sm font-semibold" placeholder="e.g., Bamboo Mission Combined Group">
                </div>
                <div>
                    <label class="form-label font-bold text-slate-800">Select Schemes to Merge:</label>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-slate-200 rounded-lg p-3 bg-slate-50 text-xs font-semibold">
                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked class="rounded text-blue-600"> 2406- National Bamboo Mission</label>
                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" checked class="rounded text-blue-600"> 2406- National Bamboo Mission (SCP)</label>
                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-blue-600"> Agroforestry Under Mission</label>
                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-blue-600"> DCP Nursery Scheme</label>
                        <label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" class="rounded text-blue-600"> RDFL Plantation</label>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
                <button type="button" onclick="closeMergeModal()" class="secondary-button text-xs py-2 px-4">Cancel</button>
                <button type="button" onclick="saveMergeGroup()" class="primary-button text-xs py-2 px-4 bg-blue-600 hover:bg-blue-700">Save Merged Group</button>
            </div>
        </div>
    </div>

    <!-- Manage Groups Modal -->
    <div id="groupsModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                <h3 class="text-base font-bold text-slate-900">Manage Merged Groups (જૂથ મેનેજ કરો)</h3>
                <button type="button" onclick="closeGroupsModal()" class="text-slate-400 hover:text-slate-700 text-xl font-bold">&times;</button>
            </div>
            <div class="space-y-3">
                @foreach($mergedGroups as $grp)
                    <div class="border border-slate-200 rounded-xl p-3 bg-slate-50 space-y-1">
                        <div class="flex items-center justify-between">
                            <h4 class="text-xs font-bold text-slate-900">{{ $grp['name'] }}</h4>
                            <span class="text-[11px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">Active</span>
                        </div>
                        <p class="text-[11px] text-slate-600 font-medium">{{ implode(', ', $grp['schemes']) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="flex items-center justify-end pt-3 border-t border-slate-200">
                <button type="button" onclick="closeGroupsModal()" class="secondary-button text-xs py-2 px-4">Close</button>
            </div>
        </div>
    </div>

    <!-- Interactive JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('finalVoucherForm');
            const alertBox = document.getElementById('fvAlertBox');
            const monthSelect = document.getElementById('month');
            const paymentModeSelect = document.getElementById('payment_mode');
            const rangeSelect = document.getElementById('range_id');
            const scaleSelect = document.getElementById('scale');
            const pageSizeSelect = document.getElementById('page_size');
            const fvReportTypeInput = document.getElementById('fvReportTypeInput');
            const closeFvFormBtn = document.getElementById('closeFvFormBtn');

            const btnAssignVouchers = document.getElementById('btnAssignVouchers');
            const previewSection = document.getElementById('fvPreviewSection');
            const previewSheetContainer = document.getElementById('fvSheetContainer');
            const previewReportBadge = document.getElementById('fvPreviewBadge');
            const previewTitle = document.getElementById('fvPreviewTitle');
            const btnPrintFvCurrent = document.getElementById('btnPrintFvCurrent');
            const btnExportFvCsv = document.getElementById('btnExportFvCsv');
            const btnCloseFvPreview = document.getElementById('btnCloseFvPreview');

            let currentReportData = null;

            const formatMoney = (amount) => {
                return '₹ ' + Number(amount || 0).toLocaleString('en-IN', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            };

            // Modal Handlers
            window.openMergeModal = () => document.getElementById('mergeModal').classList.remove('hidden');
            window.closeMergeModal = () => document.getElementById('mergeModal').classList.add('hidden');
            window.openGroupsModal = () => document.getElementById('groupsModal').classList.remove('hidden');
            window.closeGroupsModal = () => document.getElementById('groupsModal').classList.add('hidden');

            window.saveMergeGroup = function () {
                const name = document.getElementById('mergeGroupNameInput').value.trim() || 'Merged Scheme Group';
                alert(`Scheme group "${name}" saved successfully!`);
                closeMergeModal();
            };

            // Assign Final Voucher Numbers Handler
            btnAssignVouchers.addEventListener('click', async function () {
                const assignUrl = document.getElementById('finalVoucherModule').dataset.assignUrl;
                try {
                    btnAssignVouchers.disabled = true;
                    btnAssignVouchers.textContent = 'Assigning...';

                    const response = await fetch(assignUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            month: monthSelect.value,
                            payment_mode: paymentModeSelect.value,
                            scale: scaleSelect.value,
                            page_size: pageSizeSelect.value,
                        })
                    });

                    const json = await response.json();
                    if (json.success) {
                        alertBox.className = 'rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 shadow-sm block';
                        alertBox.innerHTML = `✅ ${json.message}`;
                        triggerReport('form_35');
                    } else {
                        alert(json.message || 'Error assigning vouchers.');
                    }
                } catch (e) {
                    console.error(e);
                    alert('Request failed. Please try again.');
                } finally {
                    btnAssignVouchers.disabled = false;
                    btnAssignVouchers.textContent = 'Assign Final Voucher Nos';
                }
            });

            // Report Trigger
            window.triggerReport = async function (type) {
                fvReportTypeInput.value = type;
                const previewUrl = document.getElementById('finalVoucherModule').dataset.previewUrl;

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
                            payment_mode: paymentModeSelect.value,
                            report_type: type,
                            range_id: rangeSelect.value,
                            scale: scaleSelect.value,
                            page_size: pageSizeSelect.value,
                        })
                    });

                    if (!response.ok) throw new Error('Preview error');
                    const json = await response.json();
                    if (json.success) {
                        currentReportData = json.data;
                        renderFvSheet(type, json.data);
                        previewSection.classList.remove('hidden');
                        previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                } catch (e) {
                    console.error(e);
                    form.submit();
                }
            };

            function renderFvSheet(type, data) {
                previewReportBadge.textContent = data.report_type.toUpperCase();
                previewTitle.textContent = `${data.report_title} (${data.month} - ${data.payment_mode})`;

                let tableHtml = '';

                if (type === 'cashbook' || type === 'cashbook_credit' || type === 'cashbook_debit' || type === 'range_cashbook') {
                    const sum = data.cashbook_summary;
                    tableHtml = `
                        <!-- Cash Book Balance Summary Header -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
                            <div class="rounded-lg border border-slate-300 bg-slate-50 p-3 text-center">
                                <span class="text-[11px] text-slate-600 font-bold block uppercase">Opening Balance</span>
                                <span class="text-sm font-extrabold text-slate-950 font-mono">${formatMoney(sum.opening_balance)}</span>
                            </div>
                            <div class="rounded-lg border border-blue-200 bg-blue-50/60 p-3 text-center">
                                <span class="text-[11px] text-blue-700 font-bold block uppercase">Total Receipts (આવક)</span>
                                <span class="text-sm font-extrabold text-blue-900 font-mono">${formatMoney(sum.total_receipts)}</span>
                            </div>
                            <div class="rounded-lg border border-rose-200 bg-rose-50/60 p-3 text-center">
                                <span class="text-[11px] text-rose-700 font-bold block uppercase">Total Payments (ખર્ચ)</span>
                                <span class="text-sm font-extrabold text-rose-900 font-mono">${formatMoney(sum.total_payments)}</span>
                            </div>
                            <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-3 text-center">
                                <span class="text-[11px] text-emerald-700 font-bold block uppercase">Closing Balance</span>
                                <span class="text-sm font-extrabold text-emerald-950 font-mono">${formatMoney(sum.closing_balance)}</span>
                            </div>
                        </div>

                        <!-- Cash Book Double Entry Table -->
                        <table class="w-full text-xs border border-slate-300 border-collapse mb-6">
                            <thead>
                                <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-300">
                                    <th class="p-2 border border-slate-300 text-center w-12">FV No</th>
                                    <th class="p-2 border border-slate-300 text-center w-24">Date</th>
                                    <th class="p-2 border border-slate-300 text-left">Particulars &amp; Head of Account</th>
                                    <th class="p-2 border border-slate-300 text-left">Party / Contractor</th>
                                    <th class="p-2 border border-slate-300 text-right w-28">Gross Value (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right w-28 text-rose-800">Deductions (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right w-28 font-bold text-emerald-950 bg-emerald-50/50">Net Disbursed (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.entries.map(e => `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-bold font-mono text-blue-900">${e.voucher_no}</td>
                                        <td class="p-2 border border-slate-300 text-center">${e.date}</td>
                                        <td class="p-2 border border-slate-300">
                                            <span class="font-bold text-slate-900 block">${e.budget_code}</span>
                                            <span class="text-[11px] text-slate-600 block">${e.description}</span>
                                        </td>
                                        <td class="p-2 border border-slate-300 font-semibold text-slate-900">${e.party_name}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono font-medium">${formatMoney(e.gross_amount)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono text-rose-700">${formatMoney(e.deductions)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40">${formatMoney(e.net_amount)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                    <td colspan="4" class="p-2 border border-slate-300 text-right uppercase">Total Expenditure:</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold">${formatMoney(data.totals.gross_amount)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold text-rose-800">${formatMoney(data.totals.total_deductions)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold text-emerald-950">${formatMoney(data.totals.net_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                } else {
                    tableHtml = `
                        <!-- Form No. 35 Schedule of Vouchers -->
                        <table class="w-full text-xs border border-slate-300 border-collapse mb-6">
                            <thead>
                                <tr class="bg-slate-100 text-slate-900 font-bold border-b border-slate-300">
                                    <th class="p-2 border border-slate-300 text-center w-12">FV No</th>
                                    <th class="p-2 border border-slate-300 text-center w-20">Orig No</th>
                                    <th class="p-2 border border-slate-300 text-left">Range &amp; Budget Head</th>
                                    <th class="p-2 border border-slate-300 text-left">Party Details</th>
                                    <th class="p-2 border border-slate-300 text-left">Description</th>
                                    <th class="p-2 border border-slate-300 text-right w-24">Gross (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right w-24">Deductions (₹)</th>
                                    <th class="p-2 border border-slate-300 text-right w-28 font-bold text-emerald-950 bg-emerald-50/50">Net Payable (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.entries.map(e => `
                                    <tr class="border-b border-slate-200">
                                        <td class="p-2 border border-slate-300 text-center font-bold font-mono text-blue-900">${e.voucher_no}</td>
                                        <td class="p-2 border border-slate-300 text-center font-mono text-slate-600">${e.original_voucher_no}</td>
                                        <td class="p-2 border border-slate-300">
                                            <span class="font-bold text-slate-900 block">${e.range_name}</span>
                                            <span class="text-[11px] text-slate-600 block">${e.budget_code}</span>
                                        </td>
                                        <td class="p-2 border border-slate-300 font-semibold text-slate-900">${e.party_name}</td>
                                        <td class="p-2 border border-slate-300 text-slate-800">${e.description}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono font-medium">${formatMoney(e.gross_amount)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono text-rose-700">${formatMoney(e.deductions)}</td>
                                        <td class="p-2 border border-slate-300 text-right font-mono font-bold text-emerald-950 bg-emerald-50/40">${formatMoney(e.net_amount)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr class="bg-slate-100 font-bold border-t-2 border-slate-900">
                                    <td colspan="5" class="p-2 border border-slate-300 text-right uppercase">Grand Total Vouchers:</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold">${formatMoney(data.totals.gross_amount)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold text-rose-800">${formatMoney(data.totals.total_deductions)}</td>
                                    <td class="p-2 border border-slate-300 text-right font-mono font-bold text-emerald-950">${formatMoney(data.totals.net_amount)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                }

                previewSheetContainer.innerHTML = `
                    <div class="border-b-2 border-slate-900 pb-4 text-center space-y-1">
                        <h1 class="text-base sm:text-lg font-black text-slate-950 uppercase">GUJARAT STATE FOREST DEPARTMENT</h1>
                        <h2 class="text-xs sm:text-sm font-bold text-slate-800">${data.division_name}</h2>
                        <h3 class="text-xs font-extrabold text-emerald-900 uppercase tracking-widest pt-1">
                            ${data.report_title} - ${data.month.toUpperCase()}
                        </h3>
                        <div class="flex flex-wrap justify-between items-center text-xs text-slate-700 pt-3 border-t border-slate-200 mt-2 font-semibold gap-2">
                            <span><strong>Month:</strong> ${data.month}</span>
                            <span><strong>Payment Mode:</strong> <span class="text-blue-900 font-bold">${data.payment_mode}</span></span>
                            <span><strong>Range:</strong> ${data.range_name}</span>
                            <span><strong>Date:</strong> ${data.generated_at}</span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        ${tableHtml}
                    </div>
                `;
            }

            // Print Handler
            btnPrintFvCurrent.addEventListener('click', function () {
                const printUrl = `${document.getElementById('finalVoucherModule').dataset.printUrl}?month=${monthSelect.value}&payment_mode=${paymentModeSelect.value}&report_type=${fvReportTypeInput.value}&range_id=${rangeSelect.value}&scale=${scaleSelect.value}&page_size=${pageSizeSelect.value}&autoprint=1`;
                window.open(printUrl, '_blank');
            });

            btnCloseFvPreview.addEventListener('click', function () {
                previewSection.classList.add('hidden');
            });

            closeFvFormBtn.addEventListener('click', function () {
                form.reset();
                previewSection.classList.add('hidden');
            });

            // CSV Export Handler
            btnExportFvCsv.addEventListener('click', function () {
                if (!currentReportData || !currentReportData.entries) {
                    alert('No data available to export.');
                    return;
                }
                const rows = currentReportData.entries;
                let csv = 'FV No,Original No,Date,Range,Budget Code,Party Name,Description,Gross,Deductions,Net\n';
                rows.forEach((r) => {
                    csv += `"${r.voucher_no}","${r.original_voucher_no}","${r.date}","${r.range_name}","${r.budget_code}","${r.party_name}","${r.description}","${r.gross_amount}","${r.deductions}","${r.net_amount}"\n`;
                });
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.setAttribute('href', url);
                a.setAttribute('download', `final_vouchers_${currentReportData.month}_${currentReportData.payment_mode}.csv`);
                a.click();
            });

            window.triggerDirectPrint = function (month, mode, type) {
                monthSelect.value = month;
                paymentModeSelect.value = mode;
                const printUrl = `${document.getElementById('finalVoucherModule').dataset.printUrl}?month=${month}&payment_mode=${mode}&report_type=${type}&autoprint=1`;
                window.open(printUrl, '_blank');
            };
        });
    </script>
</x-layouts.admin>
