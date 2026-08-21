<x-layouts.admin title="Reset Password | Forest Inventory" heading="Reset Password" subheading="Update your own login password">
    <div class="max-w-xl rounded-lg border border-emerald-100 bg-white p-5 shadow-sm">
        <h2 class="text-base font-semibold text-slate-950">Change password</h2>
        <p class="mt-1 text-sm text-slate-500">This affects only your current {{ auth()->user()->role }} login.</p>

        <form class="mt-5 space-y-4" method="POST" action="{{ route('password.update') }}">
            @csrf
            @method('PUT')

            <div>
                <label class="form-label" for="current_password">Current password</label>
                <input id="current_password" class="form-input" type="password" name="current_password" autocomplete="current-password">
                @error('current_password') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="password">New password</label>
                <input id="password" class="form-input" type="password" name="password" autocomplete="new-password">
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label" for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" class="form-input" type="password" name="password_confirmation" autocomplete="new-password">
            </div>

            <button class="primary-button" type="submit">Update password</button>
        </form>
    </div>
</x-layouts.admin>
