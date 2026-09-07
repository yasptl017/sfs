<x-layouts.admin title="Dashboard | Forest Inventory" heading="Inventory Dashboard">
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">Logged in as</p>
            <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $user->name }}</p>
            <p class="mt-1 text-sm capitalize text-emerald-700">{{ $user->role }} account</p>
        </div>
        @if($user->isDivision())
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">Ranges under division</p>
                <p class="mt-2 text-3xl font-semibold text-slate-950">{{ $rangeCount }}</p>
                <p class="mt-1 text-sm text-slate-500">Active range login accounts</p>
            </div>
        @elseif($user->isAdmin())
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">Access level</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">System Admin</p>
                <p class="mt-1 text-sm text-slate-500">Division account management</p>
            </div>
        @else
            <div class="stat-card">
                <p class="text-sm font-medium text-slate-500">Access level</p>
                <p class="mt-2 text-2xl font-semibold text-slate-950">Range User</p>
                <p class="mt-1 text-sm text-slate-500">Range finance and registration tools</p>
            </div>
        @endif
        <div class="stat-card">
            <p class="text-sm font-medium text-slate-500">System divisions</p>
            <p class="mt-2 text-3xl font-semibold text-slate-950">{{ $divisionCount }}</p>
            <p class="mt-1 text-sm text-slate-500">Administrative division accounts</p>
        </div>
    </div>

    <div class="mt-6 grid gap-4 xl:grid-cols-[1.35fr_.65fr]">
        <div class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-emerald-100 px-5 py-4">
                <div>
                    <h2 class="font-semibold text-slate-950">{{ $user->isAdmin() ? 'Division administration' : 'Recent range accounts' }}</h2>
                    <p class="text-sm text-slate-500">{{ $user->isAdmin() ? 'Create and manage division login accounts.' : 'Range logins created by your division.' }}</p>
                </div>
                @if($user->isAdmin())
                    <a class="secondary-button" href="{{ route('admin.divisions.index') }}">Manage divisions</a>
                @elseif($user->isDivision())
                    <a class="secondary-button" href="{{ route('ranges.index') }}">Manage</a>
                @endif
            </div>
            @if($user->isAdmin())
                <div class="p-5 text-sm text-slate-600">Each division manages its own ranges. The administrator manages division accounts only.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentRanges as $range)
                            <tr>
                                <td>{{ $range->name }}</td>
                                <td>{{ $range->username }}</td>
                                <td>{{ $range->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-slate-500">No range accounts created yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="rounded-lg border border-emerald-100 bg-white p-5 shadow-sm">
            <h2 class="font-semibold text-slate-950">Role permissions</h2>
            <div class="mt-4 space-y-3 text-sm">
                @if($user->isAdmin())
                    <div class="rounded-lg bg-emerald-50 p-3">
                        <p class="font-semibold text-emerald-900">Administrator</p>
                        <p class="mt-1 text-emerald-800">Can create, update, and remove division login accounts only.</p>
                    </div>
                @endif
                <div class="rounded-lg bg-emerald-50 p-3">
                    <p class="font-semibold text-emerald-900">Division</p>
                    <p class="mt-1 text-emerald-800">Can create range login accounts and reset its own password.</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-3">
                    <p class="font-semibold text-slate-900">Range</p>
                    <p class="mt-1 text-slate-600">Can access the admin panel and reset its own password.</p>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
