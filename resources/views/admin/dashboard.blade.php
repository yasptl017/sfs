<x-layouts.admin title="Dashboard | Forest Inventory" heading="Forest Department Dashboard" subheading="Comprehensive Management &amp; Financial Reporting Control">
    <div class="space-y-6">

        <!-- 1. Global Omni-Search Component -->
        <x-dashboard-search :user="$user" />

        <!-- 2. Office Profile & Official Letterhead Banner Card -->
        <div class="rounded-xl border border-emerald-200 bg-gradient-to-r from-emerald-50 via-white to-stone-50 p-5 shadow-sm">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 rounded-xl border-2 border-emerald-600/30 bg-white p-1 flex items-center justify-center shadow-sm shrink-0 overflow-hidden">
                        <img src="{{ $profile->logo_url }}" alt="Office Logo" class="max-h-full max-w-full object-contain">
                    </div>
                    <div class="space-y-0.5 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-base sm:text-lg font-black text-slate-950 truncate">
                                {{ $profile->display_name }}
                            </h2>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $profile->hasCustomLogo() ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $profile->hasCustomLogo() ? 'Custom Logo' : 'Official Emblem' }}
                            </span>
                        </div>
                        <p class="text-xs font-bold text-emerald-900">
                            {{ $profile->display_name_gujarati }}
                        </p>
                        <p class="text-xs text-slate-600 flex items-center gap-3 flex-wrap pt-0.5">
                            @if($profile->formatted_address)
                                <span class="flex items-center gap-1">
                                    <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" /></svg>
                                    {{ $profile->formatted_address }}
                                </span>
                            @endif
                            @if($profile->officer_name)
                                <span class="font-semibold text-slate-900">
                                    • Officer: <strong>{{ $profile->officer_name }}</strong> ({{ $profile->display_designation }})
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="shrink-0">
                    <a href="{{ route('office-profile.edit') }}" class="secondary-button text-xs font-bold py-2 px-3.5 flex items-center gap-1.5 shadow-sm">
                        <svg class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        <span>Edit Profile &amp; Logo</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- 3. Key Metrics & Stat Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="stat-card">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Active Account</p>
                <p class="mt-2 text-xl font-bold text-slate-950 truncate">{{ $user->name }}</p>
                <p class="mt-1 text-xs font-semibold capitalize text-emerald-700">{{ $user->role }} level access</p>
            </div>

            @if($user->isDivision())
                <div class="stat-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Ranges Under Division</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">{{ $rangeCount }}</p>
                    <p class="mt-1 text-xs text-slate-500">Active subordinate range logins</p>
                </div>
            @elseif($user->isAdmin())
                <div class="stat-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Access Level</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">System Admin</p>
                    <p class="mt-1 text-xs text-slate-500">Division account management</p>
                </div>
            @else
                <div class="stat-card">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Access Level</p>
                    <p class="mt-2 text-2xl font-black text-slate-950">Range User</p>
                    <p class="mt-1 text-xs text-slate-500">Finance &amp; plantation operations</p>
                </div>
            @endif

            <div class="stat-card">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Reporting Status</p>
                <p class="mt-2 text-xl font-bold text-emerald-700">5 Suites Active</p>
                <p class="mt-1 text-xs text-slate-500">40 statutory formats supported</p>
            </div>

            <div class="stat-card">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">System Divisions</p>
                <p class="mt-2 text-2xl font-black text-slate-950">{{ $divisionCount }}</p>
                <p class="mt-1 text-xs text-slate-500">Administrative division units</p>
            </div>
        </div>

        <!-- 4. Quick Action Report Cards Grid -->
        <div class="space-y-3">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                ⚡ Quick Access Reporting &amp; Operations Suites
            </h3>

            @if($user->isDivision())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="{{ route('division.monthly-reports.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-emerald-600 hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between pb-2">
                            <span class="p-2 rounded-lg bg-emerald-50 text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5"/><path d="M4 6h12l-1 4 1 4H4"/><path d="M8 19h12"/></svg>
                            </span>
                            <span class="text-xs font-bold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">Open &rarr;</span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-emerald-950">Monthly Reports (Form 53 &amp; D-36)</h4>
                        <p class="text-xs text-slate-500 mt-1">6 Demand groups, progressive totals &amp; treasury reconciliation</p>
                    </a>

                    <a href="{{ route('division.summary-reports.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-blue-600 hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between pb-2">
                            <span class="p-2 rounded-lg bg-blue-50 text-blue-700 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18 9l-5 5-4-4-3 3"/></svg>
                            </span>
                            <span class="text-xs font-bold text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">Open &rarr;</span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-blue-950">Grant Summary (10-Col Matrix)</h4>
                        <p class="text-xs text-slate-500 mt-1">Sanctioned vs Released vs Progressive expenditure abstract</p>
                    </a>

                    <a href="{{ route('division.final-voucher-cashbook.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-600 hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between pb-2">
                            <span class="p-2 rounded-lg bg-amber-50 text-amber-700 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg>
                            </span>
                            <span class="text-xs font-bold text-amber-600 opacity-0 group-hover:opacity-100 transition-opacity">Open &rarr;</span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-amber-950">Final Vouchers &amp; Cashbook</h4>
                        <p class="text-xs text-slate-500 mt-1">Form 35, Debit/Credit Cash Book, Title A4 &amp; Sticker A5</p>
                    </a>

                    <a href="{{ route('division.bill-advice-reports.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-purple-600 hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between pb-2">
                            <span class="p-2 rounded-lg bg-purple-50 text-purple-700 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            <span class="text-xs font-bold text-purple-600 opacity-0 group-hover:opacity-100 transition-opacity">Open &rarr;</span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-purple-950">Bill / Advice Reports (Form 63)</h4>
                        <p class="text-xs text-slate-500 mt-1">CMP bank transfers, DBT beneficiaries &amp; daily wager payslips</p>
                    </a>
                </div>
            @elseif($user->isRange())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="{{ route('finance.voucher-print.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-emerald-600 hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between pb-2">
                            <span class="p-2 rounded-lg bg-emerald-50 text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                            </span>
                            <span class="text-xs font-bold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">Open &rarr;</span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-emerald-950">Voucher Print (Form No. 35)</h4>
                        <p class="text-xs text-slate-500 mt-1">Range primary vouchers with items &amp; contractor certificate</p>
                    </a>

                    <a href="{{ route('finance.abstract-print.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-blue-600 hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between pb-2">
                            <span class="p-2 rounded-lg bg-blue-50 text-blue-700 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5"/><path d="M4 6h12l-1 4 1 4H4"/><path d="M8 19h12"/></svg>
                            </span>
                            <span class="text-xs font-bold text-blue-600 opacity-0 group-hover:opacity-100 transition-opacity">Open &rarr;</span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-blue-950">Abstract Print (Summary)</h4>
                        <p class="text-xs text-slate-500 mt-1">Docket expenditure breakdown by subhead &amp; object class</p>
                    </a>

                    <a href="{{ route('finance.work-order-print.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-amber-600 hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between pb-2">
                            <span class="p-2 rounded-lg bg-amber-50 text-amber-700 group-hover:bg-amber-600 group-hover:text-white transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </span>
                            <span class="text-xs font-bold text-amber-600 opacity-0 group-hover:opacity-100 transition-opacity">Open &rarr;</span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-amber-950">Work Order Print</h4>
                        <p class="text-xs text-slate-500 mt-1">Formal sanctioned work orders with rates &amp; specifications</p>
                    </a>

                    <a href="{{ route('finance.vavetar-register.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-purple-600 hover:shadow-md transition-all group">
                        <div class="flex items-center justify-between pb-2">
                            <span class="p-2 rounded-lg bg-purple-50 text-purple-700 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </span>
                            <span class="text-xs font-bold text-purple-600 opacity-0 group-hover:opacity-100 transition-opacity">Open &rarr;</span>
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-purple-950">Vavetar Register</h4>
                        <p class="text-xs text-slate-500 mt-1">Plantation daily activity log &amp; target vs achievement matrix</p>
                    </a>
                </div>
            @endif
        </div>

        <!-- 5. Recent Ranges / Permissions Panel -->
        <div class="grid gap-4 xl:grid-cols-[1.35fr_.65fr]">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 bg-slate-50/50">
                    <div>
                        <h2 class="font-bold text-slate-900">{{ $user->isAdmin() ? 'Division administration' : 'Recent range accounts' }}</h2>
                        <p class="text-xs text-slate-500">{{ $user->isAdmin() ? 'Create and manage division login accounts.' : 'Range logins created by your division.' }}</p>
                    </div>
                    @if($user->isAdmin())
                        <a class="secondary-button text-xs py-1 px-3" href="{{ route('admin.divisions.index') }}">Manage divisions</a>
                    @elseif($user->isDivision())
                        <a class="secondary-button text-xs py-1 px-3" href="{{ route('ranges.index') }}">Manage ranges</a>
                    @endif
                </div>
                @if($user->isAdmin())
                    <div class="p-5 text-sm text-slate-600">Each division manages its own ranges. The administrator manages division accounts only.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Range Name</th>
                                    <th>Username</th>
                                    <th>Created Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentRanges as $range)
                                    <tr>
                                        <td class="font-bold text-slate-900">{{ $range->name }}</td>
                                        <td class="font-mono text-emerald-800 font-semibold">{{ $range->username }}</td>
                                        <td>{{ $range->created_at->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-slate-500 py-4">No range accounts created yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-3">
                <h3 class="font-bold text-slate-900 text-sm">System Help &amp; Standards</h3>
                <div class="space-y-2.5 text-xs">
                    <div class="rounded-lg bg-emerald-50 p-3 text-emerald-900 space-y-1">
                        <p class="font-bold">Gujarat Forest Accounts Standard</p>
                        <p class="text-emerald-800 leading-relaxed">
                            All statutory Form 53, D-36, Summary, Cash Book, and Vavetar printouts strictly adhere to Gujarat State Forest Department accounting rules.
                        </p>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3 text-slate-800 space-y-1">
                        <p class="font-bold text-slate-900">Need to update letterhead?</p>
                        <p class="text-slate-600 leading-relaxed">
                            Click <a href="{{ route('office-profile.edit') }}" class="text-emerald-700 underline font-semibold">Office Profile &amp; Logo</a> to update officer in-charge, address, or custom division emblem.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts.admin>
