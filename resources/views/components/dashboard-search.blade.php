@props(['user'])

@php
    $role = $user->role ?? 'division';
    
    // Build comprehensive search catalog based on user role
    $catalog = [];

    if ($role === 'division') {
        $catalog = [
            // Reports Suite
            [
                'title' => 'Monthly Reports (Form 53 & Form D-36)',
                'title_gu' => 'માસિક હિસાબ પત્રક (ફોર્મ નં. ૫૩ / ડી-૩૬)',
                'category' => 'Statutory Reports',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'icon' => 'document-report',
                'url' => route('division.monthly-reports.index'),
                'description' => 'Demand 26, 95, 96 Form D-36 Abstract, Detailed Form 53, and Treasury Reconciliation',
                'keywords' => 'monthly report form 53 form 36 d36 d-36 abstract demand 26 95 96 revenue capital reconciliation masik patrak',
            ],
            [
                'title' => 'Summary Reports (Grant & Scheme Realization)',
                'title_gu' => 'ગ્રાન્ટ સમરી રિપોર્ટ્સ (૧૦-કોલમ ગોશવારો)',
                'category' => 'Statutory Reports',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'icon' => 'table',
                'url' => route('division.summary-reports.index'),
                'description' => '10-column grant vs expenditure abstract, merged salary summary, and LC-only wage report',
                'keywords' => 'summary report 10-column matrix grant realization budget allocation salary merged only lc goshvaro',
            ],
            [
                'title' => 'Final Voucher Nos, Form No. 35 & Cashbook',
                'title_gu' => 'ફાઇનલ વાઉચર નંબરો, ફોર્મ નં. ૩૫ અને કેશબુક',
                'category' => 'Statutory Reports',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'icon' => 'book-open',
                'url' => route('division.final-voucher-cashbook.index'),
                'description' => 'Assign final voucher numbers, generate Form-35, full Cashbook, Debit/Credit parts, A4 Title & A5 Sticker',
                'keywords' => 'final voucher form 35 form35 cashbook rokad credit debit title sticker certificate range merge schemes',
            ],
            [
                'title' => 'Bill / Advice Reports (Form 63 & DBT)',
                'title_gu' => 'બિલ / એડવાઇસ રિપોર્ટ્સ (ફોર્મ ૬૩ અને ડીબીટી)',
                'category' => 'Statutory Reports',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'icon' => 'currency-rupee',
                'url' => route('division.bill-advice-reports.index'),
                'description' => 'Form 63 electronic payments, CMP bank advices, DBT beneficiary lists, and muster wage payslips',
                'keywords' => 'bill advice report form 63 form63 dbt beneficiary cmp bank daily wager payslip muster',
            ],
            
            // Finance Entries & Operations
            [
                'title' => 'Process Bill (Advice)',
                'title_gu' => 'બિલ પ્રોસેસ કરો (એડવાઇસ)',
                'category' => 'Finance Operations',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'calculator',
                'url' => route('division.process-bill.index'),
                'description' => 'Generate and process division payment bills and CMP bank advices',
                'keywords' => 'process bill advice cmp payment generate advice voucher create bill',
            ],
            [
                'title' => 'Update GST Challan No.',
                'title_gu' => 'જીએસટી ચલણ નંબર અપડેટ કરો',
                'category' => 'Finance Operations',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'receipt-tax',
                'url' => route('division.gst-challan.index'),
                'description' => 'Update and manage GST TDS challan numbers and tax inward registers',
                'keywords' => 'gst challan update gst cgst sgst igst tax challan number',
            ],
            [
                'title' => 'Add Treasury Details',
                'title_gu' => 'ટ્રેઝરી વિગતો ઉમેરો',
                'category' => 'Finance Operations',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'office-building',
                'url' => route('division.treasury-details.index'),
                'description' => 'Record treasury tokens, bill numbers, and pass dates for approved advices',
                'keywords' => 'treasury details token no token date pass date treasury bill',
            ],
            [
                'title' => 'Change Bill No. and Order No.',
                'title_gu' => 'બિલ નંબર અને ઓર્ડર નંબર બદલો',
                'category' => 'Finance Operations',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'adjustments',
                'url' => route('division.change-bill-order-no.index'),
                'description' => 'Adjust sequence, serial ordering, and bill numbers for payment advices',
                'keywords' => 'change bill no order no reorder sequence priority advice bill number',
            ],
            [
                'title' => 'Delete Advice',
                'title_gu' => 'એડવાઇસ ડીલીટ કરો',
                'category' => 'Finance Operations',
                'badge_color' => 'bg-rose-100 text-rose-800',
                'icon' => 'trash',
                'url' => route('division.delete-advice.index'),
                'description' => 'Remove or cancel un-cleared payment advices and restore associated voucher batches',
                'keywords' => 'delete advice cancel advice remove bill discard advice',
            ],
            [
                'title' => 'Party Registration (Division)',
                'title_gu' => 'પાર્ટી રજીસ્ટ્રેશન (વિભાગ)',
                'category' => 'Masters & Entries',
                'badge_color' => 'bg-amber-100 text-amber-800',
                'icon' => 'user-group',
                'url' => route('division.parties.index'),
                'description' => 'Manage contractors, vendors, bank accounts, PAN, and GSTIN details for division payments',
                'keywords' => 'party registration contractor vendor bank ifsc pan gstin division tender party',
            ],
            [
                'title' => 'Cash Account Registration',
                'title_gu' => 'કેશ એકાઉન્ટ રજીસ્ટ્રેશન',
                'category' => 'Masters & Entries',
                'badge_color' => 'bg-amber-100 text-amber-800',
                'icon' => 'banknotes',
                'url' => route('division.cash-accounts.index'),
                'description' => 'Division bank cash accounts, drawing limits, and account ledgers',
                'keywords' => 'cash account bank account division drawing limit bank master',
            ],
            [
                'title' => 'Allotment From Circle',
                'title_gu' => 'સર્કલ તરફથી ગ્રાન્ટ ફાળવણી',
                'category' => 'Budget & Grants',
                'badge_color' => 'bg-purple-100 text-purple-800',
                'icon' => 'arrow-down-tray',
                'url' => route('division.allotments-from-circle.index'),
                'description' => 'Record sanctioned grant and allotment letters received from Circle Conservator',
                'keywords' => 'allotment circle grant received budget code sanction letter',
            ],
            [
                'title' => 'Allotment To Range',
                'title_gu' => 'રેન્જને ગ્રાન્ટ ફાળવણી',
                'category' => 'Budget & Grants',
                'badge_color' => 'bg-purple-100 text-purple-800',
                'icon' => 'arrow-up-tray',
                'url' => route('division.allotments-to-range.create'),
                'description' => 'Distribute budget grants and financial targets to subordinate ranges',
                'keywords' => 'allotment range distribute budget grant allocate to range',
            ],
            [
                'title' => 'Allotment Adjustment',
                'title_gu' => 'ગ્રાન્ટ ફાળવણી એડજસ્ટમેન્ટ',
                'category' => 'Budget & Grants',
                'badge_color' => 'bg-purple-100 text-purple-800',
                'icon' => 'switch-horizontal',
                'url' => route('division.allotment-adjustments.index'),
                'description' => 'Transfer or adjust budget allocations between ranges and subheads',
                'keywords' => 'allotment adjustment re-appropriation transfer grant adjust budget',
            ],
            [
                'title' => 'LC (Letter of Credit) Entry',
                'title_gu' => 'એલ.સી. એન્ટ્રી (Letter of Credit)',
                'category' => 'Budget & Grants',
                'badge_color' => 'bg-purple-100 text-purple-800',
                'icon' => 'credit-card',
                'url' => route('division.lc-entries.index'),
                'description' => 'Record and track Treasury Letter of Credit drawing authorizations',
                'keywords' => 'lc entry letter of credit drawing authorization treasury lc',
            ],
            
            // Administration & Settings
            [
                'title' => 'Office Profile, Contact Details & Logo Settings',
                'title_gu' => 'કચેરી પ્રોફાઇલ, સંપર્ક વિગતો અને લોગો સેટિંગ્સ',
                'category' => 'Administrative Settings',
                'badge_color' => 'bg-teal-100 text-teal-800',
                'icon' => 'cog',
                'url' => route('office-profile.edit'),
                'description' => 'Configure division address, officer name, official designations, contact numbers, and upload official logo',
                'keywords' => 'office profile letterhead address officer name designation phone email logo upload contact details ddo tan',
            ],
            [
                'title' => 'Range User Accounts Management',
                'title_gu' => 'રેન્જ યુઝર એકાઉન્ટ મેનેજમેન્ટ',
                'category' => 'Administrative Settings',
                'badge_color' => 'bg-teal-100 text-teal-800',
                'icon' => 'users',
                'url' => route('ranges.index'),
                'description' => 'Create, inspect, and manage range login credentials under this division',
                'keywords' => 'range accounts users logins range password manage ranges',
            ],
            [
                'title' => 'Range Geographic Locations',
                'title_gu' => 'રેન્જ ભૌગોલિક વિસ્તારો અને લોકેશન્સ',
                'category' => 'Administrative Settings',
                'badge_color' => 'bg-teal-100 text-teal-800',
                'icon' => 'map-pin',
                'url' => route('range-locations.index'),
                'description' => 'Maintain talukas, rounds, beats, and plantation site locations',
                'keywords' => 'range locations rounds beats talukas villages sites plantations',
            ],
            [
                'title' => 'Change Account Password',
                'title_gu' => 'પાસવર્ડ બદલો',
                'category' => 'Administrative Settings',
                'badge_color' => 'bg-slate-100 text-slate-800',
                'icon' => 'key',
                'url' => route('password.edit'),
                'description' => 'Update login password and security settings',
                'keywords' => 'password change reset password credentials security',
            ],
        ];
    } elseif ($role === 'range') {
        $catalog = [
            // Prints & Reports
            [
                'title' => 'Voucher Print (Form No. 35)',
                'title_gu' => 'વાઉચર પ્રિન્ટ (ફોર્મ નં. ૩૫)',
                'category' => 'Range Reports',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'icon' => 'printer',
                'url' => route('finance.voucher-print.index'),
                'description' => 'Print Form-35 statutory voucher sheets with items, deductions, and contractor certificates',
                'keywords' => 'voucher print form 35 form35 print range voucher bill print certificate',
            ],
            [
                'title' => 'Abstract Print (Summary & Detailed)',
                'title_gu' => 'એબ્સ્ટ્રેક્ટ પ્રિન્ટ (સમરી અને વિગતવાર)',
                'category' => 'Range Reports',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'icon' => 'document-text',
                'url' => route('finance.abstract-print.index'),
                'description' => 'Print range expenditure docket abstracts by model, subhead, and object classes',
                'keywords' => 'abstract print docket summary detailed model subhead rate qty amount',
            ],
            [
                'title' => 'Work Order Print',
                'title_gu' => 'વર્ક ઓર્ડર પ્રિન્ટ',
                'category' => 'Range Reports',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'icon' => 'clipboard-document-check',
                'url' => route('finance.work-order-print.index'),
                'description' => 'Generate and print formal work orders for contractors, labours, and nursery works',
                'keywords' => 'work order print hukam nama sanstion order contractor work order',
            ],
            [
                'title' => 'SOR Limit Report',
                'title_gu' => 'એસ.ઓ.આર. લિમિટ રિપોર્ટ (SOR Limit Report)',
                'category' => 'Range Reports',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'icon' => 'calculator',
                'url' => route('finance.sor-limit-report.index'),
                'description' => 'Schedule of Rates (SOR) limit verification, rate compliance, ceiling limits, and expenditure monitoring',
                'keywords' => 'sor limit report schedule of rates rate limit ceiling violation variance savings sor-01 pitting plantation',
            ],
            [
                'title' => 'Vavetar Plantation Register',
                'title_gu' => 'વાવેતર રજીસ્ટર (તારીખવાર અને કામવાર)',
                'category' => 'Range Reports',
                'badge_color' => 'bg-emerald-100 text-emerald-800',
                'icon' => 'sparkles',
                'url' => route('finance.vavetar-register.index'),
                'description' => 'Comprehensive date-wise and work-wise plantation log, targets, and progressive achievements',
                'keywords' => 'vavetar register plantation register date wise work wise model achievement plants',
            ],

            // Range Entries
            [
                'title' => 'Tender Entry',
                'title_gu' => 'ટેન્ડર એન્ટ્રી',
                'category' => 'Voucher Entries',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'document-check',
                'url' => route('finance.tender-entries.index'),
                'description' => 'Record tender-based contractor vouchers, deductions, and items',
                'keywords' => 'tender entry voucher contractor items tender work',
            ],
            [
                'title' => 'Free Entry',
                'title_gu' => 'ફ્રી એન્ટ્રી (સામાન્ય ખર્ચ)',
                'category' => 'Voucher Entries',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'pencil-square',
                'url' => route('finance.free-entries.index'),
                'description' => 'General petty expenses, purchase vouchers, and miscellaneous entries',
                'keywords' => 'free entry general expense petty cash purchases voucher',
            ],
            [
                'title' => 'Daily Wagers Salary Entry',
                'title_gu' => 'રોજમદાર પગાર એન્ટ્રી',
                'category' => 'Voucher Entries',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'currency-rupee',
                'url' => route('finance.d-wager-salary-entries.index'),
                'description' => 'Muster roll attendance, daily wage salary sheets, and bank credits',
                'keywords' => 'daily wager salary muster roll rojamdar pagar wages labour',
            ],
            [
                'title' => 'Daily Wagers Arrears Entry',
                'title_gu' => 'રોજમદાર એરિયર્સ એન્ટ્રી',
                'category' => 'Voucher Entries',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'clock',
                'url' => route('finance.d-wager-arrears-entries.index'),
                'description' => 'Arrears calculation and wage revision disbursals for daily wagers',
                'keywords' => 'daily wager arrears rojamdar arrears wage revision pay difference',
            ],
            [
                'title' => 'WL Beneficiary Entry (Wildlife)',
                'title_gu' => 'ડબલ્યુ.એલ. લાભાર્થી એન્ટ્રી (વન્યજીવ)',
                'category' => 'Voucher Entries',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'user-plus',
                'url' => route('finance.wl-beneficiary-entries.index'),
                'description' => 'Wildlife scheme beneficiary payment entries and subsidies',
                'keywords' => 'wl beneficiary entry wildlife animal damage crop compensation',
            ],
            [
                'title' => 'SF Beneficiary Entry (Social Forestry)',
                'title_gu' => 'એસ.એફ. લાભાર્થી એન્ટ્રી (સામાજિક વનીકરણ)',
                'category' => 'Voucher Entries',
                'badge_color' => 'bg-blue-100 text-blue-800',
                'icon' => 'user-plus',
                'url' => route('finance.sf-beneficiary-entries.index'),
                'description' => 'Social forestry scheme beneficiaries, saplings, and farmer subsidies',
                'keywords' => 'sf beneficiary entry social forestry kisan nursery plantation subsidy',
            ],

            // Registrations
            [
                'title' => 'Party Registration (Range)',
                'title_gu' => 'પાર્ટી રજીસ્ટ્રેશન (રેન્જ)',
                'category' => 'Registrations',
                'badge_color' => 'bg-amber-100 text-amber-800',
                'icon' => 'user-group',
                'url' => route('finance.registration.parties.index'),
                'description' => 'Register local contractors, suppliers, and labor groups with bank details',
                'keywords' => 'party registration range contractor vendor ifsc bank pan',
            ],
            [
                'title' => 'WL Beneficiary Registration',
                'title_gu' => 'ડબલ્યુ.એલ. લાભાર્થી રજીસ્ટ્રેશન',
                'category' => 'Registrations',
                'badge_color' => 'bg-amber-100 text-amber-800',
                'icon' => 'identification',
                'url' => route('finance.registration.wl-beneficiaries.index'),
                'description' => 'Maintain registry of wildlife beneficiaries, Aadhaar, bank accounts, and photos',
                'keywords' => 'wl beneficiary registration wildlife master list',
            ],
            [
                'title' => 'SF Beneficiary Registration',
                'title_gu' => 'એસ.એફ. લાભાર્થી રજીસ્ટ્રેશન',
                'category' => 'Registrations',
                'badge_color' => 'bg-amber-100 text-amber-800',
                'icon' => 'identification',
                'url' => route('finance.registration.sf-beneficiaries.index'),
                'description' => 'Register social forestry farmers, nursery holders, and land survey details',
                'keywords' => 'sf beneficiary registration farmer master list survey number',
            ],

            // Settings
            [
                'title' => 'Range Office Profile, Officer Details & Logo',
                'title_gu' => 'રેન્જ કચેરી પ્રોફાઇલ, સંપર્ક વિગતો અને લોગો સેટિંગ્સ',
                'category' => 'Administrative Settings',
                'badge_color' => 'bg-teal-100 text-teal-800',
                'icon' => 'cog',
                'url' => route('office-profile.edit'),
                'description' => 'Configure range office address, RFO officer name, contact numbers, and range logo',
                'keywords' => 'range office profile address rfo officer name designation phone mobile logo letterhead',
            ],
            [
                'title' => 'Change Account Password',
                'title_gu' => 'પાસવર્ડ બદલો',
                'category' => 'Administrative Settings',
                'badge_color' => 'bg-slate-100 text-slate-800',
                'icon' => 'key',
                'url' => route('password.edit'),
                'description' => 'Update range login password and security settings',
                'keywords' => 'password change reset password credentials security',
            ],
        ];
    } else {
        // Admin
        $catalog = [
            [
                'title' => 'Division Accounts Administration',
                'title_gu' => 'વિભાગ એકાઉન્ટ્સ વહીવટ',
                'category' => 'Admin Controls',
                'badge_color' => 'bg-indigo-100 text-indigo-800',
                'icon' => 'office-building',
                'url' => route('admin.divisions.index'),
                'description' => 'Create and configure division user credentials and permissions',
                'keywords' => 'divisions admin manage division accounts users create division',
            ],
            [
                'title' => 'Budget Codes Master',
                'title_gu' => 'બજેટ કોડ માસ્ટર',
                'category' => 'Admin Controls',
                'badge_color' => 'bg-indigo-100 text-indigo-800',
                'icon' => 'table',
                'url' => route('admin.budget-codes.index'),
                'description' => 'Maintain 14-digit state budget codes, demand heads, schemes, and object classes',
                'keywords' => 'budget codes master demands 2406 4406 subheads object classes',
            ],
            [
                'title' => 'Office Profile & Logo',
                'title_gu' => 'ઓફિસ પ્રોફાઇલ અને લોગો',
                'category' => 'Admin Controls',
                'badge_color' => 'bg-teal-100 text-teal-800',
                'icon' => 'cog',
                'url' => route('office-profile.edit'),
                'description' => 'Configure administration contact details and default emblems',
                'keywords' => 'office profile address logo contact admin',
            ],
            [
                'title' => 'Change Password',
                'title_gu' => 'પાસવર્ડ બદલો',
                'category' => 'Admin Controls',
                'badge_color' => 'bg-slate-100 text-slate-800',
                'icon' => 'key',
                'url' => route('password.edit'),
                'description' => 'Update administrator account credentials',
                'keywords' => 'password change reset admin credentials',
            ],
        ];
    }
