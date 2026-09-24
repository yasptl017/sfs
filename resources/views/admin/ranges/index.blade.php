<x-layouts.admin title="Ranges | Forest Inventory" heading="Range Management" subheading="Create and manage range login accounts">
    <div class="space-y-5">
        <form class="rounded-lg border border-emerald-100 bg-white p-5 shadow-sm" method="POST" action="{{ route('ranges.store') }}">
            @csrf
            <h2 class="text-base font-semibold text-slate-950">Create range login</h2>
            <p class="mt-1 text-sm text-slate-500">The same username and password can be used by the range to log in.</p>

            <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
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

            <button class="primary-button mt-5" type="submit">Create range account</button>
        </form>

        <div class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-emerald-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-950">Created ranges</h2>
                    <p class="text-sm text-slate-500">Accounts linked to your division login</p>
                </div>
                <form class="flex w-full gap-2 sm:w-auto" method="GET" action="{{ route('ranges.index') }}">
                    <input class="form-input min-w-0 sm:w-64" name="search" value="{{ request('search') }}" placeholder="Search range or username">
                    <button class="secondary-button" type="submit">Search</button>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Range</th>
                            <th>Username</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ranges as $range)
                            <tr>
                                <td>{{ $range->name }}</td>
                                <td>{{ $range->username }}</td>
                                <td>{{ $range->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="flex justify-end">
                                        <details>
                                            <summary class="secondary-button cursor-pointer list-none">Edit</summary>
                                            <form class="mt-2 w-72 rounded-lg border border-emerald-100 bg-white p-3 shadow-lg" method="POST" action="{{ route('ranges.update', $range) }}">
                                                @csrf
                                                @method('PUT')
                                                <label class="form-label">Range name</label>
                                                <input class="form-input" name="name" value="{{ $range->name }}" required>
                                                <label class="form-label mt-2 block">Login username</label>
                                                <input class="form-input" name="username" value="{{ $range->username }}" required>
                                                <label class="form-label mt-2 block">New password <span class="font-normal">(optional)</span></label>
                                                <input class="form-input" type="password" name="password">
                                                <label class="form-label mt-2 block">Confirm password</label>
                                                <input class="form-input" type="password" name="password_confirmation">
                                                <button class="primary-button mt-3 w-full" type="submit">Save changes</button>
                                            </form>
                                        </details>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-slate-500">No ranges available.</td>
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
