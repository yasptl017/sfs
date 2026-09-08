<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Forest Inventory' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-stone-50 text-slate-900 antialiased">
    <div id="adminShell" class="admin-shell min-h-screen">
        <aside id="sidebar" class="sidebar border-r border-emerald-100 bg-white/95 shadow-sm">
            <div class="flex h-16 items-center gap-3 border-b border-emerald-100 px-4">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-700 text-white">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v18M6 8c2.5 0 4.5-1.5 6-5 1.5 3.5 3.5 5 6 5-1 4-3 6-6 6s-5-2-6-6Z"/>
                    </svg>
                </div>
                <div class="brand-copy min-w-0">
                    <p class="truncate text-sm font-semibold text-slate-950">Forest Inventory</p>
                    <p class="truncate text-xs text-slate-500">Department Management</p>
                </div>
            </div>

            <nav class="sidebar-nav space-y-1 px-3 py-4">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>
                    <span>Dashboard</span>
                </a>

                @if(auth()->user()->isAdmin())
                    <a class="nav-link {{ request()->routeIs('admin.divisions.*') ? 'active' : '' }}" href="{{ route('admin.divisions.index') }}" title="Divisions">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/><path d="M8 10h.01M16 10h.01"/></svg>
                        <span>Divisions</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('admin.budget-codes.*') ? 'active' : '' }}" href="{{ route('admin.budget-codes.index') }}" title="Budget Codes">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v16H4z"/><path d="M4 9h16M9 4v16"/></svg>
                        <span>Budget Codes</span>
                    </a>
                @endif

                @if(auth()->user()->isDivision())
                    <a class="nav-link {{ request()->routeIs('ranges.*') ? 'active' : '' }}" href="{{ route('ranges.index') }}" title="Ranges">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5"/><path d="M4 6h12l-1 4 1 4H4"/><path d="M8 19h12"/></svg>
                        <span>Ranges</span>
                    </a>
                    <a class="nav-link {{ request()->routeIs('range-locations.*') ? 'active' : '' }}" href="{{ route('range-locations.index') }}" title="Range Locations">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-5.4 7-12a7 7 0 1 0-14 0c0 6.6 7 12 7 12Z"/><circle cx="12" cy="9" r="2.5"/></svg>
                        <span>Range Locations</span>
                    </a>
                @endif

                @if(auth()->user()->isDivision())
                    <div class="nav-section-title">Division Finance System</div>

                    <details class="nav-group">
                        <summary class="nav-link" title="Bill / Advice"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg><span>Bill / Advice</span><svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></summary>
                        <div class="nav-submenu"><a href="#">Process Bill (Advice)</a><a href="#">Update GST Challan No.</a><a href="#">Bill / Advice Reports</a><a href="#">Add Treasury Details</a><a href="#">Change Bill No. and Order No.</a><a href="#">Delete Advice</a></div>
                    </details>

                    <details class="nav-group">
                        <summary class="nav-link" title="Monthly Reports"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V5"/><path d="M4 6h12l-1 4 1 4H4"/><path d="M8 19h12"/></svg><span>Monthly Reports</span><svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></summary>
                        <div class="nav-submenu"><a href="#">Monthly Reports</a><a href="#">Summary Reports</a><a href="#">Final Voucher Nos. &amp; Form No. 35 &amp; Cashbook</a></div>
                    </details>

                    <details class="nav-group" open>
                        <summary class="nav-link" title="Entry"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg><span>Entry</span><svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg></summary>
                        <div class="nav-submenu"><a href="{{ route('division.parties.index') }}">Party Registration</a><a href="#">Allotment from Circle</a><a href="#">Allotment to Range</a><a href="#">Allotment Adjustment</a><a href="#">LC Entry</a><a href="#">Edit LC Entry</a></div>
                    </details>
                @endif

                @if(auth()->user()->isRange())
                    <div class="nav-section-title">Range Finance System</div>

                    <details class="nav-group" open>
                        <summary class="nav-link" title="Voucher Entry">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6"/><path d="M9 13h6M9 17h4"/></svg>
                            <span>Voucher Entry</span>
                            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </summary>
                        <div class="nav-submenu">
                            <a class="{{ request()->routeIs('finance.tender-entries.*') ? 'active' : '' }}" href="{{ route('finance.tender-entries.index') }}" title="Tender Entry">Tender Entry</a>
                            <a class="{{ request()->routeIs('finance.free-entries.*') ? 'active' : '' }}" href="{{ route('finance.free-entries.index') }}" title="Free Entry">Free Entry</a>
                            <a class="{{ request()->routeIs('finance.d-wager-salary-entries.*') ? 'active' : '' }}" href="{{ route('finance.d-wager-salary-entries.index') }}" title="D. Wagers Salary">D. Wagers Salary</a>
                            <a class="{{ request()->routeIs('finance.d-wager-arrears-entries.*') ? 'active' : '' }}" href="{{ route('finance.d-wager-arrears-entries.index') }}" title="D. Wagers Arrears">D. Wagers Arrears</a>
                            <a class="{{ request()->routeIs('finance.wl-beneficiary-entries.*') ? 'active' : '' }}" href="{{ route('finance.wl-beneficiary-entries.index') }}" title="WL Beneficiary Entry">WL Beneficiary Entry</a>
                            <a class="{{ request()->routeIs('finance.sf-beneficiary-entries.*') ? 'active' : '' }}" href="{{ route('finance.sf-beneficiary-entries.index') }}" title="SF Beneficiary Entry">SF Beneficiary Entry</a>
                            <a href="#" title="B.T. Bill Entry">B.T. Bill Entry</a>
                        </div>
                    </details>

                    <details class="nav-group" {{ request()->routeIs('finance.registration.*') ? 'open' : '' }}>
                        <summary class="nav-link" title="Registration Entry">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>
                            <span>Registration Entry</span>
                            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </summary>
                        <div class="nav-submenu">
                            <a class="{{ request()->routeIs('finance.registration.parties.*') || request()->routeIs('finance.registration.party*') ? 'active' : '' }}" href="{{ route('finance.registration.parties.index') }}" title="Party Registration">Party Registration</a>
                            <a class="{{ request()->routeIs('finance.registration.wl-beneficiaries.*') ? 'active' : '' }}" href="{{ route('finance.registration.wl-beneficiaries.index') }}" title="WL Bene. Registration">WL Bene. Registration</a>
                            <a class="{{ request()->routeIs('finance.registration.sf-beneficiaries.*') ? 'active' : '' }}" href="{{ route('finance.registration.sf-beneficiaries.index') }}" title="SF Bene. Registration">SF Bene. Registration</a>
                        </div>
                    </details>

                    <details class="nav-group">
                        <summary class="nav-link" title="Prints & Reports">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
                            <span>Prints & Reports</span>
                            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </summary>
                        <div class="nav-submenu">
                            <a href="#" title="Voucher Print">Voucher Print</a>
                            <a href="#" title="Abstract Print">Abstract Print</a>
                            <a href="#" title="Work Order Print">Work Order Print</a>
                            <a href="#" title="SOR Limit Report">SOR Limit Report</a>
                            <a href="#" title="Vavtar Register">Vavtar Register</a>
                        </div>
                    </details>

                    <details class="nav-group">
                        <summary class="nav-link" title="Import from Last Year">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/></svg>
                            <span>Import from Last Year</span>
                            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
                        </summary>
                        <div class="nav-submenu">
                            <a href="#" title="Import Parties Reg. By Range">Import Parties Reg. By Range</a>
                            <a href="#" title="Import SF Beneficiaries">Import SF Beneficiaries</a>
                        </div>
                    </details>

                    <a class="nav-link" href="#" title="Update Itemwise">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/><path d="M3 12a9 9 0 0 1 15.74-6.26L21 8"/><path d="M16 8h5V3"/></svg>
                        <span>Update Itemwise</span>
                    </a>
                    <a class="nav-link" href="#" title="Check for duplications">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        <span>Check for duplications</span>
                    </a>
                    <a class="nav-link" href="#" title="Refresh Manually">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 0 1 15-6.7L21 8"/><path d="M3 22v-6h6"/><path d="M21 12a9 9 0 0 1-15 6.7L3 16"/></svg>
                        <span>Refresh Manually</span>
                    </a>
                    <a class="nav-link danger" href="#" title="Delete Entry">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                        <span>Delete Entry</span>
                    </a>
                    <a class="nav-link" href="#" title="Help">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.1 9a3 3 0 1 1 5.8 1c0 2-3 2-3 4"/><path d="M12 17h.01"/></svg>
                        <span>Help</span>
                    </a>
                @endif

                <a class="nav-link {{ request()->routeIs('password.*') ? 'active' : '' }}" href="{{ route('password.edit') }}" title="Password">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="11" width="16" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                    <span>Password</span>
                </a>
            </nav>

            <div class="mt-auto border-t border-emerald-100 p-3">
                <div class="user-tile mb-3 rounded-lg bg-emerald-50 px-3 py-3">
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs capitalize text-emerald-700">{{ auth()->user()->role }} user</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="nav-link w-full" type="submit" title="Logout">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <div id="mobileShade" class="mobile-shade"></div>

        <main class="min-w-0">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-emerald-100 bg-stone-50/90 px-4 backdrop-blur md:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button id="sidebarToggle" class="icon-button" type="button" aria-label="Toggle navigation" title="Toggle navigation">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="min-w-0">
                        <h1 class="truncate text-lg font-semibold text-slate-950">{{ $heading ?? 'Dashboard' }}</h1>
                        <p class="truncate text-xs text-slate-500">{{ $subheading ?? 'Forest stock and administrative access control' }}</p>
                    </div>
                </div>
                <div class="hidden items-center gap-2 rounded-lg border border-emerald-100 bg-white px-3 py-2 text-sm text-slate-600 sm:flex">
                    <span class="h-2 w-2 rounded-full bg-emerald-600"></span>
                    {{ now()->format('d M Y') }}
                </div>
            </header>

            <section class="p-4 md:p-6">
                @if(session('status'))
                    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                {{ $slot }}
            </section>
        </main>
    </div>
</body>
</html>
