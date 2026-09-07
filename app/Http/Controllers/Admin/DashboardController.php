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
        return view('admin.dashboard', [
            'rangeCount' => $user->isDivision() ? $user->ranges()->count() : null,
            'divisionCount' => User::query()->where('role', 'division')->count(),
            'recentRanges' => $user->isDivision()
                ? $user->ranges()->latest()->limit(5)->get()
                : collect(),
            'user' => $user,
        ]);
    }
}