@endphp

<div id="omniSearchWrapper" class="space-y-4" data-catalog='@json($catalog)'>
    
    <!-- Search Bar Card -->
    <div class="rounded-2xl border-2 border-emerald-500/40 bg-gradient-to-r from-emerald-900 via-slate-900 to-emerald-950 p-4 sm:p-6 text-white shadow-xl">
        <div class="max-w-4xl mx-auto space-y-3">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div class="space-y-0.5">
                    <h2 class="text-base sm:text-lg font-black tracking-tight text-white flex items-center gap-2">
                        <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                        </svg>
                        <span>Quick Search &amp; Fast Navigation</span>
                    </h2>
                    <p class="text-xs text-emerald-200/80">
                        Type any form, menu, monthly report, voucher, register, party or setting to jump instantly.
                    </p>
                </div>
                <div class="hidden sm:flex items-center gap-1.5 text-[11px] font-semibold text-emerald-300 bg-white/10 px-2.5 py-1 rounded-full border border-white/10">
                    <kbd class="px-1.5 py-0.5 bg-black/40 rounded text-[10px] font-mono text-emerald-200">Ctrl</kbd> + <kbd class="px-1.5 py-0.5 bg-black/40 rounded text-[10px] font-mono text-emerald-200">K</kbd> or <kbd class="px-1.5 py-0.5 bg-black/40 rounded text-[10px] font-mono text-emerald-200">/</kbd>
                </div>
            </div>

            <!-- Search Input Box -->
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                    <svg class="h-5 w-5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                    </svg>
                </div>
                <input type="text" id="omniSearchInput"
                    placeholder="Search anything: 'Monthly report', 'Cashbook', 'Form 35', 'Salary', 'Tender', 'Party', 'Logo', 'Address'..."
                    autocomplete="off"
                    class="w-full rounded-xl border-2 border-emerald-400/60 bg-white py-3 pl-11 pr-24 text-sm font-semibold text-slate-900 placeholder:text-slate-400 shadow-inner focus:border-white focus:outline-none focus:ring-4 focus:ring-emerald-500/40 transition-all">
                
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                    <button type="button" id="omniClearBtn" class="hidden text-slate-400 hover:text-slate-600 p-1 text-xs font-bold rounded">
                        ✕ Clear
                    </button>
                    <span id="searchResultCount" class="hidden text-[11px] font-bold bg-emerald-100 text-emerald-900 px-2 py-0.5 rounded-md">
                        0 found
                    </span>
                </div>
            </div>

            <!-- Quick Filter Chips -->
            <div class="flex flex-wrap items-center gap-1.5 pt-1 text-xs font-semibold">
                <span class="text-[11px] text-emerald-300 font-bold mr-1">Quick Filters:</span>
                <button type="button" class="quick-chip active" data-filter="all">All</button>
                @if($role === 'division')
                    <button type="button" class="quick-chip" data-filter="Statutory Reports">📊 Reports</button>
                    <button type="button" class="quick-chip" data-filter="Finance Operations">⚡ Operations</button>
                    <button type="button" class="quick-chip" data-filter="Budget & Grants">💰 Grants</button>
                    <button type="button" class="quick-chip" data-filter="Masters & Entries">📝 Masters</button>
                @elseif($role === 'range')
                    <button type="button" class="quick-chip" data-filter="Range Reports">🖨️ Prints &amp; Reports</button>
                    <button type="button" class="quick-chip" data-filter="Voucher Entries">📝 Voucher Entries</button>
                    <button type="button" class="quick-chip" data-filter="Registrations">👥 Registrations</button>
                @endif
                <button type="button" class="quick-chip" data-filter="Administrative Settings">⚙️ Settings &amp; Logo</button>
            </div>

        </div>
    </div>

    <!-- Live Search Results Container -->
    <div id="searchResultsCard" class="hidden rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-md transition-all">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800 flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                <span id="searchResultsTitle">Matching Forms, Menus &amp; Reports</span>
            </h3>
            <span class="text-xs text-slate-500 font-medium">Use <kbd class="px-1 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono text-[10px]">↑</kbd> <kbd class="px-1 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono text-[10px]">↓</kbd> to navigate, <kbd class="px-1 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono text-[10px]">Enter</kbd> to open</span>
        </div>

        <div id="searchResultsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <!-- Dynamically populated -->
        </div>

        <!-- No Results Fallback -->
        <div id="searchNoResults" class="hidden py-8 text-center space-y-2">
            <div class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M8 11h6"/>
                </svg>
            </div>
            <p class="text-sm font-bold text-slate-800">No matching items found for your search query.</p>
            <p class="text-xs text-slate-500">Try searching with other keywords like "report", "voucher", "cashbook", "party", "salary", "logo", "profile".</p>
        </div>
    </div>

