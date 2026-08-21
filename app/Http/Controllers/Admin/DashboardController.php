<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $divisionId = $user->isDivision() ? $user->id : $user->division_id;

        return view('admin.dashboard', [
            'rangeCount' => $user->isDivision()
                ? $user->ranges()->count()
                : 1,
            'divisionCount' => User::query()->where('role', 'division')->count(),
            'recentRanges' => User::query()
                ->where('role', 'range')
                ->when($divisionId, fn ($query) => $query->where('division_id', $divisionId))
                ->latest()
                ->limit(5)
                ->get(),
            'user' => $user,
        ]);
    }
}
