<?php
/** Slice 7b verification (registers, budget position, Division/range screens, save flows). Save flows run in a transaction that is ROLLED BACK. */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$pass = $fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    printf("  %s %s%s\n", $ok ? 'OK  ' : 'FAIL', $name, $detail ? "  [$detail]" : '');
}
function call(string $method, string $uri, string $username, array $params = [], bool $json = false): array
{
    global $app, $kernel;
    $user = App\Models\User::where('username', $username)->firstOrFail();
    $app['auth']->guard('web')->setUser($user);
    App\Support\CurrentFinancialYear::forget();
    $req = Request::create($uri, $method, $params);
    $req->setLaravelSession($app['session.store']);
    $req->headers->set('X-CSRF-TOKEN', 'tok');
    $app['session.store']->put('_token', 'tok');
    if ($json) {
        $req->headers->set('Accept', 'application/json');
    }
    try {
        $res = $kernel->handle($req);
    } catch (Throwable $e) {
        return [500, 'EXC: '.$e->getMessage(), $e->getMessage()];
    }
    $err = '';
    if ($res->getStatusCode() >= 500 && isset($res->exception)) {
        $err = strtok($res->exception->getMessage(), "\n");
    }

    return [$res->getStatusCode(), (string) $res->getContent(), $err ?: (string) $res->headers->get('Location')];
}
function recs(string $html): int
{
    preg_match('/- ([\d,]+) records/', $html, $m);

    return (int) str_replace(',', '', $m[1] ?? '-1');
}

$fy = DB::table('financial_years')->where('code', '2026-27')->value('id');
$app['session.store']->put('financial_year_id', $fy);
$uid = fn ($u) => DB::table('users')->where('username', $u)->value('id');
$divId = $uid('division');
$rangeIdsOfDivision = DB::table('users')->where('division_id', $divId)->pluck('id')->push($divId)->all();

// -------------------------------------------------------------------------------------------- A. registers vs DB
echo "== every register: records shown == database (per role)\n";
foreach (config('registers') as $key => $def) {
    foreach (['admin', 'division', 'himatnagar'] as $u) {
        $role = DB::table('users')->where('username', $u)->value('role');
        if (! in_array($role, $def['roles'], true)) {
            [$c] = call('GET', "/registers/$key", $u);
            check("$key: $u ($role) is refused", $c === 403, "HTTP $c");

            continue;
        }
        $q = DB::table($def['table'])->where('financial_year_id', $fy);
        $me = $uid($u);
        if ($role !== 'admin') {
            $ids = $role === 'division' ? $rangeIdsOfDivision : [$me];
            match ($def['scope']) {
                'range' => $q->whereIn('range_id', $ids),
                'range_or_all' => $q->where(fn ($w) => $w->whereNull('range_id')->orWhereIn('range_id', $ids)),
                'division' => $q->where('division_id', $me),
                default => null,
            };
        }
        $expected = $q->count();
        [$c, $html, $err] = call('GET', "/registers/$key", $u);
        check("$key as $u", $c === 200 && recs($html) === $expected, 'shown='.recs($html)." db=$expected".($err ? " ERR $err" : ''));
    }
}

echo "== detail pages\n";
foreach (config('registers') as $key => $def) {
    if (($def['detail'] ?? true) === false) {
        continue;
    }
    $ids = DB::table($def['table'])->where('financial_year_id', $fy)->inRandomOrder()->limit(4)->pluck('id');
    $ok = true;
    if ($ids->isEmpty()) {
        echo "  --   $key: no rows in this year, detail pages not applicable
";
        continue;
    }
    foreach ($ids as $id) {
        [$c, $html] = call('GET', "/registers/$key/$id", 'admin');
        $ok = $ok && $c === 200 && str_contains($html, 'Record details');
    }
    check("$key: 4 random record pages open", $ok);
}
$sor = DB::table('sor_items')->where('financial_year_id', $fy)->whereExists(fn ($q) => $q->select(DB::raw(1))->from('sor_rates')->whereColumn('sor_rates.sor_item_id', 'sor_items.id'))->value('id');
[$c, $html] = call('GET', "/registers/sor/$sor", 'admin');
check('SOR detail lists its dated rates', str_contains($html, 'Rates by effective date'));

echo "== budget position == the sheet's own numbers\n";
[$c, $html] = call('GET', '/registers/budget-position', 'division');
foreach (['From Circle' => 685194758, 'Adjusted' => 685194758, 'To ranges' => 460220577] as $lab => $val) {
    check("total $lab = ".Format::inr($val), str_contains($html, Format::inr($val)));
}
$pend = 685194758 - 460220577;
check('total Pending = '.Format::inr($pend), str_contains($html, Format::inr($pend)));
[$c, $html] = call('GET', '/registers/allotments-to-range', 'division');
check('allotments to ranges total = 4,60,220,577', str_contains($html, Format::inr(460220577)));
[$c, $html] = call('GET', '/registers/allotments-to-range', 'himatnagar');
$himSum = DB::table('allotments_to_range')->where('financial_year_id', $fy)->where('range_id', $uid('himatnagar'))->sum('amount');
check('a range sees only its own allotment total', str_contains($html, Format::inr($himSum)), Format::inr($himSum));

