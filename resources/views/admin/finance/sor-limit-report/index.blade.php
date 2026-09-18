<x-layouts.admin title="SOR Limit Report | Forest Inventory" heading="SOR Limit Report" subheading="Range Finance System - Schedule of Rates (SOR) Limit Verification &amp; Expenditure Monitoring">
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
    </style>

    <div class="space-y-6" id="sorLimitReportModule"
        data-filters-url="{{ route('finance.sor-limit-report.filters') }}"
        data-preview-url="{{ route('finance.sor-limit-report.preview') }}"
        data-print-url="{{ route('finance.sor-limit-report.print') }}"
        data-export-url="{{ route('finance.sor-limit-report.export') }}">

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

        <div id="sorAlertBox" class="hidden rounded-lg border px-4 py-3 text-sm font-semibold shadow-sm"></div>

        <!-- Layout Grid: Left (Form Controls & Parameters), Right (Metrics & Live Verification Results) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            
            <!-- Left Column: Filter & Selection Form (4 Cols) -->
            <div class="lg:col-span-4 space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white shadow-md overflow-hidden">
                    
                    <!-- Form Header -->
                    <div class="flex items-center justify-between px-5 py-4 bg-slate-50/80 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-800">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                                </svg>
                            </div>
                            <h2 class="text-base font-bold text-slate-800 tracking-tight">
                                SOR Limit Parameters
                            </h2>
                        </div>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Range Form
                        </span>
                    </div>

                    <!-- Filter Form -->
                    <form id="sorReportForm" method="GET" action="{{ route('finance.sor-limit-report.print') }}" target="_blank" class="p-5 space-y-4">
                        
                        <!-- 1. Budget Code -->
                        <div>
                            <label for="budgetCodeSelect" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Budget Code / Scheme <span class="text-rose-500">*</span>
                            </label>
                            <select id="budgetCodeSelect" name="budget_code" class="maroon-border-field maroon-select">
                                <option value="">All Budget Codes (બધા બજેટ કોડ)</option>
                                @foreach($budgetCodes as $b)
                                    <option value="{{ $b->budget_code }}" @selected($selectedBudget === $b->budget_code) data-scheme="{{ $b->scheme }}">
                                        {{ $b->budget_code }} ({{ Str::limit($b->scheme, 28) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 2. Financial Year & Month Grid -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="monthSelect" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Month (માસ)
                                </label>
                                <select id="monthSelect" name="month" class="maroon-border-field maroon-select">
                                    <option value="">All Months</option>
                                    @foreach($months as $m)
                                        <option value="{{ $m }}" @selected($selectedMonth === $m)>{{ $m }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="yearInput" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                    Fin. Year (વર્ષ)
                                </label>
                                <input type="text" id="yearInput" name="year" value="{{ $selectedYear }}" class="maroon-border-field" placeholder="2026-27">
                            </div>
                        </div>

                        <!-- 3. Round Cascade -->
                        <div>
                            <label for="roundSelect" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Round (રાઉન્ડ)
                            </label>
                            <select id="roundSelect" name="round" class="maroon-border-field maroon-select">
                                <option value="">All Rounds (બધા રાઉન્ડ)</option>
                                @foreach(array_keys($locationHierarchy) as $r)
                                    <option value="{{ $r }}" @selected($selectedRound === $r)>{{ $r }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 4. Beat Cascade -->
                        <div>
                            <label for="beatSelect" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Beat (બીટ)
                            </label>
                            <select id="beatSelect" name="beat" class="maroon-border-field maroon-select">
                                <option value="">All Beats (બધી બીટ)</option>
                            </select>
                        </div>

                        <!-- 5. Place Cascade -->
                        <div>
                            <label for="placeSelect" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Plantation Site / Place (સ્થળ)
                            </label>
                            <select id="placeSelect" name="place" class="maroon-border-field maroon-select">
                                <option value="">All Plantation Sites (બધા સ્થળો)</option>
                            </select>
                        </div>

                        <!-- 6. SOR Item Code Category -->
                        <div>
                            <label for="sorCodeSelect" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                SOR Item / Operation (કામગીરી કોડ)
                            </label>
                            <select id="sorCodeSelect" name="sor_code" class="maroon-border-field maroon-select">
                                <option value="">All SOR Operations (બધી કામગીરી)</option>
                                @foreach($sorList as $sor)
                                    <option value="{{ $sor['code'] }}" @selected($selectedSorCode === $sor['code'])>
                                        {{ $sor['code'] }} - {{ $sor['name_gu'] }} (Std Rate: ₹{{ number_format($sor['standard_rate'], 2) }}/{{ $sor['unit'] }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- 7. Report Mode Radio Selection -->
                        <div class="space-y-1.5 pt-2 border-t border-slate-100">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Report Analysis Mode (રિપોર્ટ પ્રકાર)
                            </label>
                            <div class="grid grid-cols-1 gap-2">
                                <label class="flex items-center gap-2.5 p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-semibold text-slate-800 transition-colors">
                                    <input type="radio" name="report_mode" value="detailed" class="text-emerald-600 focus:ring-emerald-500" @checked($reportMode === 'detailed')>
                                    <div>
                                        <span class="block text-slate-900 font-bold">Detailed Voucher &amp; SOR Statement</span>
                                        <span class="text-[11px] text-slate-500">Each voucher item compared with SOR sanctioned limits</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2.5 p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer text-xs font-semibold text-slate-800 transition-colors">
                                    <input type="radio" name="report_mode" value="summary" class="text-emerald-600 focus:ring-emerald-500" @checked($reportMode === 'summary')>
                                    <div>
                                        <span class="block text-slate-900 font-bold">Consolidated SOR Code Summary</span>
                                        <span class="text-[11px] text-slate-500">Grouped by SOR item code, total limits vs expenditure</span>
                                    </div>
                                </label>
                                <label class="flex items-center gap-2.5 p-2 rounded-lg border border-slate-200 hover:bg-rose-50/50 cursor-pointer text-xs font-semibold text-slate-800 transition-colors">
                                    <input type="radio" name="report_mode" value="violations" class="text-rose-600 focus:ring-rose-500" @checked($reportMode === 'violations')>
                                    <div>
                                        <span class="block text-rose-950 font-bold">Limit Excess &amp; Rate Alert Filter</span>
                                        <span class="text-[11px] text-rose-600 font-medium">Show only vouchers exceeding standard SOR rates/limits</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Form Action Buttons -->
                        <div class="space-y-2 pt-3 border-t border-slate-200">
                            <button type="submit" id="printSorBtn" class="primary-button w-full justify-center py-2.5 bg-emerald-700 hover:bg-emerald-800 text-sm font-bold flex items-center gap-2 shadow-md">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                </svg>
                                <span>Print SOR Limit Report</span>
                            </button>

                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" id="previewSorBtn" class="secondary-button justify-center text-xs py-2 font-bold flex items-center gap-1.5 border-slate-300">
                                    <svg class="h-4 w-4 text-slate-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Refresh Preview</span>
                                </button>
                                <a id="exportCsvBtn" href="{{ route('finance.sor-limit-report.export') }}" class="secondary-button justify-center text-xs py-2 font-bold text-slate-700 flex items-center gap-1.5 border-slate-300">
                                    <svg class="h-4 w-4 text-emerald-700" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Export CSV</span>
                                </a>
                            </div>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Right Column: Analytics Metrics & Live Verification Table (8 Cols) -->
            <div class="lg:col-span-8 space-y-5">
                
                <!-- 1. Executive Summary Metric Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Sanctioned Limit</span>
                        <div id="statSanctionedLimit" class="text-lg sm:text-xl font-black text-slate-950 font-mono">
                            ₹ {{ number_format($reportData['totals']['total_sanctioned_limit'] ?? 0, 2) }}
                        </div>
                        <span class="text-[10px] text-slate-500 block">Total Approved SOR ceiling</span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Billed Expenditure</span>
                        <div id="statActualExpenditure" class="text-lg sm:text-xl font-black text-blue-900 font-mono">
                            ₹ {{ number_format($reportData['totals']['total_actual_expenditure'] ?? 0, 2) }}
                        </div>
                        <span class="text-[10px] text-slate-500 block">Actual voucher billing</span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Net Savings / Balance</span>
                        <div id="statSavingsBalance" class="text-lg sm:text-xl font-black text-emerald-700 font-mono">
                            ₹ {{ number_format($reportData['totals']['total_savings_balance'] ?? 0, 2) }}
                        </div>
                        <span class="text-[10px] text-emerald-700 font-semibold block">Within ceiling limit</span>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm space-y-1">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Compliance Rate</span>
                        <div id="statComplianceRate" class="text-lg sm:text-xl font-black text-emerald-950 font-mono">
                            {{ $reportData['totals']['compliance_rate'] ?? 100 }}%
                        </div>
                        <span id="statViolationsCount" class="text-[10px] {{ ($reportData['totals']['total_violations_count'] ?? 0) > 0 ? 'text-rose-600 font-bold' : 'text-emerald-700' }} block">
                            {{ ($reportData['totals']['total_violations_count'] ?? 0) }} rate violations
                        </span>
                    </div>
                </div>

                <!-- 2. Live Verification & Comparison Table Card -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-md overflow-hidden space-y-0">
                    
                    <!-- Table Title & Filter Bar -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-5 py-3.5 bg-slate-50 border-b border-slate-200 gap-3">
                        <div class="space-y-0.5">
                            <h3 id="tableHeaderTitle" class="text-sm font-extrabold text-slate-900">
                                {{ $reportMode === 'summary' ? 'Consolidated SOR Code Summary' : ($reportMode === 'violations' ? 'SOR Rate & Limit Violation Alerts' : 'Detailed Item-Wise SOR Verification Statement') }}
                            </h3>
                            <p id="tableHeaderSubtitle" class="text-xs text-slate-500">
                                {{ $reportData['budget_code'] }} | {{ $reportData['month'] }} | {{ $reportData['round'] }}
                            </p>
                        </div>
                        
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <div class="relative flex-1 sm:w-48">
                                <input type="text" id="liveTableSearch" placeholder="Filter rows..." class="w-full text-xs rounded-lg border border-slate-300 py-1.5 pl-2.5 pr-6 focus:border-emerald-600 focus:outline-none">
                            </div>
                            <button type="button" id="quickPrintTopBtn" onclick="document.getElementById('sorReportForm').submit()" class="primary-button text-xs py-1.5 px-3 bg-emerald-700 hover:bg-emerald-800 font-bold flex items-center gap-1 shadow-sm">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                </svg>
                                <span>Print</span>
                            </button>
                        </div>
                    </div>

                    <!-- Scrollable Table Container -->
                    <div class="overflow-x-auto max-h-[560px] overflow-y-auto">
                        <table class="w-full text-xs border-collapse" id="sorPreviewTable">
                            <thead class="sticky top-0 bg-slate-100 text-slate-800 font-bold border-b border-slate-300 z-10">
                                @if($reportMode === 'summary')
                                    <tr>
                                        <th class="p-2.5 text-center border-r border-slate-200 w-10">Sr.</th>
                                        <th class="p-2.5 text-left border-r border-slate-200">SOR Code &amp; Work Description</th>
                                        <th class="p-2.5 text-center border-r border-slate-200 w-16">Unit</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-24">SOR Rate (₹)</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-24">Sanctioned Qty</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-28">Approved Limit (₹)</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-24">Billed Qty</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-28">Billed Amount (₹)</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-28">Balance (₹)</th>
                                        <th class="p-2.5 text-center w-28">Status</th>
                                    </tr>
                                @else
                                    <tr>
                                        <th class="p-2.5 text-center border-r border-slate-200 w-10">Sr.</th>
                                        <th class="p-2.5 text-left border-r border-slate-200 w-32">Voucher &amp; Date</th>
                                        <th class="p-2.5 text-left border-r border-slate-200 w-44">Party / Contractor</th>
                                        <th class="p-2.5 text-left border-r border-slate-200">SOR Code &amp; Work Description</th>
                                        <th class="p-2.5 text-center border-r border-slate-200 w-14">Unit</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-20">SOR Rate (₹)</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-20">Billed Rate (₹)</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-24">Limit (₹)</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-24">Billed (₹)</th>
                                        <th class="p-2.5 text-right border-r border-slate-200 w-24">Variance (₹)</th>
                                        <th class="p-2.5 text-center w-28">Status</th>
                                    </tr>
                                @endif
                            </thead>
                            <tbody id="sorTableBody" class="divide-y divide-slate-200">
                                @if($reportMode === 'summary')
                                    @forelse($reportData['summary_rows'] as $idx => $r)
                                        <tr class="hover:bg-slate-50/80 transition-colors {{ $r['balance_amount'] < 0 ? 'bg-rose-50/40' : '' }}">
                                            <td class="p-2 text-center font-bold text-slate-700">{{ $idx + 1 }}</td>
                                            <td class="p-2">
                                                <strong class="text-slate-900 font-mono font-bold block">{{ $r['sor_code'] }}</strong>
                                                <span class="text-slate-700">{{ $r['work_description'] }}</span>
                                            </td>
                                            <td class="p-2 text-center text-slate-600">{{ $r['unit'] }}</td>
                                            <td class="p-2 text-right font-mono font-semibold">₹ {{ number_format($r['sanctioned_rate'], 2) }}</td>
                                            <td class="p-2 text-right font-semibold text-slate-800">{{ number_format($r['sanctioned_qty']) }}</td>
                                            <td class="p-2 text-right font-mono font-bold text-slate-950">₹ {{ number_format($r['sanctioned_limit'], 2) }}</td>
                                            <td class="p-2 text-right font-semibold text-slate-800">{{ number_format($r['executed_qty']) }}</td>
                                            <td class="p-2 text-right font-mono font-bold text-blue-900">₹ {{ number_format($r['actual_amount'], 2) }}</td>
                                            <td class="p-2 text-right font-mono font-bold {{ $r['balance_amount'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                                ₹ {{ number_format($r['balance_amount'], 2) }}
                                            </td>
                                            <td class="p-2 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $r['badge_class'] }}">
                                                    {{ $r['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="p-8 text-center text-slate-500">No records found matching criteria.</td>
                                        </tr>
                                    @endforelse
                                @else
                                    @forelse($reportData['detailed_rows'] as $idx => $r)
                                        <tr class="hover:bg-slate-50/80 transition-colors {{ $r['is_violation'] ? 'bg-rose-50/40' : '' }}">
                                            <td class="p-2 text-center font-bold text-slate-700">{{ $idx + 1 }}</td>
                                            <td class="p-2">
                                                <strong class="text-slate-900 font-mono font-bold block">{{ $r['voucher_no'] }}</strong>
                                                <span class="text-[11px] text-slate-500">{{ $r['date'] }}</span>
                                            </td>
                                            <td class="p-2">
                                                <span class="font-medium text-slate-900 block leading-tight">{{ $r['party_name'] }}</span>
                                                <span class="text-[10px] text-slate-500 font-mono">{{ $r['round'] }} - {{ $r['beat'] }}</span>
                                            </td>
                                            <td class="p-2">
                                                <span class="inline-block font-mono text-[11px] font-bold text-emerald-900 bg-emerald-50 px-1 rounded">{{ $r['sor_code'] }}</span>
                                                <span class="text-slate-800 block text-[11px] mt-0.5 leading-snug">{{ $r['work_description'] }}</span>
                                            </td>
                                            <td class="p-2 text-center text-slate-600">{{ $r['unit'] }}</td>
                                            <td class="p-2 text-right font-mono text-slate-600">₹ {{ number_format($r['sanctioned_rate'], 2) }}</td>
                                            <td class="p-2 text-right font-mono font-bold {{ $r['actual_rate'] > $r['sanctioned_rate'] ? 'text-rose-700' : 'text-slate-900' }}">
                                                ₹ {{ number_format($r['actual_rate'], 2) }}
                                            </td>
                                            <td class="p-2 text-right font-mono font-semibold text-slate-800">₹ {{ number_format($r['sanctioned_limit'], 2) }}</td>
                                            <td class="p-2 text-right font-mono font-bold text-blue-900">₹ {{ number_format($r['actual_amount'], 2) }}</td>
                                            <td class="p-2 text-right font-mono font-bold {{ $r['variance'] < 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                                ₹ {{ number_format($r['variance'], 2) }}
                                            </td>
                                            <td class="p-2 text-center">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $r['badge_class'] }}">
                                                    {{ $r['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11" class="p-8 text-center text-slate-500">No records found matching criteria.</td>
                                        </tr>
                                    @endforelse
                                @endif
                            </tbody>
                            <tfoot class="sticky bottom-0 bg-slate-100 font-bold border-t-2 border-slate-900 text-slate-950">
                                @if($reportMode === 'summary')
                                    <tr>
                                        <td colspan="5" class="p-2.5 text-right uppercase">Grand Total (કુલ રકમ):</td>
                                        <td class="p-2.5 text-right font-mono font-black" id="tfootSanctionedLimit">₹ {{ number_format($reportData['totals']['total_sanctioned_limit'] ?? 0, 2) }}</td>
                                        <td></td>
                                        <td class="p-2.5 text-right font-mono font-black text-blue-950" id="tfootActualAmount">₹ {{ number_format($reportData['totals']['total_actual_expenditure'] ?? 0, 2) }}</td>
                                        <td class="p-2.5 text-right font-mono font-black text-emerald-950" id="tfootBalanceAmount">₹ {{ number_format($reportData['totals']['total_savings_balance'] ?? 0, 2) }}</td>
                                        <td></td>
                                    </tr>
                                @else
                                    <tr>
                                        <td colspan="7" class="p-2.5 text-right uppercase">Grand Total (કુલ રકમ):</td>
                                        <td class="p-2.5 text-right font-mono font-black" id="tfootSanctionedLimit">₹ {{ number_format($reportData['totals']['total_sanctioned_limit'] ?? 0, 2) }}</td>
                                        <td class="p-2.5 text-right font-mono font-black text-blue-950" id="tfootActualAmount">₹ {{ number_format($reportData['totals']['total_actual_expenditure'] ?? 0, 2) }}</td>
                                        <td class="p-2.5 text-right font-mono font-black text-emerald-950" id="tfootVariance">₹ {{ number_format($reportData['totals']['total_savings_balance'] ?? 0, 2) }}</td>
                                        <td></td>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>

                    <!-- Table Footer Note -->
                    <div class="p-3.5 bg-slate-50 border-t border-slate-200 text-xs text-slate-600 flex flex-col sm:flex-row items-center justify-between gap-2">
                        <span class="flex items-center gap-1.5">
                            <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                            <span>Verified under Gujarat Forest Department Schedule of Rates (SOR) ceiling guidelines.</span>
                        </span>
                        <span class="font-mono text-[11px] text-slate-500">Generated: {{ $reportData['generated_at'] }}</span>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Interactive Client Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const locations = @json($locationHierarchy);
            const moduleEl = document.getElementById('sorLimitReportModule');
            const previewUrl = moduleEl.dataset.previewUrl;
            const exportUrl = moduleEl.dataset.exportUrl;

            const roundSelect = document.getElementById('roundSelect');
            const beatSelect = document.getElementById('beatSelect');
            const placeSelect = document.getElementById('placeSelect');
            const budgetSelect = document.getElementById('budgetCodeSelect');
            const monthSelect = document.getElementById('monthSelect');
            const yearInput = document.getElementById('yearInput');
            const sorCodeSelect = document.getElementById('sorCodeSelect');
            const previewBtn = document.getElementById('previewSorBtn');
            const exportBtn = document.getElementById('exportCsvBtn');
            const alertBox = document.getElementById('sorAlertBox');
            const liveSearch = document.getElementById('liveTableSearch');

            // Location Cascade handler
            function updateBeatOptions() {
                const selRound = roundSelect.value;
                beatSelect.innerHTML = '<option value="">All Beats (બધી બીટ)</option>';
                placeSelect.innerHTML = '<option value="">All Plantation Sites (બધા સ્થળો)</option>';

                if (selRound && locations[selRound]) {
                    Object.keys(locations[selRound]).forEach(b => {
                        const opt = document.createElement('option');
                        opt.value = b;
                        opt.textContent = b;
                        beatSelect.appendChild(opt);
                    });
                }
            }

            function updatePlaceOptions() {
                const selRound = roundSelect.value;
                const selBeat = beatSelect.value;
                placeSelect.innerHTML = '<option value="">All Plantation Sites (બધા સ્થળો)</option>';

                if (selRound && selBeat && locations[selRound] && locations[selRound][selBeat]) {
                    locations[selRound][selBeat].forEach(p => {
                        const opt = document.createElement('option');
                        opt.value = p;
                        opt.textContent = p;
                        placeSelect.appendChild(opt);
                    });
                }
            }

            roundSelect.addEventListener('change', updateBeatOptions);
            beatSelect.addEventListener('change', updatePlaceOptions);

            // Update CSV Export URL parameters dynamically
            function updateExportUrl() {
                const reportMode = document.querySelector('input[name="report_mode"]:checked')?.value || 'detailed';
                const params = new URLSearchParams({
                    budget_code: budgetSelect.value,
                    month: monthSelect.value,
                    year: yearInput.value,
                    round: roundSelect.value,
                    beat: beatSelect.value,
                    place: placeSelect.value,
                    sor_code: sorCodeSelect.value,
                    report_mode: reportMode,
                });
                exportBtn.href = `${exportUrl}?${params.toString()}`;
            }

            [budgetSelect, monthSelect, yearInput, roundSelect, beatSelect, placeSelect, sorCodeSelect].forEach(el => {
                el.addEventListener('change', updateExportUrl);
            });
            document.querySelectorAll('input[name="report_mode"]').forEach(r => {
                r.addEventListener('change', updateExportUrl);
            });
            updateExportUrl();

            // Preview calculation via AJAX
            async function fetchPreview() {
                const reportMode = document.querySelector('input[name="report_mode"]:checked')?.value || 'detailed';
                previewBtn.disabled = true;
                previewBtn.classList.add('opacity-75');

                try {
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            budget_code: budgetSelect.value,
                            month: monthSelect.value,
                            year: yearInput.value,
                            round: roundSelect.value,
                            beat: beatSelect.value,
                            place: placeSelect.value,
                            sor_code: sorCodeSelect.value,
                            report_mode: reportMode,
                        }),
                    });

                    const res = await response.json();
                    if (res.success && res.data) {
                        renderTableData(res.data, reportMode);
                    }
                } catch (e) {
                    console.error('Failed to fetch preview:', e);
                } finally {
                    previewBtn.disabled = false;
                    previewBtn.classList.remove('opacity-75');
                }
            }

            previewBtn.addEventListener('click', fetchPreview);

            // Render updated data into DOM
            function renderTableData(data, reportMode) {
                // Update Stat cards
                document.getElementById('statSanctionedLimit').textContent = '₹ ' + formatNumber(data.totals.total_sanctioned_limit);
                document.getElementById('statActualExpenditure').textContent = '₹ ' + formatNumber(data.totals.total_actual_expenditure);
                document.getElementById('statSavingsBalance').textContent = '₹ ' + formatNumber(data.totals.total_savings_balance);
                document.getElementById('statComplianceRate').textContent = data.totals.compliance_rate + '%';
                
                const violEl = document.getElementById('statViolationsCount');
                violEl.textContent = data.totals.total_violations_count + ' rate violations';
                violEl.className = `text-[10px] ${data.totals.total_violations_count > 0 ? 'text-rose-600 font-bold' : 'text-emerald-700'} block`;

                // Update Table Header Title
                document.getElementById('tableHeaderTitle').textContent = reportMode === 'summary' ? 'Consolidated SOR Code Summary' : (reportMode === 'violations' ? 'SOR Rate & Limit Violation Alerts' : 'Detailed Item-Wise SOR Verification Statement');
                document.getElementById('tableHeaderSubtitle').textContent = `${data.budget_code} | ${data.month} | ${data.round}`;

                // Table Rows
                const tbody = document.getElementById('sorTableBody');
                tbody.innerHTML = '';

                if (reportMode === 'summary') {
                    if (!data.summary_rows || !data.summary_rows.length) {
                        tbody.innerHTML = '<tr><td colspan="10" class="p-8 text-center text-slate-500">No records found matching criteria.</td></tr>';
                        return;
                    }
                    data.summary_rows.forEach((r, idx) => {
                        const tr = document.createElement('tr');
                        tr.className = `hover:bg-slate-50/80 transition-colors ${r.balance_amount < 0 ? 'bg-rose-50/40' : ''}`;
                        tr.innerHTML = `
                            <td class="p-2 text-center font-bold text-slate-700">${idx + 1}</td>
                            <td class="p-2">
                                <strong class="text-slate-900 font-mono font-bold block">${r.sor_code}</strong>
                                <span class="text-slate-700">${r.work_description}</span>
                            </td>
                            <td class="p-2 text-center text-slate-600">${r.unit}</td>
                            <td class="p-2 text-right font-mono font-semibold">₹ ${formatNumber(r.sanctioned_rate)}</td>
                            <td class="p-2 text-right font-semibold text-slate-800">${r.sanctioned_qty.toLocaleString()}</td>
                            <td class="p-2 text-right font-mono font-bold text-slate-950">₹ ${formatNumber(r.sanctioned_limit)}</td>
                            <td class="p-2 text-right font-semibold text-slate-800">${r.executed_qty.toLocaleString()}</td>
                            <td class="p-2 text-right font-mono font-bold text-blue-900">₹ ${formatNumber(r.actual_amount)}</td>
                            <td class="p-2 text-right font-mono font-bold ${r.balance_amount < 0 ? 'text-rose-700' : 'text-emerald-700'}">
                                ₹ ${formatNumber(r.balance_amount)}
                            </td>
                            <td class="p-2 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border ${r.badge_class}">
                                    ${r.status}
                                </span>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    if (!data.detailed_rows || !data.detailed_rows.length) {
                        tbody.innerHTML = '<tr><td colspan="11" class="p-8 text-center text-slate-500">No records found matching criteria.</td></tr>';
                        return;
                    }
                    data.detailed_rows.forEach((r, idx) => {
                        const tr = document.createElement('tr');
                        tr.className = `hover:bg-slate-50/80 transition-colors ${r.is_violation ? 'bg-rose-50/40' : ''}`;
                        tr.innerHTML = `
                            <td class="p-2 text-center font-bold text-slate-700">${idx + 1}</td>
                            <td class="p-2">
                                <strong class="text-slate-900 font-mono font-bold block">${r.voucher_no}</strong>
                                <span class="text-[11px] text-slate-500">${r.date}</span>
                            </td>
                            <td class="p-2">
                                <span class="font-medium text-slate-900 block leading-tight">${r.party_name}</span>
                                <span class="text-[10px] text-slate-500 font-mono">${r.round} - ${r.beat}</span>
                            </td>
                            <td class="p-2">
                                <span class="inline-block font-mono text-[11px] font-bold text-emerald-900 bg-emerald-50 px-1 rounded">${r.sor_code}</span>
                                <span class="text-slate-800 block text-[11px] mt-0.5 leading-snug">${r.work_description}</span>
                            </td>
                            <td class="p-2 text-center text-slate-600">${r.unit}</td>
                            <td class="p-2 text-right font-mono text-slate-600">₹ ${formatNumber(r.sanctioned_rate)}</td>
                            <td class="p-2 text-right font-mono font-bold ${r.actual_rate > r.sanctioned_rate ? 'text-rose-700' : 'text-slate-900'}">
                                ₹ ${formatNumber(r.actual_rate)}
                            </td>
                            <td class="p-2 text-right font-mono font-semibold text-slate-800">₹ ${formatNumber(r.sanctioned_limit)}</td>
                            <td class="p-2 text-right font-mono font-bold text-blue-900">₹ ${formatNumber(r.actual_amount)}</td>
                            <td class="p-2 text-right font-mono font-bold ${r.variance < 0 ? 'text-rose-700' : 'text-emerald-700'}">
                                ₹ ${formatNumber(r.variance)}
                            </td>
                            <td class="p-2 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border ${r.badge_class}">
                                    ${r.status}
                                </span>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }
            }

            function formatNumber(num) {
                return (num || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // Live search in table rows
            liveSearch.addEventListener('input', function () {
                const val = this.value.toLowerCase().trim();
                const rows = document.querySelectorAll('#sorTableBody tr');
                rows.forEach(r => {
                    r.style.display = r.textContent.toLowerCase().includes(val) ? '' : 'none';
                });
            });
        });
    </script>
</x-layouts.admin>
