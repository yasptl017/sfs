<?php
/** Slice 7d verification: position report vs the sheet, voucher print, Division advice workflow. Mutations are ROLLED BACK. */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Controllers\Admin\PositionReportController;
use App\Models\Voucher;
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
    $err = ($res->getStatusCode() >= 500 && isset($res->exception)) ? strtok($res->exception->getMessage(), "\n") : (string) $res->headers->get('Location');

    return [$res->getStatusCode(), (string) $res->getContent(), $err];
}

$fy = DB::table('financial_years')->where('code', '2026-27')->value('id');
$app['session.store']->put('financial_year_id', $fy);
$app['auth']->guard('web')->setUser(App\Models\User::where('username', 'division')->first());
App\Support\CurrentFinancialYear::forget();

echo "== allotment vs expenditure == the sheet's Budget Allotment (code x office)\n";
$sid = DB::table('staging_sheets')->where('source_file', 'like', 'Master Data SF Sabarkantha 26-27%')->where('sheet_name', 'Budget Allotment')->value('id');
$srows = DB::table('staging_rows')->where('staging_sheet_id', $sid)->orderBy('row_no')->get(['row_no', 'cells'])->map(fn ($r) => [$r->row_no, json_decode($r->cells, true)]);
$row1 = $srows[0][1];
$blocks = [];
for ($i = 15; $i < count($row1); $i++) {
    if (is_string($row1[$i] ?? null) && ! in_array($row1[$i], ['Phys. Target', 'Allotment'], true)) {
        $blocks[$row1[$i]] = $i;
    }
}
$sheet = [];
foreach ($srows as [$rn, $c]) {
    if ($rn >= 4 && ($c[0] ?? null) !== null) {
        foreach ($blocks as $rng => $col) {
            $sheet[$c[0].'|'.$rng] = [(float) ($c[$col + 1] ?? 0), (float) ($c[$col + 2] ?? 0), (float) ($c[$col + 3] ?? 0)]; // allotment, booked, entered
        }
    }
}
$mine = [];
foreach (PositionReportController::rows($fy, null, false) as $r) {
    $mine[$r->budget_code.'|'.($r->range_name === 'SF Sabarkantha Division' ? 'Division' : $r->range_name)] = [$r->allotment, $r->booked, $r->entered];
}
foreach (['allotment' => 0, 'expenditure booked' => 1, 'data entered' => 2] as $lab => $i) {
    $eq = 0;
    foreach ($sheet as $k => $v) {
        $eq += abs(($mine[$k][$i] ?? 0) - $v[$i]) < 1.0;
    }
    check("$lab: report == sheet in all ".count($sheet).' code x office cells', $eq === count($sheet), "$eq equal; total report=".number_format(array_sum(array_column($mine, $i)), 0).' sheet='.number_format(array_sum(array_column($sheet, $i)), 0));
}
$withOlder = array_sum(array_map(fn ($r) => $r->entered, PositionReportController::rows($fy)));
$flagged = (float) DB::table('vouchers')->where('financial_year_id', $fy)->where('only_in_older_export', 1)->sum('payable_total_amount');
check('default report = sheet + the 7 flagged vouchers (shown transparently)', abs($withOlder - array_sum(array_column($mine, 2)) - $flagged) < 1, 'flagged '.number_format($flagged, 0));
[$c, $h] = call('GET', '/reports/position', 'division');
check('page: division sees the totals', $c === 200 && str_contains($h, Format::inr($withOlder)) && str_contains($h, 'only in older export'));
$him = DB::table('users')->where('username', 'himatnagar')->value('id');
$expHim = collect(PositionReportController::rows($fy, [$him]))->sum('entered');
[$c, $h] = call('GET', '/reports/position', 'himatnagar');
check('page: a range sees only its own figures', $c === 200 && str_contains($h, Format::inr($expHim)) && ! str_contains($h, '<th>Office</th>'));

echo "== voucher print\n";
$ok = true;
foreach (Voucher::inRandomOrder()->limit(12)->get() as $v) {
    [$c, $h] = call('GET', "/vouchers/{$v->id}/print", 'admin');
    $ok = $ok && $c === 200 && str_contains($h, Format::inr($v->net_amount)) && str_contains($h, 'Net amount payable');
}
check('12 random vouchers print with their net amount', $ok);
$idarV = Voucher::where('range_id', DB::table('users')->where('username', 'idar')->value('id'))->first();
[$c] = call('GET', "/vouchers/{$idarV->id}/print", 'himatnagar');
check("another range's voucher cannot be printed (403)", $c === 403, "HTTP $c");