// -------------------------------------------------------------------------------------------- B. existing screens render with data
echo "== existing screens open with real data\n";
$screens = [
    'division' => ['/division/parties', '/division/parties/create', '/division/cash-accounts', '/division/cash-account', '/division/allotments-from-circle', '/division/allotment-from-circle',
        '/division/allotment-to-range', '/division/allotment-adjustments', '/division/allotment-adjustment', '/division/lc-entries', '/division/lc-entry'],
    'himatnagar' => ['/finance/registration/parties', '/finance/registration/party', '/finance/registration/wl-beneficiaries', '/finance/registration/wl-beneficiary', '/finance/registration/sf-beneficiaries', '/finance/registration/sf-beneficiary'],
    'admin' => ['/admin/budget-codes', '/admin/budget-codes/create'],
];
foreach ($screens as $u => $paths) {
    foreach ($paths as $p) {
        [$c, $html, $err] = call('GET', $p, $u);
        check("$u $p", $c === 200, $c === 200 ? '' : "HTTP $c $err");
    }
}
$m = fn ($model) => $model::query()->orderBy('id')->first();
foreach ([['division', '/division/allotment-from-circle/%d/edit', App\Models\AllotmentFromCircle::class], ['division', '/division/allotment-adjustment/%d/edit', App\Models\AllotmentAdjustment::class], ['division', '/division/lc-entry/%d/edit', App\Models\LcEntry::class],
    ['division', '/division/parties/%d/edit', App\Models\DivisionTenderParty::class], ['himatnagar', '/finance/registration/party/%d/edit', App\Models\PartyRegistration::class, 'range'],
    ['himatnagar', '/finance/registration/wl-beneficiary/%d/edit', App\Models\WlBeneficiary::class, 'range'], ['himatnagar', '/finance/registration/sf-beneficiary/%d/edit', App\Models\SfBeneficiary::class, 'range']] as $row) {
    [$u, $path, $model] = $row;
    $rec = isset($row[3]) ? $model::whereIn('range_id', DB::table('users')->where('role', 'range')->pluck('id'))->first() : $model::first();
    if (isset($row[3])) {
        $u = DB::table('users')->where('id', $rec->range_id)->value('username');
    }
    [$c, $html, $err] = call('GET', sprintf($path, $rec->id), $u);
    check("$u edit form ".class_basename($model), $c === 200, $c === 200 ? '' : "HTTP $c $err");
}
[$c, $html] = call('GET', '/division/allotments-from-circle', 'division');
$someCode = DB::table('allotments_from_circle')->where('financial_year_id', $fy)->orderByDesc('source_time_stamp')->first();
$code = DB::table('budget_codes')->where('id', $someCode->budget_code_id)->value('budget_code');
check('Allotment-from-Circle list shows the newest entry first', str_contains($html, '>'.$code.'<'), $code);

$ref = new ReflectionMethod(App\Http\Controllers\Admin\Finance\PartyRegistrationController::class, 'banks');
$ref->setAccessible(true);
$app['auth']->guard('web')->setUser(App\Models\User::where('username', 'division')->first());
App\Support\CurrentFinancialYear::forget();
$bankList = $ref->invoke(new App\Http\Controllers\Admin\Finance\PartyRegistrationController());
$unsaveable = DB::table('party_registrations')->where('financial_year_id', $fy)->whereNotNull('bank_name')->whereNotIn('bank_name', $bankList)->count();
check('every migrated party can be saved: its bank is in the allowed list', $unsaveable === 0, "parties with an unlisted bank: $unsaveable");

