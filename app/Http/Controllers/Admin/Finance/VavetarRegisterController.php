<?php

namespace App\Http\Controllers\Admin\Finance;

use App\Http\Controllers\Controller;
use App\Models\RangeLocation;
use App\Models\User;
use App\Models\Voucher;
use App\Support\CurrentFinancialYear;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VavetarRegisterController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeRange($request);

        $rangeUser = $request->user();
        $locationMap = $this->getLocationHierarchy($rangeUser);
        $recentRegisters = $this->getRecentRegistersSummary($rangeUser);

        $selectedRound = $request->query('round', '');
        $selectedBeat = $request->query('beat', '');
        $selectedPlace = $request->query('place', '');
        $selectedArea = $request->query('area', '');

        return view('admin.finance.vavetar-register.index', [
            'locationMap' => $locationMap,
            'selectedRound' => $selectedRound,
            'selectedBeat' => $selectedBeat,
            'selectedPlace' => $selectedPlace,
            'selectedArea' => $selectedArea,
            'recentRegisters' => $recentRegisters,
        ]);
    }

    public function locations(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $locationMap = $this->getLocationHierarchy($request->user());

        return response()->json([
            'success' => true,
            'locations' => $locationMap,
        ]);
    }

    public function preview(Request $request): JsonResponse
    {
        $this->authorizeRange($request);

        $validated = $request->validate([
            'round' => ['nullable', 'string', 'max:100'],
            'beat' => ['nullable', 'string', 'max:100'],
            'place' => ['nullable', 'string', 'max:150'],
            'area' => ['nullable', 'string', 'max:50'],
            'report_type' => ['nullable', 'string', 'in:date_wise,work_wise'],
        ]);

        $round = $validated['round'] ?? '';
        $beat = $validated['beat'] ?? '';
        $place = $validated['place'] ?? '';
        $area = $validated['area'] ?? '';
        $reportType = $validated['report_type'] ?? 'date_wise';

        $data = $this->compileVavetarData($request->user(), $round, $beat, $place, $area, $reportType);

        return response()->json([
            'success' => true,
            'round' => $round,
            'beat' => $beat,
            'place' => $place,
            'area' => $area,
            'report_type' => $reportType,
            'data' => $data,
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorizeRange($request);

        $round = (string) $request->query('round', '');
        $beat = (string) $request->query('beat', '');
        $place = (string) $request->query('place', '');
        $area = (string) $request->query('area', '');
        $reportType = (string) $request->query('report_type', 'date_wise');

        $data = $this->compileVavetarData($request->user(), $round, $beat, $place, $area, $reportType);
        $profile = $request->user()->getOrCreateOfficeProfile();

        return view('admin.finance.vavetar-register.print', [
            'round' => $round,
            'beat' => $beat,
            'place' => $place,
            'area' => $area,
            'reportType' => $reportType,
            'data' => $data,
            'rangeUser' => $request->user(),
            'profile' => $profile,
        ]);
    }

    // ------------------------------------------------------------------------------------------------ data
    private function query(User $rangeUser)
    {
        return Voucher::query()->forYear(CurrentFinancialYear::id())->where('range_id', $rangeUser->id)->whereNull('rejected_reason');
    }

    private function date(mixed $value): string
    {
        return $value ? Carbon::parse($value)->format('d/m/Y') : '';
    }

    /** Round > beat > places: the range's location list plus every place its vouchers really use (so no voucher is unreachable). */
    private function getLocationHierarchy(User $rangeUser): array
    {
        $hierarchy = [];
        $add = function (string $r, string $b, string $p, string $ha, string $year) use (&$hierarchy) {
            $r = trim($r);
            $b = trim($b);
            $p = trim($p);
            if ($r === '' && $b === '' && $p === '') {
                return;
            }
            foreach ($hierarchy[$r][$b] ?? [] as $existing) {
                if (mb_strtolower($existing['place']) === mb_strtolower($p)) {
                    return;
                }
            }
            $hierarchy[$r][$b][] = ['place' => $p, 'hectares' => $ha, 'place_year' => $year];
        };

        foreach (RangeLocation::query()->where('range_id', $rangeUser->id)->orderBy('round')->orderBy('beat')->orderBy('place')->get() as $loc) {
            $add((string) $loc->round, (string) $loc->beat, (string) $loc->place, $loc->hectares ? number_format((float) $loc->hectares, 2) : '', (string) ($loc->place_year ?? ''));
        }
        foreach ($this->query($rangeUser)->where('place', '<>', '')->select('round', 'beat', 'place')->distinct()->orderBy('round')->orderBy('beat')->orderBy('place')->get() as $v) {
            $add((string) $v->round, (string) $v->beat, (string) $v->place, '', '');
        }
        ksort($hierarchy);

        return $hierarchy;
    }

    private function compileVavetarData(User $rangeUser, string $round, string $beat, string $place, string $area, string $reportType): array
    {
        $q = $this->query($rangeUser)->with('items');
        foreach (['round' => $round, 'beat' => $beat, 'place' => $place] as $col => $val) {
            if (trim($val) !== '') {
                $q->whereRaw("TRIM($col) = ?", [trim($val)]);
            }
        }
        $vouchers = $q->get();

        $rows = [];
        foreach ($vouchers as $v) {
            foreach ($v->items as $i) {
                $rows[] = [
                    'sort' => ($d = $i->work_start_date ?? $i->work_order_date ?? $v->bill_date) ? Carbon::parse($d)->format('Y-m-d') : '9999-12-31',
                    'serial' => (int) $v->serial_number, 'line' => (int) $i->line_no,
                    'date' => $this->date($i->work_start_date ?? $i->work_order_date ?? $v->bill_date),
                    'voucher_no' => $v->voucher_no_final ?: 'Sr. '.$v->serial_number,
                    'party_name' => $v->party_name,
                    'description' => trim((string) $i->description),
                    'plot_area' => $v->place,
                    'quantity' => $i->quantity !== null ? (float) $i->quantity : null,
                    'unit' => $i->unit,
                    'rate' => $i->rate !== null ? (float) $i->rate : null,
                    'amount' => (float) $i->amount,
                ];
            }
        }
        usort($rows, fn ($a, $b) => [$a['sort'], $a['serial'], $a['line']] <=> [$b['sort'], $b['serial'], $b['line']]);

        $dateWise = [];
        $cumulative = 0.0;
        foreach ($rows as $r) {
            $cumulative += $r['amount'];
            $dateWise[] = array_merge(array_diff_key($r, ['sort' => 1, 'serial' => 1, 'line' => 1]), ['cumulative_amount' => $cumulative]);
        }

        // Work wise = the same work (item description) added up over all its vouchers.
        $groups = [];
        foreach ($rows as $r) {
            $k = $r['description'] !== '' ? $r['description'] : '(no description recorded)';
            $groups[$k] ??= ['category' => $k, 'total_quantity' => 0.0, 'unit' => $r['unit'], 'total_amount' => 0.0, 'items' => []];
            $groups[$k]['total_quantity'] += (float) $r['quantity'];
            $groups[$k]['total_amount'] += $r['amount'];
            $groups[$k]['items'][] = ['date' => $r['date'], 'voucher_no' => $r['voucher_no'], 'description' => $r['description'], 'quantity' => $r['quantity'], 'unit' => $r['unit'], 'rate' => $r['rate'], 'amount' => $r['amount']];
        }
        uasort($groups, fn ($a, $b) => $b['total_amount'] <=> $a['total_amount']);

        // Area / year of creation come from the range's location list (only when exactly one place is chosen).
        $loc = null;
        if (trim($place) !== '') {
            $loc = RangeLocation::query()->where('range_id', $rangeUser->id)->whereRaw('TRIM(place) = ?', [trim($place)])
                ->when(trim($round) !== '', fn ($x) => $x->whereRaw('TRIM(round) = ?', [trim($round)]))
                ->when(trim($beat) !== '', fn ($x) => $x->whereRaw('TRIM(beat) = ?', [trim($beat)]))->first();
        }
        $areaText = trim($area) !== '' ? $area : ($loc?->hectares ? number_format((float) $loc->hectares, 2).' Ha' : '');
        $schemes = $vouchers->pluck('scheme')->filter()->unique()->values();

        return [
            'round' => $round ?: 'All rounds',
            'beat' => $beat ?: 'All beats',
            'place' => $place ?: 'All places',
            'area' => $areaText ?: '-',
            'report_type' => $reportType,
            'range_name' => $rangeUser->name,
            'division_name' => ($rangeUser->division ?? $rangeUser)->getOrCreateOfficeProfile()->display_name,
            'year_of_plantation' => $loc?->place_year ?: '-',
            'scheme_name' => $schemes->isEmpty() ? '-' : $schemes->implode('; '),
            'date_wise_rows' => $dateWise,
            'work_wise_groups' => array_values($groups),
            'total_expenditure' => array_sum(array_column($rows, 'amount')),
            'total_vouchers' => $vouchers->count(),
            'generated_at' => now()->format('d M Y, h:i A'),
        ];
    }

    /** The range's busiest real places (by expenditure) for the quick-pick table. */
    private function getRecentRegistersSummary(User $rangeUser): array
    {
        $rows = DB::table('vouchers as v')->join('voucher_items as i', 'i.voucher_id', '=', 'v.id')
            ->where('v.financial_year_id', CurrentFinancialYear::id())->where('v.range_id', $rangeUser->id)->whereNull('v.rejected_reason')->where('v.place', '<>', '')
            ->selectRaw('TRIM(v.round) as round, TRIM(v.beat) as beat, TRIM(v.place) as place, COUNT(DISTINCT v.id) as vouchers_count, SUM(i.amount) as total_expenditure')
            ->groupByRaw('TRIM(v.round), TRIM(v.beat), TRIM(v.place)')->orderByDesc('total_expenditure')->limit(12)->get();
        $areas = RangeLocation::query()->where('range_id', $rangeUser->id)->get()->mapWithKeys(fn ($l) => [trim($l->place) => $l->hectares ? number_format((float) $l->hectares, 2).' Ha' : '']);

        return $rows->map(fn ($r) => [
            'round' => $r->round, 'beat' => $r->beat, 'place' => $r->place, 'area' => $areas[$r->place] ?? '',
            'vouchers_count' => (int) $r->vouchers_count, 'total_expenditure' => (float) $r->total_expenditure,
        ])->all();
    }

    private function authorizeRange(Request $request): void
    {
        abort_unless($request->user()?->isRange(), 403);
    }
}
