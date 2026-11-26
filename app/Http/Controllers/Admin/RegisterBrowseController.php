<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CurrentFinancialYear;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/** Read-only browser for every migrated register, defined in config/registers.php. */
class RegisterBrowseController extends Controller
{
    private const HIDDEN = ['id', 'created_at', 'updated_at', 'financial_year_id', 'staging_sheet_id', 'source_row', 'attachments'];

    public function index(Request $request, string $key): View
    {
        $def = $this->definition($request, $key);
        $hasRange = $this->hasRange($def);
        $base = $this->baseQuery($request, $def, $hasRange);

        // filters / search apply to the base rows
        $filterOptions = [];
        foreach ($def['filters'] as $col => $label) {
            // only the one column (the base query selects every column, which made DISTINCT compare whole rows)
            $filterOptions[$col] = $this->scopedBase($request, $def, $hasRange)->whereNotNull("t.$col")->where("t.$col", '!=', '')->distinct()->orderBy("t.$col")->pluck("t.$col");
            if ($value = $request->query("f_$col")) {
                $base->where("t.$col", $value);
            }
        }
        if ($hasRange && ! $request->user()->isRange() && ($rid = $request->query('range_id'))) {
            $base->where('t.range_id', $rid);
        }
        if ($term = trim((string) $request->query('q'))) {
            $base->where(function ($w) use ($def, $term) {
                foreach ($def['search'] as $col) {
                    $w->orWhere("t.$col", 'like', "%{$term}%");
                }
                foreach ($def['search_sub'] ?? [] as $expr) {
                    $w->orWhereRaw("$expr LIKE ?", ["%{$term}%"]);
                }
            });
        }

        $outer = $this->outer($base, $def);
        $totals = $this->totals($outer, $def);
        foreach ($def['order'] as $col) {
            $outer->orderBy($this->prefix($def).$col);
        }
        $rows = $outer->paginate(50)->withQueryString();

        $rangeOptions = ($hasRange && ! $request->user()->isRange())
            ? User::whereIn('id', $this->scopedBase($request, $def, $hasRange)->distinct()->pluck('t.range_id'))->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('admin.registers.index', [
            'key' => $key, 'def' => $def, 'rows' => $rows, 'totals' => $totals, 'filterOptions' => $filterOptions,
            'rangeOptions' => $rangeOptions, 'filters' => $request->query(), 'menu' => $this->menu($request),
        ]);
    }

    public function show(Request $request, string $key, int $id): View
    {
        $def = $this->definition($request, $key);
        abort_if(($def['detail'] ?? true) === false, 404);
        $hasRange = $this->hasRange($def);
        $base = $this->baseQuery($request, $def, $hasRange);
        $outer = $this->outer($base, $def)->where($this->prefix($def).'id', $id);
        $row = $outer->first();
        abort_unless($row, 404);

        $fields = [];
        foreach ((array) $row as $col => $value) {
            if (in_array($col, self::HIDDEN, true) || $value === null || $value === '' || str_starts_with($col, 'extra_') && $value === null) {
                continue;
            }
            $fields[$col === '_range' ? 'Office' : ucfirst(str_replace('_', ' ', $col))] = $value;
        }
        $children = null;
        if ($c = $def['children'] ?? null) {
            $children = ['title' => $c['title'], 'columns' => $c['columns'], 'rows' => DB::table($c['table'])->where($c['fk'], $id)->orderBy($c['order'])->get()];
        }

        return view('admin.registers.show', ['key' => $key, 'def' => $def, 'fields' => $fields, 'children' => $children, 'menu' => $this->menu($request)]);
    }

    /** Register menu for the sidebar / page tabs, filtered by role. */
    public function menu(Request $request): array
    {
        $out = [];
        foreach (config('registers') as $k => $d) {
            if (in_array($request->user()->role, $d['roles'], true)) {
                $out[$d['group']][$k] = $d['title'];
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------------------------------------ internals
    private function definition(Request $request, string $key): array
    {
        $def = config("registers.$key");
        abort_unless($def, 404);
        abort_unless(in_array($request->user()->role, $def['roles'], true), 403);
        $def['filters'] ??= [];

        return $def;
    }

    private function hasRange(array $def): bool
    {
        return Schema::hasColumn($def['table'], 'range_id');
    }

    private function prefix(array $def): string
    {
        return isset($def['computed']) ? 'x.' : 't.';
    }

    private function scopedBase(Request $request, array $def, bool $hasRange): Builder
    {
        $user = $request->user();
        $q = DB::table($def['table'].' as t')->where('t.financial_year_id', CurrentFinancialYear::id());
        $rangeIds = fn () => User::where('division_id', $user->id)->pluck('id')->push($user->id);

        switch ($def['scope']) {
            case 'range':
                $user->isAdmin() ?: $q->whereIn('t.range_id', $user->isDivision() ? $rangeIds() : [$user->id]);
                break;
            case 'range_or_all':
                $user->isAdmin() ?: $q->where(fn ($w) => $w->whereNull('t.range_id')->orWhereIn('t.range_id', $user->isDivision() ? $rangeIds() : [$user->id]));
                break;
            case 'division':
                $user->isAdmin() ?: $q->where('t.division_id', $user->id);
                break;
        }

        return $q;
    }

    private function baseQuery(Request $request, array $def, bool $hasRange): Builder
    {
        $q = $this->scopedBase($request, $def, $hasRange);
        $select = ['t.*', ...array_map(fn ($s) => DB::raw($s), $def['select'] ?? [])];
        if ($hasRange) {
            $q->leftJoin('users as ru', 'ru.id', '=', 't.range_id');
            $select[] = 'ru.name as _range';
        }

        return $q->select($select);
    }

    /** Wraps the base query when computed (derived) columns are defined. */
    private function outer(Builder $base, array $def): Builder
    {
        if (empty($def['computed'])) {
            return $base;
        }
        $extra = collect($def['computed'])->map(fn ($expr, $alias) => "($expr) AS $alias")->implode(', ');

        return DB::query()->fromSub($base, 'x')->selectRaw("x.*, $extra");
    }

    /** @return array<string, float|int> */
    private function totals(Builder $outer, array $def): array
    {
        $parts = ['COUNT(*) AS _count'];
        foreach ($def['sum'] ?? [] as $col) {
            $parts[] = "SUM(y.$col) AS $col";
        }
        $row = DB::query()->fromSub(clone $outer, 'y')->selectRaw(implode(', ', $parts))->first();

        return (array) $row;
    }
}