// -------------------------------------------------------------------------------------------- C. save flows (rolled back)
echo "== save flows (inside a transaction, rolled back at the end)\n";
DB::beginTransaction();
try {
    $bc = DB::table('budget_codes')->where('financial_year_id', $fy)->where('budget_code', 'A3')->first();
    $pos = fn () => App\Support\BudgetPosition::for($divId, $bc->id);
    App\Support\CurrentFinancialYear::forget();
    $before = $pos();
    [$c, $h, $err] = call('POST', '/division/allotment-from-circle', 'division', ['budget_code' => 'A3', 'allotment_date' => '2026-11-25', 'rate' => 0, 'target' => 0, 'allotment' => 900000, '_token' => 'tok']);
    $after = $pos();
    check('circle entry saved and becomes the CURRENT allotment (latest, not sum)', in_array($c, [302, 303]) && $after['circle'] == 900000.0 && $before['circle'] != 900000.0, 'A3 circle '.$before['circle'].' -> '.$after['circle']);
    $ledgerRows = DB::table('allotments_from_circle')->where('budget_code_id', $bc->id)->count();
    check('previous Circle entries are kept (ledger)', $ledgerRows >= 2, "rows=$ledgerRows");

    $pending = $after['pending'];
    [$c] = call('POST', '/division/allotment-to-range', 'division', ['budget_code_id' => $bc->id, 'allocations' => [['range_id' => $uid('himatnagar'), 'target' => 0, 'allotment' => $pending + 1000]], '_token' => 'tok'], true);
    check('distributing MORE than pending is refused', $c === 422, "HTTP $c pending=$pending");
    $beforeRange = (float) DB::table('allotments_to_range')->where('budget_code_id', $bc->id)->where('range_id', $uid('himatnagar'))->sum('amount');
    [$c] = call('POST', '/division/allotment-to-range', 'division', ['budget_code_id' => $bc->id, 'remark' => 'verify', 'allocations' => [['range_id' => $uid('himatnagar'), 'target' => 0, 'allotment' => 1000]], '_token' => 'tok']);
    $afterRange = (float) DB::table('allotments_to_range')->where('budget_code_id', $bc->id)->where('range_id', $uid('himatnagar'))->sum('amount');
    check('valid distribution saved as an INCREMENT (range total = old + 1000)', in_array($c, [302, 303]) && abs($afterRange - $beforeRange - 1000) < 0.01, "$beforeRange -> $afterRange");

    $other = DB::table('budget_codes')->where('financial_year_id', $fy)->where('budget_code', 'A6')->first();
    $pend = App\Support\BudgetPosition::for($divId, $bc->id)['pending'];
    [$c] = call('POST', '/division/allotment-adjustment', 'division', ['from_budget_code_id' => $bc->id, 'to_budget_code_id' => $other->id, 'amount' => $pend + 5000, '_token' => 'tok'], true);
    check('adjustment larger than pending is refused', $c === 422, "HTTP $c");
    $adjBefore = App\Support\BudgetPosition::for($divId, $other->id)['adjusted'];
    [$c] = call('POST', '/division/allotment-adjustment', 'division', ['from_budget_code_id' => $bc->id, 'to_budget_code_id' => $other->id, 'amount' => 2500, 'remark' => 'verify', '_token' => 'tok']);
    $adjAfter = App\Support\BudgetPosition::for($divId, $other->id)['adjusted'];
    check('valid adjustment raises the receiving code by exactly the amount', in_array($c, [302, 303]) && abs($adjAfter - $adjBefore - 2500) < 0.01, "$adjBefore -> $adjAfter");

    [$c] = call('POST', '/division/cash-account', 'division', ['cash_account_month' => '2026-11', 'designation' => 'D.C.F', 'round_range' => 'Division', 'recovery_details' => 'verify', 'recoverer_designation' => 'D.C.F', 'recovery_officer_name' => 'T', 'recovery_type' => 'Forest Deposit', 'recovery_date' => '2026-11-20', 'bank_deposit_date' => '2026-11-21', 'bank_name_branch' => 'SBI', 'challan_no' => '123', 'amount' => 777, '_token' => 'tok']);
    $cash = DB::table('cash_accounts')->where('challan_no', '123')->where('amount', 777)->first();
    check('cash account saved with sheet-style month text', in_array($c, [302, 303]) && $cash && $cash->cash_account_month === 'Nov-26' && $cash->financial_year_id == $fy, $cash->cash_account_month ?? 'none');

    [$c] = call('POST', '/division/lc-entry', 'division', ['scheme' => '02- Divisional', 'class' => 'Class-2', 'entry_date' => '2026-11-25', 'amount' => 1234, '_token' => 'tok']);
    $lc = DB::table('lc_entries')->where('amount', 1234)->first();
    check('L.C. entry saved in the current year', in_array($c, [302, 303]) && $lc && $lc->financial_year_id == $fy);

    [$c, $body] = call('POST', '/finance/registration/party', 'himatnagar', ['party_code' => 'ZZ/1', 'round' => 'Himmatnagar SF', 'party_name' => 'VERIFY PARTY', 'bank_name' => 'State Bank of India', 'party_status' => 'Active', 'approved_percent' => 100, 'small_description' => 'Test', '_token' => 'tok'], true);
    $party = DB::table('party_registrations')->where('party_name', 'VERIFY PARTY')->first();
    check('party registered for the range in the current year', $c < 300 && $party && $party->range_id == $uid('himatnagar') && $party->financial_year_id == $fy && $party->serial_number > 0, "HTTP $c ".mb_substr($body, 0, 120));
    $cnt = DB::table('party_registrations')->where('financial_year_id', $fy)->where('range_id', $uid('himatnagar'))->count();
    [$c, $html] = call('GET', '/finance/registration/parties', 'himatnagar');
    check('new party appears in the range list', str_contains($html, 'VERIFY PARTY'));
} finally {
    DB::rollBack();
}
$left = DB::table('party_registrations')->where('party_name', 'VERIFY PARTY')->count() + DB::table('cash_accounts')->where('challan_no', '123')->where('amount', 777)->count();
check('rollback left no test rows behind', $left === 0);
$fy27 = DB::table('financial_years')->where('code', '2026-27')->value('id');
check('allotment ledger counts of 2026-27 unchanged after rollback (311 / 3,343 / 109)', DB::table('allotments_from_circle')->where('financial_year_id', $fy27)->count() === 311 && DB::table('allotments_to_range')->where('financial_year_id', $fy27)->count() === 3343 && DB::table('allotment_adjustments')->where('financial_year_id', $fy27)->count() === 109);

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
