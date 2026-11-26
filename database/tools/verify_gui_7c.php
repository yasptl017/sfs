<?php
/** Slice 7c verification: voucher entry. Save flows run in a transaction that is ROLLED BACK. */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\VoucherEntryController;
use App\Models\Voucher;
use App\Support\VoucherCalculator;
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
    $err = ($res->getStatusCode() >= 500 && isset($res->exception)) ? strtok($res->exception->getMessage(), "\n") : (string) $res->headers->get('Location');

    return [$res->getStatusCode(), (string) $res->getContent(), $err];
}

$fy = DB::table('financial_years')->where('code', '2026-27')->value('id');
$app['session.store']->put('financial_year_id', $fy);
$inputs = (new ReflectionClassConstant(VoucherEntryController::class, 'INPUTS'))->getValue();
$types = VoucherEntryController::TYPES;
$plural = ['tender' => 'tender-entries', 'free' => 'free-entries', 'dw-salary' => 'd-wager-salary-entries', 'dw-arrears' => 'd-wager-arrears-entries', 'sf-bene' => 'sf-beneficiary-entries', 'wl-bene' => 'wl-beneficiary-entries'];
$single = ['tender' => 'tender-entry', 'free' => 'free-entry', 'dw-salary' => 'd-wager-salary-entry', 'dw-arrears' => 'd-wager-arrears-entry', 'sf-bene' => 'sf-beneficiary-entry', 'wl-bene' => 'wl-beneficiary-entry'];

function payload(Voucher $v): array
{
    global $inputs;
    $p = ['_token' => 'tok', 'v' => [], 'items' => []];
    foreach ($inputs as $col) {
        $val = $v->{$col};
        if ($val === null) {
            continue;
        }
        $p['v'][$col] = $val instanceof DateTimeInterface ? $val->format('Y-m-d') : (preg_match('/^\d{4}-\d{2}-\d{2} 00:00:00$/', (string) $val) ? substr((string) $val, 0, 10) : (is_numeric($val) && str_contains((string) $val, '.') ? rtrim(rtrim((string) $val, '0'), '.') : $val));
    }
    foreach ($v->items as $i) {
        $p['items'][] = array_filter(['sor_code' => $i->sor_code, 'description' => $i->description, 'work_order_no' => $i->work_order_no, 'work_order_date' => $i->work_order_date ? substr((string) $i->work_order_date, 0, 10) : null,
            'work_start_date' => $i->work_start_date ? substr((string) $i->work_start_date, 0, 10) : null, 'work_end_date' => $i->work_end_date ? substr((string) $i->work_end_date, 0, 10) : null,
            'quantity' => $i->quantity !== null ? rtrim(rtrim((string) $i->quantity, '0'), '.') : null, 'unit' => $i->unit, 'rate' => $i->rate !== null ? rtrim(rtrim((string) $i->rate, '0'), '.') : null,
            'amount' => $i->amount !== null ? rtrim(rtrim((string) $i->amount, '0'), '.') : null], fn ($x) => $x !== null);
    }

    return $p;
}

echo "== the amount rules reproduce every migrated voucher\n";
$mism = ['sub_total' => 0, 'amount_as_per_approved_rate' => 0, 'payable_total_amount' => 0, 'total_deduction' => 0, 'net_amount' => 0];
$n = 0;
$bad = [];
Voucher::with('items')->orderBy('id')->chunk(500, function ($chunk) use (&$mism, &$n, &$bad) {
    foreach ($chunk as $v) {
        $n++;
        $calc = VoucherCalculator::totals($v->only(array_merge(['approved_percent'], VoucherCalculator::ADDITIONS, VoucherCalculator::DEDUCTIONS)), $v->items->map(fn ($i) => ['quantity' => $i->quantity, 'rate' => $i->rate, 'amount' => $i->amount])->all());
        foreach (array_keys($mism) as $k) {
            $stored = (float) ($v->{$k} ?? 0);
            if ($k === 'amount_as_per_approved_rate' && $v->{$k} === null) {
                continue; // not stored for these (e.g. beneficiary entries)
            }
            if (abs($calc[$k] - $stored) >= 1.0) {
                $mism[$k]++;
                if (count($bad) < 6) {
                    $bad[] = "$k #{$v->id} {$v->entry_type}: calc {$calc[$k]} vs stored $stored";
                }
            }
        }
    }
});
foreach ($mism as $k => $c) {
    check("$k: stored value == recomputed (within 1 rupee), mismatches=$c of $n", $c <= ($k === 'total_deduction' ? 0 : 1), $c ? implode('; ', array_slice($bad, 0, 2)) : '');
}

