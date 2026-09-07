<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RangeLocation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RangeLocationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDivision($request);
        $ranges = $request->user()->ranges()->orderBy('name')->get();

        return view('admin.range-locations.index', [
            'ranges' => $ranges,
            'placeYears' => $this->placeYears(),
            'locations' => RangeLocation::query()->whereIn('range_id', $ranges->pluck('id'))->with('range')->latest()->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDivision($request);
        RangeLocation::query()->create($this->validated($request));

        return back()->with('status', 'Range location added successfully.');
    }

    public function update(Request $request, RangeLocation $location): RedirectResponse
    {
        $this->authorizeLocation($request, $location);
        $location->update($this->validated($request));

        return back()->with('status', 'Range location updated successfully.');
    }

    public function destroy(Request $request, RangeLocation $location): RedirectResponse
    {
        $this->authorizeLocation($request, $location);
        $location->delete();

        return back()->with('status', 'Range location removed successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'range_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('division_id', $request->user()->id)->where('role', 'range'))],
            'round' => ['required', 'string', 'max:255'],
            'beat' => ['required', 'string', 'max:255'],
            'place' => ['required', 'string', 'max:255'],
            'hectares' => ['nullable', 'numeric', 'min:0'],
            'place_year' => ['nullable', Rule::in($this->placeYears())],
        ]);
    }

    private function authorizeDivision(Request $request): void
    {
        abort_unless($request->user()->isDivision(), 403);
    }

    /** @return array<int, string> */
    private function placeYears(): array
    {
        $year = (int) now()->format('Y');
        $financialYearStart = (int) now()->format('n') < 4 ? $year - 1 : $year;

        return collect(range($financialYearStart - 30, $financialYearStart + 30))
            ->map(fn (int $start) => sprintf('%d-%02d', $start, ($start + 1) % 100))
            ->all();
    }

    private function authorizeLocation(Request $request, RangeLocation $location): void
    {
        $this->authorizeDivision($request);
        abort_unless($location->range?->division_id === $request->user()->id, 403);
    }
}
