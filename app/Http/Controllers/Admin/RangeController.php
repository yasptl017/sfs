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
            'ranges' => $request->user()->ranges()->latest()->paginate(10),
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
}
