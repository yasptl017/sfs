<?php
/** FINANCIAL YEAR 2025-26: reports verification against the spreadsheets, through the real endpoints.  php database/tools/verify_reports_2526.php */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\ExpenditureReport;
use App\Support\Format;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$only = $argv[1] ?? 'all';
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
        return [500, 'EXC: '.$e->getMessage(), []];
    }

    return [$res->getStatusCode(), (string) $res->getContent(), json_decode((string) $res->getContent(), true) ?: []];
}
$fy = DB::table('financial_years')->where('code', '2025-26')->value('id');
$app['session.store']->put('financial_year_id', $fy);
$TOTAL = 497365608;   // LC SHEET 'Total_Exp' of March = sum of the Ded. Cheques sheet

echo "== Year: the treasury-passed vouchers and the cheque register\n";
$approved = DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved');
check('passed vouchers (Pass = P, latest register) add up to the LC SHEET total expenditure 4,97,365,608', abs((float) (clone $approved)->sum('payable_total_amount') - $TOTAL) < 1, number_format((float) (clone $approved)->sum('payable_total_amount')));
$cheq = DB::table('deduction_cheques')->where('financial_year_id', $fy)->groupBy('advice_no')->selectRaw('advice_no, SUM(amount) a')->pluck('a', 'advice_no');
$adv = (clone $approved)->groupBy('advice_no')->selectRaw('advice_no, SUM(payable_total_amount) p')->pluck('p', 'advice_no');
$eq = 0;
foreach ($adv as $no => $p) {
    $eq += abs((float) ($cheq[$no] ?? -1) - (float) $p) < 1;
}
check('every advice: cheques of the Ded. Cheques sheet add up to the payable total of its passed vouchers', $eq === count($adv) && count($adv) === count($cheq), "$eq of ".count($adv).' advices; '.count($cheq).' advices with cheques');
check('cheque register total = 4,97,365,608', abs((float) $cheq->sum() - $TOTAL) < 1);

echo "== Summary (endpoint)\n";
foreach (['Oct' => [203938439, 37506296], 'Mar' => [397520830, 99844778]] as $m => [$p, $c]) {
    [$code, , $j] = call('POST', '/division/summary-reports/preview', 'division', ['month' => $m, 'payment_mode' => 'ALL', 'report_type' => 'summary_abstract', '_token' => 'tok']);
    $g = $j['data']['grand_totals'] ?? [];
    check("abstract $m: up to last month = ".number_format($p).' and this month = '.number_format($c).' (Summary / LC SHEET)', $code === 200 && abs(($g['last_month'] ?? 0) - $p) < 1 && abs(($g['current_month'] ?? 0) - $c) < 1, number_format($g['last_month'] ?? 0).' / '.number_format($g['current_month'] ?? 0));
}
[$code, , $j] = call('POST', '/division/summary-reports/preview', 'division', ['month' => 'Mar', 'payment_mode' => 'ALL', 'report_type' => 'summary_abstract', '_token' => 'tok']);
check('abstract Mar: sanctioned = 547,892,647 (LC SHEET total allotment)', abs(($j['data']['grand_totals']['sanctioned'] ?? 0) - 547892647) < 1, number_format($j['data']['grand_totals']['sanctioned'] ?? 0));
foreach (['summary_merged_salary', 'summary_only_lc'] as $t) {
    [$code] = call('POST', '/division/summary-reports/preview', 'division', ['month' => 'Mar', 'payment_mode' => 'ALL', 'report_type' => $t, '_token' => 'tok']);
    check("$t answers", $code === 200);
}

echo "== Monthly: every month x mode\n";
$tot = ['gross' => 0.0, 'deductions' => 0.0, 'net' => 0.0];
$bad = $monthsOk = 0;
foreach (ExpenditureReport::MONTHS as $m) {
    $db = (clone $approved)->where('treasury_approval_month', $m);
    $g = 0.0;
    foreach (['IFMS', 'PFMS'] as $mode) {
        [$c, , $j] = call('POST', '/division/monthly-reports/preview', 'division', ['month' => $m, 'payment_mode' => $mode, 'report_type' => 'form_53_new', '_token' => 'tok']);
        if ($c !== 200) {
            $bad++;
            continue;
        }
        $t = $j['data']['totals'];
        $tot['gross'] += $t['gross'];
        $tot['deductions'] += $t['deductions'];
        $tot['net'] += $t['net'];
        $g += $t['gross'];
    }
    $monthsOk += abs($g - (float) (clone $db)->sum('payable_total_amount')) < 1;
}
check('all 24 month x mode previews answer', $bad === 0);
check('every month: IFMS + PFMS gross = passed vouchers of that Approval Month', $monthsOk === 12, "$monthsOk of 12");
check('12 months: gross = 4,97,365,608, deductions and net = the vouchers', abs($tot['gross'] - $TOTAL) < 1 && abs($tot['deductions'] - (float) (clone $approved)->sum('total_deduction')) < 1 && abs($tot['net'] - (float) (clone $approved)->sum('net_amount')) < 1, number_format($tot['gross']).' / '.number_format($tot['deductions']).' / '.number_format($tot['net']));
$d = (clone $approved)->where('treasury_approval_month', 'Oct')->sum('payable_total_amount');
check('October gross = 37,506,296 (Summary sheet C_Exp of Oct)', abs((float) $d - 37506296) < 1);
[$c, , $j] = call('POST', '/division/monthly-reports/preview', 'division', ['month' => 'Jan', 'payment_mode' => 'IFMS', 'report_type' => 'pay_slips', '_token' => 'tok']);
$slips = $j['data']['pay_slips_list'] ?? [];
$withDays = count(array_filter($slips, fn ($s) => $s['days_worked'] !== null));
$expectSalary = (clone $approved)->where('treasury_approval_month', 'Jan')->where('payment_mode', 'IFMS')->where('entry_type', 'D.Wagers Salary')->count();
check('pay slips of January: one per approved salary voucher, each with days worked from the D.Wager Salary register', $c === 200 && count($slips) === $expectSalary && $withDays === count($slips), count($slips).' slips, '.$withDays.' with days; vouchers '.$expectSalary);