</div>

<style>
    .quick-chip {
        padding: 0.25rem 0.65rem !important;
        border-radius: 9999px !important;
        background-color: rgba(255, 255, 255, 0.12) !important;
        color: #d1fae5 !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        transition: all 0.15s ease-in-out !important;
        cursor: pointer !important;
    }
    .quick-chip:hover {
        background-color: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
        transform: translateY(-1px) !important;
    }
    .quick-chip.active {
        background-color: #10b981 !important;
        color: #064e3b !important;
        font-weight: 800 !important;
        border-color: #34d399 !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.2) !important;
    }
    .search-result-item {
        border: 1.5px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.875rem;
        background-color: #ffffff;
        transition: all 0.15s ease-in-out;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .search-result-item:hover, .search-result-item.selected {
        border-color: #059669;
        background-color: #f0fdf4;
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const wrapper = document.getElementById('omniSearchWrapper');
        if (!wrapper) return;

        const catalog = JSON.parse(wrapper.dataset.catalog || '[]');
        const searchInput = document.getElementById('omniSearchInput');
        const clearBtn = document.getElementById('omniClearBtn');
        const resultsCard = document.getElementById('searchResultsCard');
        const resultsList = document.getElementById('searchResultsList');
        const countBadge = document.getElementById('searchResultCount');
        const noResults = document.getElementById('searchNoResults');
        const chips = document.querySelectorAll('.quick-chip');

        let activeCategory = 'all';
        let selectedIndex = -1;

        // Global hotkeys (Ctrl+K or /)
        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey && e.key === 'k') || (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA')) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });

        function renderResults() {
            const query = searchInput.value.trim().toLowerCase();

            if (!query && activeCategory === 'all') {
                resultsCard.classList.add('hidden');
                clearBtn.classList.add('hidden');
                countBadge.classList.add('hidden');
                return;
            }

            clearBtn.classList.remove('hidden');

            const filtered = catalog.filter(item => {
                const matchesCategory = (activeCategory === 'all') || (item.category === activeCategory);
                if (!matchesCategory) return false;

                if (!query) return true;

                const textToSearch = `${item.title} ${item.title_gu || ''} ${item.description || ''} ${item.keywords || ''} ${item.category}`.toLowerCase();
                const terms = query.split(' ').filter(Boolean);
                return terms.every(term => textToSearch.includes(term));
            });

            resultsCard.classList.remove('hidden');
            countBadge.classList.remove('hidden');
            countBadge.textContent = `${filtered.length} found`;

            if (filtered.length === 0) {
                resultsList.innerHTML = '';
                noResults.classList.remove('hidden');
                return;
            }

            noResults.classList.add('hidden');

            resultsList.innerHTML = filtered.map((item, idx) => `
                <a href="${item.url}" class="search-result-item group ${idx === selectedIndex ? 'selected' : ''}" data-index="${idx}">
                    <div class="space-y-1.5">
                        <div class="flex items-start justify-between gap-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold ${item.badge_color}">
                                ${item.category}
                            </span>
                            <span class="text-xs text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity font-bold">
                                Open &rarr;
                            </span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-emerald-950 leading-snug">
                            ${highlightText(item.title, query)}
                        </h4>
                        ${item.title_gu ? `<p class="text-xs font-semibold text-emerald-800 leading-snug">${item.title_gu}</p>` : ''}
                        <p class="text-[11px] text-slate-600 leading-relaxed">
                            ${item.description}
                        </p>
                    </div>
                </a>
            `).join('');
        }

        function highlightText(text, query) {
            if (!query) return text;
            const terms = query.split(' ').filter(Boolean);
            let result = text;
            terms.forEach(term => {
                const regex = new RegExp(`(${term})`, 'gi');
                result = result.replace(regex, '<mark class="bg-yellow-200 text-slate-900 font-bold px-0.5 rounded">$1</mark>');
            });
            return result;
        }

        searchInput.addEventListener('input', function () {
            selectedIndex = -1;
            renderResults();
        });

        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            selectedIndex = -1;
            renderResults();
            searchInput.focus();
        });

        // Quick Filter Chips Handling
        chips.forEach(chip => {
            chip.addEventListener('click', function () {
                chips.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                activeCategory = this.dataset.filter;
                selectedIndex = -1;
                renderResults();
            });
        });

        // Keyboard navigation across results
        searchInput.addEventListener('keydown', function (e) {
            const items = resultsList.querySelectorAll('.search-result-item');
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateSelection(items);
            } else if (e.key === 'Enter') {
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    e.preventDefault();
                    items[selectedIndex].click();
                }
            } else if (e.key === 'Escape') {
                searchInput.value = '';
                renderResults();
            }
        });

        function updateSelection(items) {
            items.forEach((it, idx) => {
                if (idx === selectedIndex) {
                    it.classList.add('selected');
                    it.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                } else {
                    it.classList.remove('selected');
                }
            });
        }
    });
</script>
