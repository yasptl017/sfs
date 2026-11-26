<?php
/** Slice 7a verification: the browser shows exactly what the database holds, scoped by role.  php database/tools/verify_gui_7a.php */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$pass = $fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    printf("  %s %s%s\n", $ok ? 'OK  ' : 'FAIL', $name, $detail ? "  [$detail]" : '');
}
function call(string $method, string $uri, string $username, array $params = []): array
{
    global $app, $kernel;
    $user = App\Models\User::where('username', $username)->firstOrFail();
    $app['auth']->guard('web')->setUser($user);
    App\Support\CurrentFinancialYear::forget();
    $req = Request::create($uri, $method, $params);
    $req->setLaravelSession($app['session.store']);
    $req->headers->set('X-CSRF-TOKEN', 'tok');
    $app['session.store']->put('_token', 'tok');
    $t = microtime(true);
    $res = $kernel->handle($req);

    return [$res->getStatusCode(), (string) $res->getContent(), round((microtime(true) - $t) * 1000), $res->headers->get('Location')];
}
function shown(string $html): int
{
    preg_match('/Vouchers<\/div>\s*<div class="mt-1 text-lg font-bold text-slate-950">([\d,]+)</', $html, $m);

    return (int) str_replace(',', '', $m[1] ?? '-1');
}

$fy = DB::table('financial_years')->where('code', '2026-27')->value('id');
$app['session.store']->put('financial_year_id', $fy);
$him = DB::table('users')->where('username', 'himatnagar')->value('id');
$idar = DB::table('users')->where('username', 'idar')->value('id');

echo "== counts shown vs database\n";
foreach (['division' => null, 'admin' => null, 'himatnagar' => $him, 'idar' => $idar] as $u => $rid) {
    [$code, $html, $ms] = call('GET', '/vouchers', $u);
    $db = $rid ? DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $rid)->count() : DB::table('vouchers')->where('financial_year_id', $fy)->count();
    check("$u sees all of its vouchers", $code === 200 && shown($html) === $db, 'page='.shown($html)." db=$db {$ms}ms");
}

echo "== entry-type filters (division)\n";
foreach (DB::table('vouchers')->where('financial_year_id', $fy)->distinct()->pluck('entry_type') as $t) {
    [$code, $html] = call('GET', '/vouchers', 'division', ['entry_type' => $t]);
    $db = DB::table('vouchers')->where('financial_year_id', $fy)->where('entry_type', $t)->count();
    check("type '$t'", shown($html) === $db, 'page='.shown($html)." db=$db");
}

echo "== money totals (division, all) vs SQL\n";
[$c, $html] = call('GET', '/vouchers', 'division');
$net = DB::table('vouchers')->where('financial_year_id', $fy)->sum('net_amount');
check('net amount card equals SUM(net_amount)', str_contains($html, App\Support\Format::inr($net)), App\Support\Format::inr($net));

echo "== range filter + role scoping\n";
[$c, $html] = call('GET', '/vouchers', 'division', ['range_id' => $idar]);
check('division filtered to Idar', shown($html) === DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $idar)->count());
[$c, $html] = call('GET', '/vouchers', 'himatnagar', ['range_id' => $idar]);
check('a range cannot widen its scope with ?range_id=', shown($html) === DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him)->count());
$idarVoucher = DB::table('vouchers')->where('range_id', $idar)->value('id');
[$c] = call('GET', "/vouchers/$idarVoucher", 'himatnagar');
check('Himatnagar opening an Idar voucher -> 403', $c === 403, "HTTP $c");
[$c] = call('GET', "/vouchers/$idarVoucher", 'idar');
check('Idar opening its own voucher -> 200', $c === 200, "HTTP $c");
[$c] = call('GET', "/vouchers/$idarVoucher", 'division');
check('Division opening an Idar voucher -> 200', $c === 200, "HTTP $c");

