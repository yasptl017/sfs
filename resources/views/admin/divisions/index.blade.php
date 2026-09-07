<x-layouts.admin title="Divisions | Forest Inventory" heading="Division Management" subheading="Create and manage division login accounts">
    <div class="grid gap-5 xl:grid-cols-[.8fr_1.2fr]">
        <form class="rounded-lg border border-emerald-100 bg-white p-5 shadow-sm" method="POST" action="{{ route('admin.divisions.store') }}">
            @csrf
            <h2 class="text-base font-semibold text-slate-950">Create division login</h2>
            <p class="mt-1 text-sm text-slate-500">Division users can create and manage their own range accounts.</p>

            <div class="mt-5 space-y-4">
                <div><label class="form-label" for="name">Division name</label><input id="name" class="form-input" name="name" value="{{ old('name') }}" placeholder="North Division">@error('name') <p class="form-error">{{ $message }}</p> @enderror</div>
                <div><label class="form-label" for="username">Login username</label><input id="username" class="form-input" name="username" value="{{ old('username') }}" placeholder="north_division">@error('username') <p class="form-error">{{ $message }}</p> @enderror</div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label class="form-label" for="password">Password</label><input id="password" class="form-input" type="password" name="password">@error('password') <p class="form-error">{{ $message }}</p> @enderror</div><div><label class="form-label" for="password_confirmation">Confirm password</label><input id="password_confirmation" class="form-input" type="password" name="password_confirmation"></div></div>
            </div>
            <button class="primary-button mt-5 w-full" type="submit">Create division account</button>
        </form>

        <div class="rounded-lg border border-emerald-100 bg-white shadow-sm">
            <div class="border-b border-emerald-100 px-5 py-4"><h2 class="font-semibold text-slate-950">Created divisions</h2><p class="text-sm text-slate-500">Division accounts and their linked ranges</p></div>
            @error('division') <p class="mx-5 mt-4 form-error">{{ $message }}</p> @enderror
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Division</th><th>Username</th><th>Ranges</th><th>Created</th><th class="text-right">Actions</th></tr></thead><tbody>
                @forelse($divisions as $division)
                    <tr>
                        <td>{{ $division->name }}</td><td>{{ $division->username }}</td><td>{{ $division->ranges_count }}</td><td>{{ $division->created_at->format('d M Y') }}</td>
                        <td><div class="flex justify-end gap-2"><details><summary class="secondary-button cursor-pointer list-none">Edit</summary><form class="mt-2 w-72 rounded-lg border border-emerald-100 bg-white p-3 shadow-lg" method="POST" action="{{ route('admin.divisions.update', $division) }}">@csrf @method('PUT')<label class="form-label">Name</label><input class="form-input" name="name" value="{{ $division->name }}"><label class="form-label mt-2 block">Username</label><input class="form-input" name="username" value="{{ $division->username }}"><label class="form-label mt-2 block">New password <span class="font-normal">(optional)</span></label><input class="form-input" type="password" name="password"><label class="form-label mt-2 block">Confirm password</label><input class="form-input" type="password" name="password_confirmation"><button class="primary-button mt-3 w-full" type="submit">Save changes</button></form></details><form method="POST" action="{{ route('admin.divisions.destroy', $division) }}" onsubmit="return confirm('Remove this division account?');">@csrf @method('DELETE')<button class="secondary-button text-red-700" type="submit">Delete</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-500">No division accounts available.</td></tr>
                @endforelse
            </tbody></table></div>
            @if($divisions->hasPages()) <div class="border-t border-emerald-100 px-5 py-4">{{ $divisions->links() }}</div> @endif
        </div>
    </div>
</x-layouts.admin>