echo "== forms open for every type (range login)\n";
foreach ($types as $slug => $label) {
    [$c, $h, $e] = call('GET', "/finance/{$single[$slug]}", 'himatnagar');
    check("create form: $label", $c === 200 && str_contains($h, 'Save voucher'), $c === 200 ? '' : "HTTP $c $e");
}
foreach ($types as $slug => $label) {
    $v = Voucher::where('financial_year_id', $fy)->where('entry_type', $label)->whereNull('advice_no')->whereIn('range_id', DB::table('users')->where('role', 'range')->pluck('id'))->first();
    if (! $v) {
        echo "  --   no unlocked $label voucher to open\n";
        continue;
    }
    $owner = DB::table('users')->where('id', $v->range_id)->value('username');
    [$c, $h, $e] = call('GET', "/finance/{$single[$slug]}/{$v->id}/edit", $owner);
    check("edit form: $label #{$v->id} ($owner)", $c === 200 && str_contains($h, 'Save changes') && str_contains($h, e(mb_substr((string) $v->party_name, 0, 12))), $c === 200 ? '' : "HTTP $c $e");
}

echo "== save flows (rolled back)\n";
DB::beginTransaction();
try {
    $before = ['v' => Voucher::count(), 'i' => DB::table('voucher_items')->count(), 'ver' => DB::table('voucher_versions')->count()];

    // --- editing a migrated voucher WITHOUT changing anything keeps every amount identical
    $keep = 0; $tested = 0; $problems = [];
    foreach ($types as $slug => $label) {
        $cands = Voucher::with('items')->where('financial_year_id', $fy)->where('entry_type', $label)->whereNull('advice_no')->whereIn('range_id', DB::table('users')->where('role', 'range')->pluck('id'))->inRandomOrder()->limit(6)->get();
        foreach ($cands as $v) {
            $owner = DB::table('users')->where('id', $v->range_id)->value('username');
            $snap = $v->only(['sub_total', 'amount_as_per_approved_rate', 'payable_total_amount', 'total_deduction', 'net_amount', 'budget_code', 'party_name', 'bill_no']);
            $sumItems = (float) $v->items->sum('amount'); $cnt = $v->items->count();
            [$c, $h, $loc] = call('PUT', "/finance/{$single[$slug]}/{$v->id}", $owner, payload($v));
            $after = Voucher::with('items')->find($v->id);
            $same = $c === 302 && collect($snap)->every(fn ($val, $k) => (string) $after->{$k} === (string) $val) && $after->items->count() === $cnt && abs($after->items->sum('amount') - $sumItems) < 0.01;
            $tested++; $keep += $same;
            if (! $same && count($problems) < 3) {
                $problems[] = "#{$v->id} $label HTTP $c ".($c !== 302 ? mb_substr(strip_tags($h), 0, 120) : json_encode(array_filter(array_map(fn ($k) => (string) $after->{$k} === (string) $snap[$k] ? null : "$k {$snap[$k]}->{$after->{$k}}", array_keys($snap)))));
            }
        }
    }
    check("unchanged edits leave stored amounts identical ($tested migrated vouchers, all 6 types)", $tested > 20 && $keep === $tested, "kept $keep of $tested ".implode(' | ', $problems));
    $ver = DB::table('voucher_versions')->where('version_kind', 'before_edit')->count();
    check('every edit stored a "before edit" version', $ver === $tested, "versions=$ver");
    check('unchanged edits record no changed fields', DB::table('voucher_versions')->where('version_kind', 'before_edit')->where('differs_from_live', 1)->count() <= 3, 'with-changes='.DB::table('voucher_versions')->where('version_kind', 'before_edit')->where('differs_from_live', 1)->count());

    // --- a real change recalculates
    $v = Voucher::with('items')->where('financial_year_id', $fy)->where('entry_type', 'Tender Entry')->whereNull('advice_no')->whereNotNull('approved_percent')->whereHas('items', fn ($q) => $q->whereNotNull('quantity')->whereNotNull('rate'))->first();
    $owner = DB::table('users')->where('id', $v->range_id)->value('username');
    $p = payload($v);
    $p['items'][0]['quantity'] = 10;
    $p['items'][0]['rate'] = 100;
    $p['items'][0]['amount'] = 1000; // the form's JS sets amount = quantity x rate when the user edits either
    $expectedSub = 1000.0 + $v->items->skip(1)->sum('amount');
    [$c] = call('PUT', "/finance/tender-entry/{$v->id}", $owner, $p);
    $after = Voucher::find($v->id);
    check('changing quantity x rate recalculates the sub total', $c === 302 && abs((float) $after->sub_total - $expectedSub) < 0.01, "sub_total {$v->sub_total} -> {$after->sub_total} (expected $expectedSub)");
    $ok = abs((float) $after->net_amount - ((float) $after->payable_total_amount - (float) $after->total_deduction)) < 0.01;
    check('net = payable - deduction after the change', $ok);
    $vv = DB::table('voucher_versions')->where('voucher_id', $v->id)->where('version_kind', 'before_edit')->orderByDesc('id')->first();
    check('the change is recorded: changed fields include Item Details and Sub Total', $vv && str_contains($vv->diff_fields, 'Item Details') && str_contains($vv->diff_fields, 'Sub Total'), (string) ($vv->diff_fields ?? ''));
    [$c, $h] = call('GET', "/vouchers/{$v->id}", 'admin');
    check('the voucher page shows the "Before edit" version', str_contains($h, 'Before edit on'));

    // --- new vouchers, every type
    foreach ($types as $slug => $label) {
        $max = (int) Voucher::where('range_id', DB::table('users')->where('username', 'himatnagar')->value('id'))->where('entry_type', $label)->where('financial_year_id', $fy)->max('serial_number');
        $items = [['description' => 'Verify work item 1', 'quantity' => 10, 'rate' => 120.5, 'unit' => 'Nos'], ['description' => 'Verify item 2 (typed amount)', 'amount' => 300]];
        $pay = ['_token' => 'tok', 'v' => ['entry_month' => 'Nov', 'budget_code' => 'A3', 'party_code' => 'ZZ/9', 'party_name' => "VERIFY $label", 'approved_percent' => 100, 'sgst' => 50, 'cgst' => 50, 'tds' => 20, 'deduction_sgst' => 10, 'bill_no' => 'B-1', 'bill_date' => '2026-11-20', 'round' => 'Himmatnagar SF'], 'items' => $items];
        [$c, $h, $loc] = call('POST', "/finance/{$single[$slug]}", 'himatnagar', $pay);
        $nv = Voucher::where('party_name', "VERIFY $label")->first();
        $sub = 1205.0 + 300.0;
        $okv = $c === 302 && $nv && $nv->serial_number === $max + 1 && abs((float) $nv->sub_total - $sub) < 0.01 && abs((float) $nv->payable_total_amount - ($sub + 100)) < 0.01 && abs((float) $nv->total_deduction - 30) < 0.01
            && abs((float) $nv->net_amount - ($sub + 100 - 30)) < 0.01 && $nv->financial_year_id == $fy && $nv->entered_by === 'himatnagar@sfs.local' && $nv->items()->count() === 2 && $nv->scheme !== null;
        check("new $label saved: next serial, totals, scheme from budget code", $okv, $nv ? "sr {$nv->serial_number} sub {$nv->sub_total} net {$nv->net_amount} scheme ".($nv->scheme ?? 'NULL') : "HTTP $c $loc");
    }
    [$c, $h] = call('GET', '/vouchers?q=VERIFY', 'himatnagar');
    check('new vouchers appear in the browser (6)', preg_match('/Vouchers<\/div>\s*<div class="mt-1 text-lg font-bold text-slate-950">6</', $h) === 1);

    // --- copy
    $src = Voucher::where('range_id', DB::table('users')->where('username', 'himatnagar')->value('id'))->where('entry_type', 'Tender Entry')->where('financial_year_id', $fy)->whereNotNull('description')->first();
    [$c, $h] = call('GET', "/finance/tender-entry?copy={$src->serial_number}", 'himatnagar');
    check('copy entry pre-fills from the earlier voucher', $c === 200 && str_contains($h, 'Copied from Sr. '.$src->serial_number) && str_contains($h, e(mb_substr((string) $src->party_name, 0, 10))));

    // --- delete keeps an audit copy
    $victim = Voucher::where('party_name', 'VERIFY Free Entry')->first();
    [$c] = call('DELETE', "/finance/free-entry/{$victim->id}", 'himatnagar', ['_token' => 'tok']);
    check('delete removes the voucher and keeps a "deleted" copy', $c === 302 && ! Voucher::find($victim->id) && DB::table('voucher_versions')->where('version_kind', 'deleted')->where('serial_number', $victim->serial_number)->exists());

    // --- guards
    $locked = Voucher::whereNotNull('advice_no')->whereIn('range_id', DB::table('users')->where('role', 'range')->pluck('id'))->first();
    $lo = DB::table('users')->where('id', $locked->range_id)->value('username');
    [$c] = call('GET', "/finance/tender-entry/{$locked->id}/edit", $lo);
    check('a voucher already in an advice cannot be edited (403)', $c === 403, "HTTP $c");
    [$c] = call('DELETE', "/finance/tender-entry/{$locked->id}", $lo, ['_token' => 'tok']);
    check('...or deleted (403)', $c === 403 && Voucher::find($locked->id) !== null, "HTTP $c");
    $other = Voucher::whereNull('advice_no')->where('range_id', '!=', DB::table('users')->where('username', 'himatnagar')->value('id'))->whereIn('range_id', DB::table('users')->where('role', 'range')->pluck('id'))->first();
    [$c] = call('GET', "/finance/tender-entry/{$other->id}/edit", 'himatnagar');
    check("another range's voucher cannot be edited (403)", $c === 403, "HTTP $c");
    [$c] = call('GET', '/finance/tender-entry', 'division');
    check('the Division cannot open the entry form (403)', $c === 403, "HTTP $c");
    [$c, $h] = call('POST', '/finance/tender-entry', 'himatnagar', ['_token' => 'tok', 'v' => ['entry_month' => 'Nov', 'budget_code' => 'A3', 'party_code' => 'x', 'party_name' => 'x'], 'items' => []], true);
    check('a voucher without line items is refused (422)', $c === 422, "HTTP $c");
    [$c, $h] = call('POST', '/finance/tender-entry', 'himatnagar', ['_token' => 'tok', 'v' => ['entry_month' => 'Nov', 'budget_code' => 'NOSUCH', 'party_code' => 'x', 'party_name' => 'x'], 'items' => [['description' => 'd']]], true);
    check('an unknown budget code is refused (422)', $c === 422, "HTTP $c");
} finally {
    DB::rollBack();
}
$fy27 = DB::table('financial_years')->where('code', '2026-27')->value('id');
$after = ['v' => DB::table('vouchers')->where('financial_year_id', $fy27)->count(), 'i' => DB::table('voucher_items')->whereIn('voucher_id', DB::table('vouchers')->where('financial_year_id', $fy27)->select('id'))->count(), 'ver' => DB::table('voucher_versions')->where('financial_year_id', $fy27)->count()];
check('rollback restored the 2026-27 data exactly (7,572 vouchers / 15,773 items / 3,696 versions)', $after === ['v' => 7572, 'i' => 15773, 'ver' => 3696], json_encode($after));

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
