<x-layouts.admin title="Party Details | Forest Inventory" heading="Party Details" subheading="Registered finance parties for this range">
    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-base font-semibold text-slate-950">Registered parties</h2>
                <p class="mt-1 text-sm text-slate-500">Review party registration details and manage active status.</p>
            </div>
            <a class="primary-button w-full sm:w-auto" href="{{ route('finance.registration.party') }}">Add Party</a>
        </div>

        <div class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="data-table min-w-[72rem]">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Party Code</th>
                            <th>Party Name</th>
                            <th>Round</th>
                            <th>Bank</th>
                            <th>Mobile</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($parties as $party)
                            <tr>
                                <td class="font-semibold text-slate-950">{{ $party->serial_number }}</td>
                                <td>{{ $party->party_code }}</td>
                                <td>
                                    <div class="font-semibold text-slate-900">{{ $party->party_name }}</div>
                                    @if($party->small_description)
                                        <div class="mt-1 max-w-xs truncate text-xs text-slate-500">{{ $party->small_description }}</div>
                                    @endif
                                </td>
                                <td>{{ $party->round }}</td>
                                <td>
                                    <div>{{ $party->bank_name }}</div>
                                    @if($party->account_no)
                                        <div class="mt-1 text-xs text-slate-500">A/C {{ $party->account_no }}</div>
                                    @endif
                                </td>
                                <td>{{ $party->party_mobile_no ?? '-' }}</td>
                                <td>
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $party->party_status === 'Active' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                        {{ $party->party_status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a class="secondary-button min-h-9 px-3 py-1.5 text-xs" href="{{ route('finance.registration.party.edit', $party) }}">Edit</a>

                                        <form method="POST" action="{{ route('finance.registration.party.status', $party) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="secondary-button min-h-9 px-3 py-1.5 text-xs" type="submit">
                                                {{ $party->party_status === 'Active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('finance.registration.party.destroy', $party) }}" onsubmit="return confirm('Delete this party registration?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="secondary-button min-h-9 border-red-200 bg-red-50 px-3 py-1.5 text-xs text-red-700 hover:border-red-300 hover:bg-white" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-slate-500">
                                    No party registrations available.
                                    <a class="font-semibold text-emerald-700 hover:text-emerald-800" href="{{ route('finance.registration.party') }}">Create the first party.</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($parties->hasPages())
                <div class="border-t border-emerald-100 px-5 py-4">
                    {{ $parties->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