echo "== Division advice workflow (rolled back)\n";
[$c, $h] = call('GET', '/division/advices', 'division');
$nAdv = DB::table('vouchers')->where('financial_year_id', $fy)->whereNotNull('advice_no')->distinct()->count('advice_no');
check("advice list opens, shows {$nAdv} advices", $c === 200 && str_contains($h, number_format($nAdv).' advices'));
foreach (['/division/advices/pending', '/division/process-bill', '/division/gst-challans', '/division/treasury-details', '/division/delete-advice', '/division/change-bill-order-no', '/division/bill-advice-reports'] as $u) {
    [$c, $h, $e] = call('GET', $u, 'division');
    check("division $u opens (menu link)", in_array($c, [200, 302]), "HTTP $c $e");
}
[$c] = call('GET', '/division/advices', 'himatnagar');
check('a range cannot open the advice screens (403)', $c === 403);

DB::beginTransaction();
try {
    $pendingQ = fn () => Voucher::where('financial_year_id', $fy)->whereNull('advice_no')->whereNull('rejected_reason')->where('received_at_division', 'Received');
    $pendingBefore = $pendingQ()->count();
    [$c, $h] = call('GET', '/division/advices/pending', 'division');
    check("pending list shows the waiting vouchers (database: $pendingBefore)", $c === 200 && $pendingBefore > 0 && substr_count($h, 'name="voucher_ids[]"') === min(100, $pendingBefore));

    // vouchers must be received at the division before they can be advised
    $notRec = Voucher::where('financial_year_id', $fy)->whereNull('advice_no')->whereNull('rejected_reason')->where('received_at_division', '!=', 'Received')->where('payment_mode', 'IFMS')->orderBy('id')->limit(4)->pluck('id')->all();
    [$c] = call('POST', '/division/advices/receive', 'division', ['_token' => 'tok', 'voucher_ids' => $notRec]);
    check('"Not received" vouchers can be marked received ('.count($notRec).')', $c === 302 && Voucher::whereIn('id', $notRec)->where('received_at_division', 'Received')->count() === count($notRec));
    [$c, $h] = call('GET', '/division/advices/pending?received=0', 'division');
    check('the "not yet received" list opens', $c === 200 && str_contains($h, 'Mark selected as received'));
    $pendingBefore = $pendingQ()->count();
    $ifms = $pendingQ()->where('payment_mode', 'IFMS')->orderBy('id')->limit(3)->get();
    $sna = $pendingQ()->where('payment_mode', 'SNA')->first();
    $maxIfms = (int) Voucher::where('financial_year_id', $fy)->where('advice_no', '<', 5001)->max('advice_no');
    [$c, $h, $loc] = call('POST', '/division/advices', 'division', ['_token' => 'tok', 'voucher_ids' => $ifms->pluck('id')->all(), 'bill_type' => 'Simple Receipt', 'advice_date' => '2026-11-24', 'bill_order_outward_no' => 'T/1/2026']);
    $v1 = Voucher::find($ifms[0]->id);
    check('advice created: next IFMS number, register "n/2026-27", all 3 vouchers carry it', $c === 302 && $v1->advice_no === $maxIfms + 1 && $v1->bill_register_no === ($maxIfms + 1).'/2026-27' && Voucher::where('advice_no', $maxIfms + 1)->count() === 3 && $v1->ifms_bill_type === 'Simple Receipt', "advice ".($v1->advice_no));
    $no = $maxIfms + 1;
    check('each voucher got an audit version of the advice step', DB::table('voucher_versions')->where('version_kind', 'advice:advice_created')->count() === 3);
    check('pending list shrank by 3', $pendingQ()->count() === $pendingBefore - 3);
    $rangeUser = DB::table('users')->where('id', $v1->range_id)->value('username');
    $plural = ['Tender Entry' => 'tender-entry', 'Free Entry' => 'free-entry', 'D.Wagers Salary' => 'd-wager-salary-entry', 'D.Wagers Arrears' => 'd-wager-arrears-entry', 'Bene. Entry' => 'sf-beneficiary-entry', 'WL Bene. Entry' => 'wl-beneficiary-entry'][$v1->entry_type];
    [$c] = call('GET', "/finance/$plural/{$v1->id}/edit", $rangeUser);
    check('the range can no longer edit a voucher that is in an advice (403)', $c === 403);

    if ($sna) {
        [$c] = call('POST', '/division/advices', 'division', ['_token' => 'tok', 'voucher_ids' => [$sna->id], 'bill_type' => 'Contingency', 'advice_date' => '2026-11-24'], true);
        check('an SNA voucher cannot go into an IFMS advice (422)', $c === 422, "HTTP $c");
        [$c, $h, $loc] = call('POST', '/division/advices', 'division', ['_token' => 'tok', 'voucher_ids' => [$sna->id], 'bill_type' => 'SNA', 'advice_date' => '2026-11-24']);
        $sv = Voucher::find($sna->id);
        check('SNA advice uses the separate 5001+ series', $c === 302 && $sv->advice_no >= 5024, 'advice '.$sv->advice_no);
    }

    [$c] = call('PUT', "/division/advices/$no/treasury", 'division', ['_token' => 'tok', 'treasury_voucher_no' => '999', 'treasury_approval_date' => '2026-11-26', 'treasury_status' => 'Approved', 'epayment_code' => 'EP1']);
    $t = Voucher::where('advice_no', $no)->get();
    check('treasury details saved on all vouchers, month derived (Nov)', $c === 302 && $t->every(fn ($v) => $v->treasury_voucher_no === '999' && $v->treasury_status === 'Approved' && $v->treasury_approval_month === 'Nov' && $v->epayment_code === 'EP1'));
    [$c] = call('PUT', "/division/advices/$no/gst", 'division', ['_token' => 'tok', 'gst_challan_no' => 'GC-77']);
    check('GST challan saved on all vouchers', $c === 302 && Voucher::where('advice_no', $no)->where('gst_challan_no', 'GC-77')->count() === 3);
    [$c] = call('PUT', "/division/advices/$no/numbers", 'division', ['_token' => 'tok', 'advice_date' => '2026-11-25', 'bill_register_no' => "$no/2026-27/A", 'bill_order_outward_no' => 'O/9']);
    check('date / bill register / order no. changed', $c === 302 && Voucher::where('advice_no', $no)->where('bill_register_no', "$no/2026-27/A")->count() === 3);
    // numbers restart every month: the series continues after the highest SR- number of the advice's own month
    $first = Voucher::where('advice_no', $no)->where('financial_year_id', $fy)->first();
    $maxSr = \App\Support\FinalVoucherNumbering::highest('SR', \App\Support\FinalVoucherNumbering::seriesDate($first), $fy);
    [$c] = call('POST', "/division/advices/$no/final-numbers", 'division', ['_token' => 'tok']);
    $finals = Voucher::where('advice_no', $no)->orderBy('range_id')->orderBy('entry_type')->orderBy('serial_number')->pluck('voucher_no_final')->all();
    check('final voucher numbers continue the SR- series of the month', $c === 302 && $finals === ['SR-'.($maxSr + 1), 'SR-'.($maxSr + 2), 'SR-'.($maxSr + 3)], implode(',', $finals));
    [$c] = call('POST', "/division/advices/$no/final-numbers", 'division', ['_token' => 'tok'], true);
    check('assigning again is refused (nothing left)', $c === 422);

    [$c] = call('DELETE', "/division/advices/$no", 'division', ['_token' => 'tok'], true);
    check('an advice approved by the treasury cannot be deleted (422)', $c === 422 && Voucher::where('advice_no', $no)->count() === 3);
    call('PUT', "/division/advices/$no/treasury", 'division', ['_token' => 'tok', 'treasury_voucher_no' => '999', 'treasury_approval_date' => '2026-11-26', 'treasury_status' => 'Pending']);
    [$c] = call('DELETE', "/division/advices/$no", 'division', ['_token' => 'tok']);
    $back = Voucher::find($ifms[0]->id);
    check('once not approved, delete clears the advice and the vouchers are pending again', $c === 302 && $back->advice_no === null && $back->bill_register_no === null && $back->treasury_voucher_no === null && $back->voucher_no_final === null && $back->payment_mode === 'IFMS' && $pendingQ()->count() >= $pendingBefore - 1);
} finally {
    DB::rollBack();
}
check('rollback restored everything (146 advices, 3,696 versions)', DB::table('vouchers')->where('financial_year_id', $fy)->whereNotNull('advice_no')->distinct()->count('advice_no') === $nAdv && DB::table('voucher_versions')->where('financial_year_id', $fy)->count() === 3696);

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
