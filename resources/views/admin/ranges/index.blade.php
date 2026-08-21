<x-layouts.admin title="Ranges | Forest Inventory" heading="Range Management" subheading="Create and manage range login accounts">
    <div class="grid gap-5 xl:grid-cols-[.8fr_1.2fr]">
        <form class="rounded-lg border border-emerald-100 bg-white p-5 shadow-sm" method="POST" action="{{ route('ranges.store') }}">
            @csrf
            <h2 class="text-base font-semibold text-slate-950">Create range login</h2>
            <p class="mt-1 text-sm text-slate-500">The same username and password can be used by the range to log in.</p>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="form-label" for="name">Range name</label>
                    <input id="name" class="form-input" name="name" value="{{ old('name') }}" placeholder="North Range">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label" for="username">Login username</label>
                    <input id="username" class="form-input" name="username" value="{{ old('username') }}" placeholder="north_range">
                    @error('username') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="password">Password</label>
                        <input id="password" class="form-input" type="password" name="password">
                        @error('password') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="password_confirmation">Confirm password</label>
                        <input id="password_confirmation" class="form-input" type="password" name="password_confirmation">
                    </div>
                </div>
            </div>

            <button class="primary-button mt-5 w-full" type="submit">Create range account</button>
        </form>

        <div class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4">
                <h2 class="font-semibold text-slate-950">Created ranges</h2>
                <p class="text-sm text-slate-500">Accounts linked to your division login</p>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Range</th>
                            <th>Username</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ranges as $range)
                            <tr>
                                <td>{{ $range->name }}</td>
                                <td>{{ $range->username }}</td>
                                <td>{{ $range->created_at->format('d M Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-slate-500">No ranges available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($ranges->hasPages())
                <div class="border-t border-emerald-100 px-5 py-4">
                    {{ $ranges->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts.admin>