echo "== search\n";
foreach (DB::table('vouchers')->where('financial_year_id', $fy)->whereNotNull('voucher_no_final')->inRandomOrder()->limit(5)->get(['id', 'voucher_no_final']) as $v) {
    // narrow by the voucher's own range too so the hit is on page 1
    $rid = DB::table('vouchers')->where('id', $v->id)->value('range_id');
    [$c, $html] = call('GET', '/vouchers', 'division', ['q' => $v->voucher_no_final, 'range_id' => $rid]);
    check("search final voucher no '{$v->voucher_no_final}' finds voucher {$v->id}", str_contains($html, route('vouchers.show', $v->id)));
}
[$c, $html] = call('GET', '/vouchers', 'division', ['q' => 'zzqqNoSuchText']);
check('search with no match shows empty state', str_contains($html, 'No vouchers match'));

echo "== pagination\n";
[$c, $html] = call('GET', '/vouchers', 'division', ['page' => 3]);
check('page 3 renders 50 rows', substr_count($html, 'text-[11px]" href=') === 50, 'rows='.substr_count($html, 'text-[11px]" href='));

echo "== detail pages\n";
foreach (DB::table('vouchers')->where('only_in_older_export', 1)->get(['id']) as $f) {
    [$c, $html] = call('GET', "/vouchers/{$f->id}", 'division');
    check("flagged voucher {$f->id} shows warning + items", $c === 200 && str_contains($html, 'Only in older export') && str_contains($html, 'Line items'));
}
$okAll = true;
foreach (DB::table('voucher_versions')->where('differs_from_live', 1)->inRandomOrder()->limit(15)->pluck('voucher_id')->unique() as $vid) {
    [$c, $html] = call('GET', "/vouchers/$vid", 'admin');
    $okAll = $okAll && $c === 200 && str_contains($html, 'Earlier versions');
}
check('15 vouchers with changed older versions render the version section', $okAll);
$okAll = true;
$bad = [];
foreach (DB::table('vouchers')->inRandomOrder()->limit(40)->get(['id', 'party_name', 'net_amount']) as $v) {
    [$c, $html] = call('GET', "/vouchers/{$v->id}", 'admin');
    if ($c !== 200 || ! str_contains($html, App\Support\Format::inr($v->net_amount)) || ! str_contains($html, e($v->party_name))) {
        $okAll = false;
        $bad[] = $v->id;
    }
}
check('40 random vouchers show their party name and net amount', $okAll, $bad ? 'bad ids '.implode(',', $bad) : '');
$okItems = true;
foreach (DB::table('vouchers')->inRandomOrder()->limit(25)->pluck('id') as $vid) {
    [$c, $html] = call('GET', "/vouchers/$vid", 'admin');
    $n = DB::table('voucher_items')->where('voucher_id', $vid)->count();
    $okItems = $okItems && str_contains($html, "Line items ($n)");
}
check('line-item counts shown equal the database (25 vouchers)', $okItems);

echo "== legacy menu entries redirect into the browser\n";
[$c, $html, $ms, $loc] = call('GET', '/finance/tender-entries', 'himatnagar');
check('Tender Entry menu -> unified list', $c === 302 && str_contains((string) $loc, 'entry_type=Tender'), (string) $loc);
[$c, $html] = call('GET', '/vouchers', 'himatnagar', ['entry_type' => 'Tender Entry']);
check('Himatnagar tender entries shown == database', shown($html) === DB::table('vouchers')->where('financial_year_id', App\Support\CurrentFinancialYear::id())->where('range_id', $him)->where('entry_type', 'Tender Entry')->count(), 'page='.shown($html));

echo "== financial-year switch\n";
$fy25 = DB::table('financial_years')->where('code', '2025-26')->value('id');
[$c, $h, $ms, $loc] = call('POST', '/financial-year', 'division', ['financial_year_id' => $fy25, '_token' => 'tok']);
check('switch to 2025-26 accepted', in_array($c, [302, 303]), "HTTP $c");
[$c, $html] = call('GET', '/vouchers', 'division');
check('2025-26 shows what the DB holds for that year (no vouchers exist for it)', shown($html) === DB::table('vouchers')->where('financial_year_id', $fy25)->count(), 'page='.shown($html));

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