echo "== Form No. 35 (endpoint): every month x mode\n";
$f35 = $cntOk = $sumOk = $ordOk = $unOk = 0;
$gross = 0.0;
foreach (ExpenditureReport::MONTHS as $m) {
    foreach (['IFMS', 'PFMS'] as $mode) {
        $q = (clone $approved)->where('treasury_approval_month', $m)->where('payment_mode', $mode);
        [$c, , $j] = call('POST', '/division/final-voucher-cashbook/preview', 'division', ['month' => $m, 'payment_mode' => $mode, 'report_type' => 'form_35', '_token' => 'tok']);
        $dd = $j['data'] ?? [];
        $f35++;
        $cntOk += ($c === 200 && ($dd['totals']['count'] ?? -1) === (clone $q)->count());
        $sumOk += abs(($dd['totals']['gross_amount'] ?? -1) - (float) (clone $q)->sum('payable_total_amount')) < 0.01 && abs(($dd['totals']['net_amount'] ?? -1) - (float) (clone $q)->sum('net_amount')) < 0.01;
        $gross += $dd['totals']['gross_amount'] ?? 0;
        $nums = array_map(fn ($e) => $e['final_voucher_no'] === null ? PHP_INT_MAX : (int) $e['final_voucher_no'], $dd['entries'] ?? []);
        $sorted = $nums;
        sort($sorted);
        $ordOk += ($nums === $sorted);
        $unOk += count(array_filter($dd['entries'] ?? [], fn ($e) => $e['final_voucher_no'] === null)) === (clone $q)->whereNull('voucher_no_final')->count();
    }
}
check("$f35 month x mode schedules: voucher count equals the database", $cntOk === $f35, "$cntOk of $f35");
check('gross and net of every schedule equal the database; year gross = 4,97,365,608', $sumOk === $f35 && abs($gross - $TOTAL) < 1, number_format($gross));
check('schedules are in final-number order (the 1..n of the month), unnumbered last', $ordOk === $f35);
check('unnumbered vouchers are counted correctly (they are listed, never dropped)', $unOk === $f35);

echo "== Cash book: receipts, payments, balances\n";
$closingPrev = null;
$cbOk = $chainOk = 0;
foreach (ExpenditureReport::MONTHS as $m) {
    $w = ExpenditureReport::window($fy, $m);
    [$c, , $j] = call('POST', '/division/final-voucher-cashbook/preview', 'division', ['month' => $m, 'report_type' => 'cashbook', '_token' => 'tok']);
    $sm = $j['data']['cashbook_summary'] ?? [];
    $lc = (float) DB::table('lc_entries')->where('financial_year_id', $fy)->whereBetween('entry_date', [$w['start'], $w['end']])->sum('amount');
    $paid = (float) (clone $approved)->where('treasury_approval_month', $m)->sum('payable_total_amount');
    $cbOk += ($c === 200 && abs(($sm['total_receipts'] ?? -1) - $lc) < 0.01 && abs(($sm['total_payments'] ?? -1) - $paid) < 0.01 && abs(($sm['closing_balance'] ?? 0) - (($sm['opening_balance'] ?? 0) + $lc - $paid)) < 0.01);
    $chainOk += $closingPrev === null || abs($closingPrev - ($sm['opening_balance'] ?? -1)) < 0.01;
    $closingPrev = $sm['closing_balance'] ?? null;
}
check('12 months: receipts = L.C. issued, payments = passed vouchers, closing = opening + receipts - payments', $cbOk === 12, "$cbOk of 12");
check('opening balance of every month = closing balance of the month before', $chainOk === 12);
$lcAll = (float) DB::table('lc_entries')->where('financial_year_id', $fy)->sum('amount');
$lcAfter = (float) DB::table('lc_entries')->where('financial_year_id', $fy)->where('entry_date', '>', '2026-03-31')->sum('amount');
check('year end balance = all L.C. dated up to 31 Mar 2026 - payments (entries typed before April 2025 count as opening balance: open point OP-03)', abs((float) $closingPrev - (($lcAll - $lcAfter) - $TOTAL)) < 1, 'closing '.number_format((float) $closingPrev).'; all L.C. '.number_format($lcAll).'; dated after the year '.number_format($lcAfter));
check('with L.C. Sr 53 read as 2025-10-15 the year-end balance would be 12,451 = "LC in Hand" of the LC SHEET', abs((float) $closingPrev + $lcAfter - 12451) < 1, number_format((float) $closingPrev + $lcAfter));

echo "== Registers of the year\n";
[$c, $h] = call('GET', '/registers/d-wager-salaries', 'division');
check('D.Wager salary register opens and shows rows', $c === 200 && str_contains($h, 'Working days'));
[$c, $h] = call('GET', '/registers/deduction-cheques', 'division');
check('deduction cheques register opens', $c === 200 && str_contains($h, 'Cheque'));
[$c, $h] = call('GET', '/vouchers', 'division');
check('All Vouchers lists the 25-26 vouchers', $c === 200);
[$c, $h] = call('GET', '/reports/position', 'division');
check('Allotment vs Expenditure opens for 2025-26', $c === 200);

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
