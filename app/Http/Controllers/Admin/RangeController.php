<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RangeController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isDivision(), 403);

        return view('admin.ranges.index', [
            'ranges' => $request->user()->ranges()
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->string('search')->trim();

                    $query->where(fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%"));
                })
                ->latest()
                ->paginate(50)
                ->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isDivision(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('users')],
            'password' => ['required', 'string', 'min:4', 'confirmed'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['username'].'@sfs.local',
            'role' => 'range',
            'division_id' => $request->user()->id,
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('status', 'Range login created successfully.');
    }

    public function update(Request $request, User $range): RedirectResponse
    {
        abort_unless($request->user()->isDivision(), 403);
        abort_unless($range->isRange() && $range->division_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'alpha_dash:ascii', 'max:255', Rule::unique('users')->ignore($range)],
            'password' => ['nullable', 'string', 'min:4', 'confirmed'],
        ]);

        $attributes = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['username'].'@sfs.local',
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $range->update($attributes);

        return back()->with('status', 'Range login updated successfully.');
    }
}
