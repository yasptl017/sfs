<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DivisionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        return view('admin.divisions.index', [
            'divisions' => User::query()
                ->where('role', 'division')
                ->withCount('ranges')
                ->latest()
                ->paginate(10),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);
        $validated = $this->validateDivision($request);

        User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['username'].'@sfs.local',
            'role' => 'division',
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Division login created successfully.');
    }

    public function update(Request $request, User $division): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($division->isDivision(), 404);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('users')->ignore($division)],
            'password' => ['nullable', 'string', 'min:4', 'confirmed'],
        ];
        $validated = $request->validate($rules);

        $attributes = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['username'].'@sfs.local',
        ];
        if (!empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }
        $division->update($attributes);

        return back()->with('status', 'Division updated successfully.');
    }

    public function destroy(Request $request, User $division): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($division->isDivision(), 404);

        if ($division->ranges()->exists()) {
            return back()->withErrors(['division' => 'This division still has range accounts. Remove or reassign them before deleting it.']);
        }

        $division->delete();

        return back()->with('status', 'Division removed successfully.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }

    /** @return array<string, mixed> */
    private function validateDivision(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);
    }
}
