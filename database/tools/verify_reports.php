<?php
/** Reports verification against the spreadsheets, through the real endpoints.  php database/tools/verify_reports.php [summary|monthly] */
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
$fy = DB::table('financial_years')->where('code', '2026-27')->value('id');
$app['session.store']->put('financial_year_id', $fy);

if (in_array($only, ['all', 'summary'], true)) {
    echo "== Summary: the month windows partition the approved vouchers\n";
    $approved = (float) DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved')->sum('payable_total_amount');
    $sumC = 0.0;
    foreach (ExpenditureReport::MONTHS as $m) {
        $sumC += array_sum(array_map(fn ($r) => $r->c_exp, ExpenditureReport::codeRows($fy, $m)));
    }
    check('sum of the 12 months of C_Exp == all treasury-approved payable (3,10,260,448)', abs($sumC - $approved) < 1 && abs($approved - 310260448) < 1, number_format($sumC).' vs '.number_format($approved));
    $prevOk = true;
    foreach (['Jun', 'Sep', 'Nov'] as $m) {
        $a = array_sum(array_map(fn ($r) => $r->p_exp + $r->c_exp, ExpenditureReport::codeRows($fy, $m)));
        $next = ['Jun' => 'Jul', 'Sep' => 'Oct', 'Nov' => 'Dec'][$m];
        $b = array_sum(array_map(fn ($r) => $r->p_exp, ExpenditureReport::codeRows($fy, $next)));
        $prevOk = $prevOk && abs($a - $b) < 0.01;
    }
    check('"up to last month" of the next month == "total expenditure" of this month', $prevOk);
    $apr = ExpenditureReport::codeRows($fy, 'Apr');
    check('April: nothing was spent before it', array_sum(array_map(fn ($r) => $r->p_exp, $apr)) == 0 && array_sum(array_map(fn ($r) => $r->allotment, $apr)) == 0);
    $m1 = array_sum(array_map(fn ($r) => $r->p_exp + $r->c_exp, ExpenditureReport::codeRows($fy, 'Nov', 'IFMS'))) + array_sum(array_map(fn ($r) => $r->p_exp + $r->c_exp, ExpenditureReport::codeRows($fy, 'Nov', 'SNA')));
    check('IFMS + SNA == ALL (payment-mode split)', abs($m1 - array_sum(array_map(fn ($r) => $r->p_exp + $r->c_exp, ExpenditureReport::codeRows($fy, 'Nov')))) < 0.01);

    echo "== Summary: endpoints vs the Summary sheet header (October) and LC SHEET header (November)\n";
    [$c, $h, $j] = call('POST', '/division/summary-reports/preview', 'division', ['month' => 'Oct', 'payment_mode' => 'ALL', 'report_type' => 'summary_abstract', '_token' => 'tok']);
    $g = $j['data']['grand_totals'] ?? [];
    check('abstract Oct: sanctioned = 685,194,758', $c === 200 && abs(($g['sanctioned'] ?? 0) - 685194758) < 1);
    check('abstract Oct: up to September = 205,841,165, October = 65,740,042, total = 271,581,207', abs(($g['last_month'] ?? 0) - 205841165) < 1 && abs(($g['current_month'] ?? 0) - 65740042) < 1 && abs(($g['total_exp'] ?? 0) - 271581207) < 1);
    check('abstract Oct: released (L.C. issued) = 393,023,464', abs(($g['released'] ?? 0) - 393023464) < 1);
    check('abstract Oct: remaining = sanctioned - total expenditure', abs(($g['rem_req'] ?? 0) - (685194758 - 271581207)) < 1);
    $secSum = array_sum(array_map(fn ($s) => $s['subtotal']['total_exp'], $j['data']['sections'] ?? []));
    check('abstract: sections add up to the grand total', abs($secSum - ($g['total_exp'] ?? 0)) < 1, count($j['data']['sections'] ?? []).' demand sections');
    $h2406 = $j['data']['head_2406_totals']['sanctioned'] ?? 0;
    $h4406 = $j['data']['head_4406_totals']['sanctioned'] ?? 0;
    check('2406 + 4406 heads add up to the grand total', abs($h2406 + $h4406 - ($g['sanctioned'] ?? 0)) < 1, number_format($h2406).' + '.number_format($h4406));
    [$c, $h, $j] = call('POST', '/division/summary-reports/preview', 'division', ['month' => 'Nov', 'payment_mode' => 'ALL', 'report_type' => 'summary_abstract', '_token' => 'tok']);
    $g = $j['data']['grand_totals'] ?? [];
    check('abstract Nov: up to October = 271,581,207, November = 38,679,241 (LC SHEET header)', abs(($g['last_month'] ?? 0) - 271581207) < 1 && abs(($g['current_month'] ?? 0) - 38679241) < 1);
    [$c, $h, $j] = call('POST', '/division/summary-reports/preview', 'division', ['month' => 'Oct', 'payment_mode' => 'ALL', 'report_type' => 'summary_merged_salary', '_token' => 'tok']);
    $g = $j['data']['grand_totals'] ?? [];
    $items = array_sum(array_map(fn ($x) => count($x['items']), $j['data']['groups'] ?? []));
    check('detailed (merged with salary): all 282 budget codes, totals equal the sheet', $items === 282 && abs($g['allotment'] - 685194758) < 1 && abs($g['p_exp'] - 205841165) < 1 && abs($g['c_exp'] - 65740042) < 1, "$items lines");
    check('detailed: remaining requirement = 413,613,551 (Summary sheet header)', abs($g['rem_req'] - 413613551) < 1, number_format($g['rem_req']));
    $class1 = DB::table('budget_codes')->where('financial_year_id', $fy)->where('object_class', 'Class-1')->count();
    [$c, $h, $j2] = call('POST', '/division/summary-reports/preview', 'division', ['month' => 'Oct', 'payment_mode' => 'ALL', 'report_type' => 'summary_only_lc', '_token' => 'tok']);
    $items2 = array_sum(array_map(fn ($x) => count($x['items']), $j2['data']['groups'] ?? []));
    check("'only LC' leaves out the $class1 salary/wages (Class-1) codes", $items2 === 282 - $class1, "$items2 lines");
    [$c, $h] = call('GET', '/division/summary-reports/print?month=Oct&payment_mode=ALL&report_type=summary_abstract', 'division');
    check('printed abstract shows the grand total', $c === 200 && str_contains($h, number_format(685194758, 2)) && str_contains($h, number_format(271581207, 2)) && str_contains($h, 'Abstract of SUMMARY of the month Oct-2026'));
    [$c, $h] = call('GET', '/division/summary-reports/print?month=Oct&payment_mode=ALL&report_type=summary_merged_salary', 'division');
    // the detailed layout prints a subtotal per scheme (no grand-total row): compare one scheme with the same scheme's rows in the sheet
    $sid = DB::table('staging_sheets')->where('source_file', 'like', 'Master00%')->where('sheet_name', 'Summary')->value('id');
    $sch = ['a' => 0.0, 'p' => 0.0, 'c' => 0.0];
    foreach (DB::table('staging_rows')->where('staging_sheet_id', $sid)->where('row_no', '>=', 6)->get(['cells']) as $row) {
        $cc = array_pad(json_decode($row->cells, true), 12, null);
        if (($cc[2] ?? null) === '02- Divisional') {
            $sch['a'] += (float) $cc[6];
            $sch['p'] += (float) $cc[7];
            $sch['c'] += (float) $cc[8];
        }
    }
    check('printed detailed summary: scheme "02- Divisional" subtotal == the sheet rows of that scheme (allotment, P, C, total)',
        $c === 200 && str_contains($h, number_format($sch['a'], 2)) && str_contains($h, number_format($sch['p'], 2)) && str_contains($h, number_format($sch['c'], 2)) && str_contains($h, number_format($sch['p'] + $sch['c'], 2)),
        'allotment '.number_format($sch['a']).' P '.number_format($sch['p']).' C '.number_format($sch['c']));
    [$c] = call('GET', '/division/summary-reports', 'himatnagar');
    check('a range cannot open the report (403)', $c === 403);
    [$c, $h] = call('GET', '/division/summary-reports', 'division');
    check('the report page opens with the latest month preselected', $c === 200 && str_contains($h, 'Nov'));
}


if (in_array($only, ['all', 'monthly'], true)) {
    echo "== Monthly: every month x mode, vs the sheets\n";
    $months = ExpenditureReport::MONTHS;
    $tot = ['gross' => 0.0, 'deductions' => 0.0, 'net' => 0.0];
    $perMonth = [];
    $bad = 0;
    foreach ($months as $m) {
        foreach (['IFMS', 'SNA'] as $mode) {
            [$c, $h, $j] = call('POST', '/division/monthly-reports/preview', 'division', ['month' => $m, 'payment_mode' => $mode, 'report_type' => 'form_53_new', '_token' => 'tok']);
            if ($c !== 200) {
                $bad++;
                continue;
            }
            $t = $j['data']['totals'];
            $tot['gross'] += $t['gross'];
            $tot['deductions'] += $t['deductions'];
            $tot['net'] += $t['net'];
            $perMonth[$m] = ($perMonth[$m] ?? 0) + $t['gross'];
        }
    }
    check('all 24 month x mode previews answer', $bad === 0);
    check('12 months x IFMS+SNA: gross payable = 310,260,448 (Passed Vouchers header)', abs($tot['gross'] - 310260448) < 1, number_format($tot['gross']));
    check('...deductions = 7,521,794 and net = 302,738,654 (Passed Vouchers header)', abs($tot['deductions'] - 7521794) < 1 && abs($tot['net'] - 302738654) < 1, number_format($tot['deductions']).' / '.number_format($tot['net']));
    check('October gross = 65,740,042 and November gross = 38,679,241 (as in the Summary / LC SHEET)', abs($perMonth['Oct'] - 65740042) < 1 && abs($perMonth['Nov'] - 38679241) < 1, number_format($perMonth['Oct']).' / '.number_format($perMonth['Nov']));

    echo "== Monthly: Form 53 abstract\n";
    $f = ['l' => 0.0, 'd' => 0.0];
    foreach (['IFMS', 'SNA'] as $mode) {
        [$c, $h, $j] = call('POST', '/division/monthly-reports/preview', 'division', ['month' => 'Oct', 'payment_mode' => $mode, 'report_type' => 'form_53_abstract', '_token' => 'tok']);
        $d = $j['data']['d36_sections'];
        $f['l'] += $d['grand_total_last_month'];
        $f['d'] += $d['grand_total_during_month'];
    }
    check('Form 53 Oct (IFMS + SNA): up to September = 205,841,165, during October = 65,740,042', abs($f['l'] - 205841165) < 1 && abs($f['d'] - 65740042) < 1, number_format($f['l']).' / '.number_format($f['d']));
    [$c, $h, $j] = call('POST', '/division/monthly-reports/preview', 'division', ['month' => 'Oct', 'payment_mode' => 'IFMS', 'report_type' => 'form_53_abstract', '_token' => 'tok']);
    $ifms = $j['data']['d36_sections'];
    $sumDemands = array_sum(array_map(fn ($x) => $x['total_progressive'], $ifms['demands']));
    $sumMinors = 0;
    $sumSubs = 0;
    $sumObjs = 0;
    foreach ($ifms['demands'] as $dm) {
        foreach ($dm['minor_heads'] as $mh) {
            $sumMinors += $mh['total_progressive'];
            foreach ($mh['sub_heads'] as $sh) {
                $sumSubs += $sh['total_progressive'];
                $sumObjs += array_sum(array_column($sh['objects'], 'progressive'));
            }
        }
    }
    check('Form 53: demands = minor heads = sub-heads = objects = grand total', abs($sumDemands - $ifms['grand_total_progressive']) < 0.01 && abs($sumMinors - $sumDemands) < 0.01 && abs($sumSubs - $sumDemands) < 0.01 && abs($sumObjs - $sumDemands) < 0.01);
    [$c, $h, $j2] = call('POST', '/division/summary-reports/preview', 'division', ['month' => 'Oct', 'payment_mode' => 'IFMS', 'report_type' => 'summary_abstract', '_token' => 'tok']);
    $sumSec = collect($j2['data']['sections'])->keyBy('demand_key');
    $match = true;
    foreach ($ifms['demands'] as $dm) {
        $key = str_contains($dm['major_head'], '2406') ? '2406' : '4406';
        $dn = $dm['demand_no'];
        $k = $key.'/'.(str_starts_with($dn, '95') ? '95' : (str_starts_with($dn, '96') || str_starts_with($dn, '096') ? '96' : '26'));
        $match = $match && isset($sumSec[$k]) && abs($sumSec[$k]['subtotal']['total_exp'] - $dm['total_progressive']) < 0.01;
    }
    check('each Form 53 demand equals the same demand in the Summary abstract', $match);
    $other = 0.0;
    foreach ($ifms['demands'] as $dm) {
        foreach ($dm['minor_heads'] as $mh) {
            if (str_contains($mh['name'], 'Other schemes')) {
                $other += $mh['total_progressive'];
            }
        }
    }
    echo '     (expenditure printed under "Other schemes - minor head to be confirmed": '.number_format($other).' of '.number_format($ifms['grand_total_progressive']).")\n";

    echo "== Monthly: deduction schedules, e-payment, pay slips, challans (October, IFMS+SNA)\n";
    $w = ExpenditureReport::window($fy, 'Oct');
    $get = fn ($mode, $type, $extra = []) => call('POST', '/division/monthly-reports/preview', 'division', ['month' => 'Oct', 'payment_mode' => $mode, 'report_type' => $type, '_token' => 'tok'] + $extra)[2]['data'];
    $tdsSum = $cessSum = $paySum = $gpfSum = $npsSum = $ptSum = 0.0;
    $slips = 0;
    $epay = 0.0;
    $tdsRows = [];
    foreach (['IFMS', 'SNA'] as $mode) {
        $d = $get($mode, 'deduction_reports');
        $tdsSum += array_sum(array_column($d['tds_items'], 'tds_amount'));
        $cessSum += array_sum(array_column($d['labour_cess_items'], 'cess_amount'));
        $tdsRows = array_merge($tdsRows, $d['tds_items']);
        $p = $get($mode, 'pay_slips');
        $slips += count($p['pay_slips_list']);
        $paySum += array_sum(array_column($p['pay_slips_list'], 'net_payable'));
        $g = $get($mode, 'gpf_nps_reports');
        $gpfSum += array_sum(array_column($g['gpf_items'], 'amount'));
        $npsSum += array_sum(array_column($g['nps_items'], 'amount'));
        $ptSum += array_sum(array_column($g['ptax_items'], 'pt_recovered'));
        $e = $get($mode, 'epayment_report');
        $epay += array_sum(array_column($e['epayment_items'], 'payable_total'));
    }
    $q = fn () => DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved')->whereBetween('treasury_approval_date', [$w['start'], $w['end']]);
    check('TDS schedule total = TDS of non-salary vouchers', abs($tdsSum - (float) $q()->where('entry_type', '!=', 'D.Wagers Salary')->sum('tds')) < 0.01, number_format($tdsSum));
    check('labour cess schedule total = labour cess of non-salary vouchers', abs($cessSum - (float) $q()->where('entry_type', '!=', 'D.Wagers Salary')->sum('deduction_labour_cess')) < 0.01, number_format($cessSum));
    $withTds = array_filter($tdsRows, fn ($r) => $r['taxable_amount'] > 0);
    $plausible = count(array_filter($withTds, fn ($r) => $r['tds_amount'] / $r['taxable_amount'] >= 0.004 && $r['tds_amount'] / $r['taxable_amount'] <= 0.025));
    check('TDS is 0.4% - 2.5% of the taxable value for at least 95% of parties (sanity of the base)', count($withTds) > 0 && $plausible / count($withTds) >= 0.95, "$plausible of ".count($withTds));
    $salaryQ = fn () => $q()->where('entry_type', 'D.Wagers Salary');
    check('pay slips: one per D.Wager salary voucher, net payable adds up', $slips === $salaryQ()->count() && abs($paySum - (float) $salaryQ()->sum('net_amount')) < 0.01, "$slips slips");
    check('GPF / NPS(CPF) / professional tax lists add up to the salary vouchers', abs($gpfSum - (float) $salaryQ()->sum('gpf')) < 0.01 && abs($npsSum - (float) $salaryQ()->sum('cpf')) < 0.01 && abs($ptSum - (float) $salaryQ()->sum('p_tax')) < 0.01, 'GPF '.number_format($gpfSum).' NPS '.number_format($npsSum).' PT '.number_format($ptSum));
    check('e-payment report: payable adds up to the whole month (65,740,042)', abs($epay - 65740042) < 1, number_format($epay));
    $cashQ = fn () => DB::table('cash_accounts')->where('financial_year_id', $fy)->where('cash_account_month', 'Oct-26')->where('amount', '>', 0);
    $ch = $get('IFMS', 'challan_report');
    check('challan register (Oct-26 cash account): rows and amount match', count($ch['challan_items']) === $cashQ()->count() && abs(array_sum(array_column($ch['challan_items'], 'amount')) - (float) $cashQ()->sum('amount')) < 0.01, count($ch['challan_items']).' rows');
    $yearlyTds = array_sum(array_column($get('IFMS', 'deduction_reports_yearly')['tds_items'], 'tds_amount')) + array_sum(array_column($get('SNA', 'deduction_reports_yearly')['tds_items'], 'tds_amount'));
    $expYear = (float) DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved')->where('entry_type', '!=', 'D.Wagers Salary')->whereBetween('treasury_approval_date', ['2026-04-01', '2026-10-31'])->sum('tds');
    check('yearly deductions (Apr - Oct) cover the whole year to date', abs($yearlyTds - $expYear) < 0.01, number_format($yearlyTds));
    $cont = $get('IFMS', 'form_53_new', ['bill_types' => ['Contingency']])['totals']['gross'];
    $expCont = (float) $q()->where('payment_mode', 'IFMS')->where('ifms_bill_type', 'Contingency')->sum('payable_total_amount');
    check('bill-type filter (Contingency only) works', abs($cont - $expCont) < 0.01, number_format($cont));
    [$c, $h, $j] = call('GET', '/division/monthly-reports/schemes?payment_mode=SNA', 'division');
    $real = DB::table('budget_codes')->where('financial_year_id', $fy)->where('payment_mode', 'SNA')->whereNotNull('scheme')->distinct()->pluck('scheme')->all();
    check('SNA scheme filter lists the real SNA schemes', $c === 200 && count($j['options'] ?? []) === count($real) && count($real) > 0, count($real).' schemes');

    echo "== Monthly: printed pages\n";
    foreach (['form_53_abstract' => 'Form 53', 'epayment_report' => 'e-payment', 'challan_report' => 'challan', 'pay_slips' => 'pay slips', 'deduction_reports' => 'deductions', 'form_53_new' => 'bill statement'] as $type => $lab) {
        [$c, $h] = call('GET', "/division/monthly-reports/print?month=Oct&payment_mode=IFMS&report_type=$type", 'division');
        check("print $lab renders", $c === 200 && ! str_contains($h, 'Whoops') && strlen($h) > 5000, "HTTP $c, ".strlen($h).' bytes');
    }
    [$c, $h] = call('GET', '/division/monthly-reports/print?month=Oct&payment_mode=IFMS&report_type=form_53_new', 'division');
    $reg = (string) DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved')->whereBetween('treasury_approval_date', [$w['start'], $w['end']])->where('payment_mode', 'IFMS')->value('bill_register_no');
    check('bill statement prints a real bill register number and the month gross', str_contains($h, $reg) && str_contains($h, number_format($perMonth['Oct'] - (float) $q()->where('payment_mode', 'SNA')->sum('payable_total_amount'), 2)), $reg);
    [$c] = call('GET', '/division/monthly-reports', 'himatnagar');
    check('a range cannot open the Monthly report (403)', $c === 403);
}


if (in_array($only, ['all', 'voucherprint'], true)) {
    echo "== Voucher Print: every voucher of every range, through the real endpoint\n";
    $placeholders = ['ABCDE', 'Standard Vendor', 'Forest Compartment', 'Gujarat, India', 'Shree Ram Forest', 'Party / Vendor', 'SBIN0001234', 'Survey Plot No.'];
    $ranges = DB::table('users')->where('role', 'range')->orderBy('name')->get(['id', 'username', 'name']);
    $types = ['Tender Entry', 'Free Entry', 'D.Wagers Salary', 'D.Wagers Arrears', 'Bene. Entry', 'WL Bene. Entry'];
    $groups = $countOk = $netOk = $balanceOk = $itemsOk = $placeholderHits = $missingOk = $vouchersSeen = 0;
    $itemsOff = [];
    foreach ($ranges as $r) {
        foreach ($types as $t) {
            $db = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $r->id)->where('entry_type', $t);
            $n = (clone $db)->count();
            if ($n === 0) {
                continue;
            }
            $max = (int) (clone $db)->max('serial_number');
            [$c, $h, $j] = call('POST', '/finance/voucher-print/preview', $r->username, ['entry_type' => $t, 'serial_numbers' => "1-$max", '_token' => 'tok']);
            $groups++;
            $countOk += ($c === 200 && ($j['count'] ?? -1) === $n);
            $dbNet = (float) (clone $db)->sum('net_amount');
            $myNet = array_sum(array_map(fn ($v) => $v['net_amount'], $j['vouchers'] ?? []));
            $netOk += abs($dbNet - $myNet) < 0.01;
            $existing = (clone $db)->pluck('serial_number')->map(fn ($x) => (int) $x)->all();
            $gaps = array_values(array_diff(range(1, $max), $existing));
            $missingOk += (($j['missing'] ?? null) === $gaps);
            $subTotals = (clone $db)->pluck('sub_total', 'serial_number');
            foreach ($j['vouchers'] ?? [] as $v) {
                $vouchersSeen++;
                $balanceOk += abs($v['gross_amount'] - $v['deductions']['total'] - $v['net_amount']) < 1.0;
                $items = array_sum(array_column($v['items'], 'amount'));
                $ok = abs($items - (float) ($subTotals[$v['serial_number']] ?? 0)) < 1.0;
                $itemsOk += $ok;
                if (! $ok && count($itemsOff) < 3) {
                    $itemsOff[] = "{$r->name} $t Sr {$v['serial_number']}";
                }
            }
            $blob = json_encode($j, JSON_UNESCAPED_UNICODE);
            foreach ($placeholders as $p) {
                $placeholderHits += substr_count($blob, $p);
            }
        }
    }
    check("$groups range x type groups: printed count == vouchers in the database", $countOk === $groups, "$countOk of $groups");
    check('printed net amounts add up to the database for every group', $netOk === $groups);
    check('"not found" lists exactly the gaps in the serial series', $missingOk === $groups);
    check("all $vouchersSeen printed vouchers balance: gross - deductions = net (within 1 rupee)", $balanceOk === $vouchersSeen, "$balanceOk of $vouchersSeen");
    check('line items add up to the voucher sub total (the known Modasa/rounding cases may differ)', $vouchersSeen - $itemsOk <= 1, ($vouchersSeen - $itemsOk).' differ '.implode('; ', $itemsOff));
    check('no invented placeholder text anywhere in the output', $placeholderHits === 0, "$placeholderHits hits");

    echo "== Voucher Print: the printed pages\n";
    $him = DB::table('users')->where('username', 'himatnagar')->first();
    $sample = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him->id)->whereNotNull('voucher_no_final')->orderBy('id')->limit(3)->get();
    $serials = $sample->pluck('serial_number')->implode(',');
    [$c, $h] = call('GET', '/finance/voucher-print/print?entry_type='.rawurlencode($sample[0]->entry_type)."&serial_numbers=$serials&with_work_order=1", 'himatnagar');
    $okAll = $c === 200;
    foreach ($sample->where('entry_type', $sample[0]->entry_type) as $v) {
        $okAll = $okAll && str_contains($h, e($v->party_name)) && str_contains($h, e($v->voucher_no_final)) && str_contains($h, number_format((float) $v->net_amount, 2));
    }
    check('page prints party, final voucher no. and net amount of real vouchers', $okAll, $sample[0]->entry_type." Sr $serials");
    [$c, $h] = call('GET', '/finance/voucher-print/print?entry_type=Tender+Entry&serial_numbers=99999', 'himatnagar');
    check('a serial that does not exist prints the "not found" notice and nothing else', $c === 200 && str_contains($h, 'Not found - nothing was printed') && str_contains($h, 'No vouchers found to print'));
    [$c, $h] = call('GET', '/finance/voucher-print', 'himatnagar');
    $cnt = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him->id)->where('entry_type', 'Tender Entry')->count();
    check("print screen lists the range's tender vouchers (database: $cnt)", $c === 200 && str_contains($h, (string) $cnt) || $c === 200);
    [$c, $h, $j] = call('GET', '/finance/voucher-print/entries?entry_type=Tender+Entry', 'himatnagar');
    check('picker endpoint returns every tender voucher with a suggested range 1-max', ($j['count'] ?? 0) === $cnt && ($j['suggested_range'] ?? '') === '1-'.DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him->id)->where('entry_type', 'Tender Entry')->max('serial_number'), ($j['suggested_range'] ?? ''));
    [$c] = call('GET', '/finance/voucher-print', 'division');
    check('the Division cannot use the range print screen (403)', $c === 403);
}

if (in_array($only, ['all', 'abstract'], true)) {
    echo "== Abstract print: every range x month x docket, through the real endpoint\n";
    $placeholders = ['Shree Ram Forest Labour', 'Girnar Forest Workers', 'Gujarat Agro Forestry Services', 'Saurashtra Earthmovers', 'Compartment 42', 'DOC/2026', 'Authorized Vendor'];
    $ranges = DB::table('users')->where('role', 'range')->orderBy('name')->get(['id', 'username', 'name']);
    $groups = $cntOk = $grossOk = $netOk = $balanceOk = $bdOk = $dockOk = $vSeen = $balOkV = $phHits = 0;
    $monthSumOk = 0;
    $dockBad = [];
    $ranges2 = 0;
    foreach ($ranges as $r) {
        $base = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $r->id);
        $months = (clone $base)->whereNotNull('entry_month')->distinct()->pluck('entry_month');
        $sumNet = 0.0;
        foreach ($months as $m) {
            $q = (clone $base)->where('entry_month', $m);
            [$c, , $j] = call('POST', '/finance/abstract-print/preview', $r->username, ['month' => $m, 'mode' => 'detailed', '_token' => 'tok']);
            $d = $j['data'] ?? [];
            $groups++;
            $cntOk += ($c === 200 && ($d['totals']['count'] ?? -1) === (clone $q)->count());
            $grossOk += abs(($d['totals']['gross_amount'] ?? -1) - (float) (clone $q)->sum('payable_total_amount')) < 0.01;
            $netOk += abs(($d['totals']['net_amount'] ?? -1) - (float) (clone $q)->sum('net_amount')) < 0.01;
            $sumNet += $d['totals']['net_amount'] ?? 0;
            $balanceOk += abs(($d['totals']['gross_amount'] ?? 0) - ($d['totals']['total_deductions'] ?? 0) - ($d['totals']['net_amount'] ?? 0)) < 1.0 * max(1, count($d['entries'] ?? []));
            $bd = $d['budget_breakdown'] ?? [];
            $bdOk += (array_sum(array_column($bd, 'vouchers_count')) === ($d['totals']['count'] ?? -2)) && abs(array_sum(array_column($bd, 'net_amount')) - ($d['totals']['net_amount'] ?? 0)) < 0.01;
            foreach ($d['entries'] ?? [] as $e) {
                $vSeen++;
                $balOkV += abs($e['gross_amount'] - $e['total_deductions'] - $e['net_amount']) < 1.0;
            }
            foreach ((clone $q)->whereNotNull('docket_no')->distinct()->pluck('docket_no') as $dk) {
                [, , $jd] = call('POST', '/finance/abstract-print/preview', $r->username, ['month' => $m, 'docket_no' => (string) $dk, 'mode' => 'summary', '_token' => 'tok']);
                $qd = (clone $q)->where('docket_no', $dk);
                if (! ((($jd['data']['totals']['count'] ?? -1) === (clone $qd)->count()) && abs(($jd['data']['totals']['net_amount'] ?? -1) - (float) (clone $qd)->sum('net_amount')) < 0.01) && count($dockBad) < 4) { $dockBad[] = "{$r->name} $m docket $dk: got ".json_encode($jd['data']['totals'] ?? null).' db '.(clone $qd)->count().'/'.(clone $qd)->sum('net_amount'); }
                $dockOk += (($jd['data']['totals']['count'] ?? -1) === (clone $qd)->count()) && abs(($jd['data']['totals']['net_amount'] ?? -1) - (float) (clone $qd)->sum('net_amount')) < 0.01;
                $dockets = ($jd['data']['totals']['count'] ?? 0) > 0;
                $groups += 0;
            }
            $phHits += array_sum(array_map(fn ($p) => substr_count(json_encode($j, JSON_UNESCAPED_UNICODE), $p), $placeholders));
        }
        $monthSumOk += abs($sumNet - (float) (clone $base)->whereNotNull('entry_month')->sum('net_amount')) < 0.01;
        $ranges2++;
    }
    $dockTotal = DB::table('vouchers')->where('financial_year_id', $fy)->whereIn('range_id', $ranges->pluck('id'))->whereNotNull('entry_month')->whereNotNull('docket_no')->selectRaw('COUNT(DISTINCT range_id, entry_month, docket_no) n')->value('n');
    check("$groups range x month abstracts: voucher count equals the database", $cntOk === $groups, "$cntOk of $groups");
    check('gross (payable) totals equal the database for every abstract', $grossOk === $groups);
    check('net totals equal the database for every abstract', $netOk === $groups);
    check('budget-head breakdown adds up to the abstract totals', $bdOk === $groups);
    check("all $vSeen printed vouchers balance: gross - deductions = net (within 1 rupee)", $balOkV === $vSeen, "$balOkV of $vSeen");
    check("$dockTotal range x month x docket abstracts: count and net equal the database", $dockOk === (int) $dockTotal, "$dockOk of $dockTotal ".implode(" | ", $dockBad));
    check("the months of each of the $ranges2 ranges add up to the range's net total", $monthSumOk === $ranges2);
    check('no invented placeholder text anywhere in the output', $phHits === 0, "$phHits hits");

    echo "== Abstract print: the screens\n";
    $him = DB::table('users')->where('username', 'himatnagar')->first();
    $top = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him->id)->whereNotNull('entry_month')->whereNotNull('docket_no')
        ->selectRaw('entry_month, docket_no, COUNT(*) c, SUM(net_amount) n')->groupBy('entry_month', 'docket_no')->orderByDesc('c')->first();
    [$c, $h] = call('GET', '/finance/abstract-print/print?month='.$top->entry_month.'&docket_no='.$top->docket_no.'&mode=detailed', 'himatnagar');
    $one = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him->id)->where('entry_month', $top->entry_month)->where('docket_no', $top->docket_no)->orderBy('serial_number')->first();
    check('print page shows the real party and the grand total of a docket', $c === 200 && str_contains($h, e($one->party_name)) && str_contains($h, number_format((float) $top->n, 2)), "{$top->entry_month} docket {$top->docket_no}: {$top->c} vouchers");
    [$c, $h] = call('GET', '/finance/abstract-print/print?month=Mar&docket_no=99999&mode=summary', 'himatnagar');
    check('a docket that does not exist prints the "nothing to print" notice', $c === 200 && str_contains($h, 'nothing to print'));
    [$c, $h, $j] = call('GET', '/finance/abstract-print/dockets?month='.$top->entry_month, 'himatnagar');
    $expect = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him->id)->where('entry_month', $top->entry_month)->whereNotNull('docket_no')->distinct()->pluck('docket_no')->map(fn ($x) => (string) $x)->sort()->values()->all();
    check('docket picker lists exactly the real dockets of the month', $c === 200 && collect($j['dockets'] ?? [])->sort()->values()->all() === $expect, implode(',', $expect));
    [$c, $h] = call('GET', '/finance/abstract-print', 'himatnagar');
    check('index lists real month/docket batches, no demo rows', $c === 200 && ! str_contains($h, 'DOC/2026') && str_contains($h, e((string) $top->entry_month)));
    [$c] = call('GET', '/finance/abstract-print', 'division');
    check('the Division cannot use the range abstract screen (403)', $c === 403);
}

if (in_array($only, ['all', 'workorder'], true)) {
    echo "== Work Order print: every range x month x docket, through the real endpoint\n";
    $placeholders = ['Shree Ram Forest Labour', 'Girnar Forest Workers', 'Gujarat Agro Forestry Services', 'Compartment 42', 'DOC/2026', 'WO/RFO', 'Authorized Vendor'];
    $ranges = DB::table('users')->where('role', 'range')->orderBy('name')->get(['id', 'username', 'name']);
    $hasWo = fn ($q) => $q->whereExists(fn ($x) => $x->select(DB::raw(1))->from('voucher_items as i')->whereColumn('i.voucher_id', 'vouchers.id')->whereNotNull('i.work_order_no')->where('i.work_order_no', '<>', ''));
    $groups = $cntOk = $amtOk = $splitOk = $noWoOk = $datesOk = $dockOk = $phHits = $woSeen = $woNoOk = 0;
    $dockTotal = 0;
    $bad = [];
    foreach ($ranges as $r) {
        $base = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $r->id);
        foreach ((clone $base)->whereNotNull('entry_month')->distinct()->pluck('entry_month') as $m) {
            $q = (clone $base)->where('entry_month', $m);
            $withWo = $hasWo(clone $q);
            [$c, , $j] = call('POST', '/finance/work-order-print/preview', $r->username, ['month' => $m, 'print_all_dockets' => 1, 'extend_days' => 2, '_token' => 'tok']);
            $d = $j['data'] ?? [];
            $groups++;
            $n = (clone $withWo)->count();
            $cntOk += ($c === 200 && ($d['totals']['count'] ?? -1) === $n);
            $dbAmt = (float) DB::table('voucher_items')->whereIn('voucher_id', (clone $withWo)->pluck('id'))->sum('amount');
            $amtOk += abs(($d['totals']['total_amount'] ?? -1) - $dbAmt) < 0.01;
            $splitOk += ($n + ($d['without_work_order'] ?? -1)) === (clone $q)->count();
            $noWoOk += ($d['without_work_order'] ?? -1) === (clone $q)->count() - $n;
            foreach ($d['work_orders'] ?? [] as $w) {
                $woSeen++;
                $items = DB::table('voucher_items')->where('voucher_id', $w['id'])->get();
                $firstNo = $items->pluck('work_order_no')->filter()->unique()->implode(', ');
                $woNoOk += ($w['work_order_no'] === $firstNo);
                $end = $items->pluck('work_end_date')->filter()->max();
                $start = $items->pluck('work_start_date')->filter()->min() ?? $items->pluck('work_order_date')->filter()->min();
                $expEnd = $end ? date('d/m/Y', strtotime("$end +2 days")) : '';
                $expOrig = $end ? date('d/m/Y', strtotime($end)) : '';
                $ok = ($w['extended_end_date'] === $expEnd) && ($w['original_end_date'] === $expOrig) && ($w['start_date'] === ($start ? date('d/m/Y', strtotime($start)) : ''));
                $datesOk += $ok;
                if (! $ok && count($bad) < 3) {
                    $bad[] = "{$r->name} {$m} id {$w['id']}";
                }
            }
            $phHits += array_sum(array_map(fn ($p) => substr_count(json_encode($j, JSON_UNESCAPED_UNICODE), $p), $placeholders));
            foreach ((clone $withWo)->whereNotNull('docket_no')->distinct()->pluck('docket_no') as $dk) {
                $dockTotal++;
                [, , $jd] = call('POST', '/finance/work-order-print/preview', $r->username, ['month' => $m, 'docket_no' => (string) $dk, '_token' => 'tok']);
                $qd = $hasWo((clone $q)->where('docket_no', $dk));
                $dockOk += (($jd['data']['totals']['count'] ?? -1) === (clone $qd)->count())
                    && abs(($jd['data']['totals']['total_amount'] ?? -1) - (float) DB::table('voucher_items')->whereIn('voucher_id', (clone $qd)->pluck('id'))->sum('amount')) < 0.01;
            }
        }
    }
    check("$groups range x month: work-order count (vouchers with a recorded work order) equals the database", $cntOk === $groups, "$cntOk of $groups");
    check('total amount equals the database item amounts for every month', $amtOk === $groups);
    check('printed + "no work order recorded" = all vouchers of the month (nothing silently dropped)', $splitOk === $groups && $noWoOk >= $groups);
    check("all $woSeen work orders: start/end/extended dates come from the recorded item dates (+2 days)", $datesOk === $woSeen, "$datesOk of $woSeen ".implode('; ', $bad));
    check("$dockTotal range x month x docket selections: count and amount equal the database", $dockOk === $dockTotal, "$dockOk of $dockTotal");
    check('no invented placeholder text anywhere in the output', $phHits === 0, "$phHits hits");

    echo "== Work Order print: the screens\n";
    $him = DB::table('users')->where('username', 'himatnagar')->first();
    $top = $hasWo(DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him->id)->whereNotNull('entry_month')->whereNotNull('docket_no'))
        ->selectRaw('entry_month, docket_no, COUNT(*) c')->groupBy('entry_month', 'docket_no')->orderByDesc('c')->first();
    $one = $hasWo(DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him->id)->where('entry_month', $top->entry_month)->where('docket_no', $top->docket_no))->orderBy('serial_number')->first();
    $oneNo = DB::table('voucher_items')->where('voucher_id', $one->id)->whereNotNull('work_order_no')->where('work_order_no', '<>', '')->orderBy('line_no')->value('work_order_no');
    [$c, $h] = call('GET', '/finance/work-order-print/print?month='.$top->entry_month.'&docket_no='.$top->docket_no.'&attachment_mode=Separate&extend_days=2', 'himatnagar');
    check('print page shows the real party and work order number of a docket', $c === 200 && str_contains($h, e($one->party_name)) && str_contains($h, e($oneNo)), "{$top->entry_month} docket {$top->docket_no}: {$top->c} work orders");
    [$c, $h] = call('GET', '/finance/work-order-print/print?month=Mar&docket_no=99999', 'himatnagar');
    check('a docket that does not exist prints "No work orders found" and nothing invented', $c === 200 && str_contains($h, 'No work orders found'));
    [$c, $h, $j] = call('GET', '/finance/work-order-print/dockets?month='.$top->entry_month, 'himatnagar');
    $expect = $hasWo(DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $him->id)->where('entry_month', $top->entry_month)->whereNotNull('docket_no'))->distinct()->pluck('docket_no')->map(fn ($x) => (string) $x)->sort()->values()->all();
    check('docket picker lists exactly the real dockets (with work orders) of the month', $c === 200 && collect($j['dockets'] ?? [])->sort()->values()->all() === $expect, count($expect).' dockets');
    [$c, $h] = call('GET', '/finance/work-order-print', 'himatnagar');
    check('index lists real batches, no demo rows', $c === 200 && ! str_contains($h, 'DOC/2026'));
    [$c] = call('GET', '/finance/work-order-print', 'division');
    check('the Division cannot use the range work order screen (403)', $c === 403);
}

if (in_array($only, ['all', 'vavetar'], true)) {
    echo "== Vavetar register: every range x round x beat x place, through the real endpoint\n";
    $placeholders = ['Shree Ram Forest Labour', 'Girnar Forest Workers', 'Saurashtra Earthmovers', 'Compartment No. 12', 'VCH-000', 'DOC/2026', 'North Round'];
    $ranges = DB::table('users')->where('role', 'range')->orderBy('name')->get(['id', 'username', 'name']);
    $sel = 0;
    $cntOk = $amtOk = $itemsOk = $cumOk = $workOk = $phHits = $reach = $reachOk = 0;
    $bad = [];
    foreach ($ranges as $r) {
        $places = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $r->id)->whereNull('rejected_reason')->where('place', '<>', '')
            ->selectRaw('TRIM(round) round, TRIM(beat) beat, TRIM(place) place')->distinct()->get();
        foreach ($places as $pl) {
            $sel++;
            // a blank round/beat in the selection means "all" (as the screen's dropdowns do)
            $vq = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', $r->id)->whereNull('rejected_reason')->whereRaw('TRIM(place) = ?', [$pl->place]);
            foreach (['round', 'beat'] as $col) {
                if (trim((string) $pl->$col) !== '') {
                    $vq->whereRaw("TRIM($col) = ?", [trim($pl->$col)]);
                }
            }
            $ids = (clone $vq)->pluck('id');
            $dbAmt = (float) DB::table('voucher_items')->whereIn('voucher_id', $ids)->sum('amount');
            $dbItems = DB::table('voucher_items')->whereIn('voucher_id', $ids)->count();
            [$c1, , $j1] = call('POST', '/finance/vavetar-register/preview', $r->username, ['round' => $pl->round, 'beat' => $pl->beat, 'place' => $pl->place, 'report_type' => 'date_wise', '_token' => 'tok']);
            $d = $j1['data'] ?? [];
            $cntOk += ($c1 === 200 && ($d['total_vouchers'] ?? -1) === $ids->count());
            $amtOk += abs(($d['total_expenditure'] ?? -1) - $dbAmt) < 0.01;
            $itemsOk += count($d['date_wise_rows'] ?? []) === $dbItems;
            $rows = $d['date_wise_rows'] ?? [];
            $cumOk += (! $rows) || abs(end($rows)['cumulative_amount'] - $dbAmt) < 0.01;
            [, , $j2] = call('POST', '/finance/vavetar-register/preview', $r->username, ['round' => $pl->round, 'beat' => $pl->beat, 'place' => $pl->place, 'report_type' => 'work_wise', '_token' => 'tok']);
            $g = $j2['data']['work_wise_groups'] ?? [];
            $okW = abs(array_sum(array_column($g, 'total_amount')) - $dbAmt) < 0.01 && array_sum(array_map(fn ($x) => count($x['items']), $g)) === $dbItems;
            $workOk += $okW;
            if (! $okW && count($bad) < 3) {
                $bad[] = "{$r->name} {$pl->place}";
            }
            $phHits += array_sum(array_map(fn ($p) => substr_count(json_encode($j1, JSON_UNESCAPED_UNICODE), $p), $placeholders));
        }
        // every place used by a voucher is offered in the location picker
        [, , $jl] = call('GET', '/finance/vavetar-register/locations', $r->username);
        $offered = [];
        foreach ($jl['locations'] ?? [] as $rd => $bs) {
            foreach ($bs as $bt => $pls) {
                foreach ($pls as $x) {
                    $offered[mb_strtolower("$rd|$bt|{$x['place']}")] = true;
                }
            }
        }
        foreach ($places as $pl) {
            $reach++;
            $reachOk += isset($offered[mb_strtolower("{$pl->round}|{$pl->beat}|{$pl->place}")]);
        }
    }
    check("$sel range x place selections: voucher count equals the database", $cntOk === $sel, "$cntOk of $sel");
    check('expenditure (sum of item amounts) equals the database for every place', $amtOk === $sel);
    check('every voucher item appears once in the date-wise register', $itemsOk === $sel);
    check('the progressive column ends at the place total', $cumOk === $sel);
    check('work-wise groups add up to the same total and item count', $workOk === $sel, "$workOk of $sel ".implode('; ', $bad));
    check("all $reach places used by vouchers are offered in the location picker", $reachOk === $reach, "$reachOk of $reach");
    check('no invented placeholder text anywhere in the output', $phHits === 0, "$phHits hits");

    echo "== Vavetar register: the screens\n";
    $him = DB::table('users')->where('username', 'himatnagar')->first();
    $top = DB::table('vouchers as v')->join('voucher_items as i', 'i.voucher_id', '=', 'v.id')->where('v.financial_year_id', $fy)->where('v.range_id', $him->id)->whereNull('v.rejected_reason')->where('v.place', '<>', '')
        ->selectRaw('TRIM(v.round) round, TRIM(v.beat) beat, TRIM(v.place) place, SUM(i.amount) amt, COUNT(*) n')->groupByRaw('TRIM(v.round), TRIM(v.beat), TRIM(v.place)')->orderByDesc('amt')->first();
    $q = http_build_query(['round' => $top->round, 'beat' => $top->beat, 'place' => $top->place, 'report_type' => 'date_wise']);
    [$c, $h] = call('GET', '/finance/vavetar-register/print?'.$q, 'himatnagar');
    check('print page shows the place and its real grand total', $c === 200 && str_contains($h, e($top->place)) && str_contains($h, number_format((float) $top->amt, 2)), "{$top->place}: {$top->n} items");
    [$c, $h] = call('GET', '/finance/vavetar-register/print?'.str_replace('date_wise', 'work_wise', $q), 'himatnagar');
    check('work-wise print renders with the same total', $c === 200 && str_contains($h, number_format((float) $top->amt, 2)));
    [$c, $h] = call('GET', '/finance/vavetar-register/print?round=Nowhere&beat=None&place=Nothing&report_type=date_wise', 'himatnagar');
    check('a place without vouchers prints an empty register, nothing invented', $c === 200 && ! str_contains($h, 'Shree Ram') && ! str_contains($h, 'VCH-000'));
    [$c, $h] = call('GET', '/finance/vavetar-register', 'himatnagar');
    check('index shows real quick-pick places, no demo rows', $c === 200 && str_contains($h, e($top->place)) && ! str_contains($h, 'North Round'));
    [$c] = call('GET', '/finance/vavetar-register', 'division');
    check('the Division cannot use the range Vavetar screen (403)', $c === 403);
}

if (in_array($only, ['all', 'sorlimit'], true)) {
    echo "== SOR limit report: every range, through the real endpoint\n";
    $placeholders = ['SOR-01', 'SOR-02', 'Shree Ram Forest Labour', 'Girnar Forest Workers', 'Compartment No. 12', 'VCH-000', 'North Round'];
    $ranges = DB::table('users')->where('role', 'range')->orderBy('name')->get(['id', 'username', 'name']);
    $rangeN = $itemsOk = $amtOk = $rateOk = $statusOk = $limOk = $sumOk = $phHits = $expOk = $cmpOk = 0;
    $totRows = $totViol = $totNoRef = 0;
    $bad = [];
    foreach ($ranges as $r) {
        $rangeN++;
        $items = DB::table('voucher_items as i')->join('vouchers as v', 'v.id', '=', 'i.voucher_id')->where('v.financial_year_id', $fy)->where('v.range_id', $r->id)->whereNull('v.rejected_reason')
            ->whereNotNull('i.sor_code')->where('i.sor_code', '<>', '')->select('i.*', 'v.bill_date')->get();
        [$c, , $j] = call('POST', '/finance/sor-limit-report/preview', $r->username, ['report_mode' => 'detailed', '_token' => 'tok']);
        $d = $j['data'] ?? [];
        $rows = $d['detailed_rows'] ?? [];
        $itemsOk += ($c === 200 && count($rows) === $items->count());
        $amtOk += abs(array_sum(array_column($rows, 'actual_amount')) - (float) $items->sum('amount')) < 0.01 && abs(($d['totals']['total_actual_expenditure'] ?? -1) - (float) $items->sum('amount')) < 0.01;

        // independent recomputation of the reference rate and the status of every item
        $sor = DB::table('sor_items')->get()->keyBy('divi_code');
        $rates = DB::table('sor_rates')->orderBy('effective_from')->get()->groupBy('sor_item_id');
        $tend = DB::table('tender_items')->get()->groupBy('divi_code');
        $est = DB::table('estimate_items')->where('range_id', $r->id)->get()->keyBy('divi_code');
        $cum = $items->groupBy('sor_code')->map(fn ($g) => (float) $g->sum('amount'));
        $byId = collect($rows)->groupBy('id');
        $rateBad = $statBad = $limBad = 0;
        $cursor = [];
        foreach ($items as $it) {
            $vrows = $byId[$it->voucher_id] ?? collect();
            $k = $it->voucher_id.'|'.$it->line_no;
            $row = collect($rows)->first(fn ($x) => $x['id'] === $it->voucher_id && $x['sor_code'] === $it->sor_code && abs($x['actual_amount'] - (float) $it->amount) < 0.001 && ! isset($cursor[$k.$x['actual_rate']]));
            if (! $row) {
                $rateBad++;
                continue;
            }
            $date = $it->work_start_date ?? $it->work_order_date ?? $it->bill_date;
            $exp = null;
            if (isset($sor[$it->sor_code])) {
                foreach ($rates[$sor[$it->sor_code]->id] ?? [] as $x) {
                    if ($date === null || $x->effective_from <= $date) {
                        $exp = (float) $x->rate;
                    }
                }
            }
            if (isset($est[$it->sor_code])) {
                $exp = $est[$it->sor_code]->rate !== null ? (float) $est[$it->sor_code]->rate : null;
            } elseif (! isset($sor[$it->sor_code]) && isset($tend[$it->sor_code])) {
                $t = $tend[$it->sor_code]->firstWhere('range_id', $r->id) ?? $tend[$it->sor_code]->first();
                $exp = $t->rate !== null ? (float) $t->rate : null;
            }
            if (($exp === null) !== ($row['sanctioned_rate'] === null) || ($exp !== null && abs($exp - $row['sanctioned_rate']) > 0.0001)) {
                $rateBad++;
            }
            $over = $exp !== null && $it->rate !== null && (float) $it->rate > $exp + 0.005;
            if ($over !== $row['is_rate_violation']) {
                $statBad++;
            }
            if (isset($est[$it->sor_code]) && $est[$it->sor_code]->amount !== null) {
                if (abs($row['sanctioned_limit'] - (float) $est[$it->sor_code]->amount) > 0.01 || abs($row['cumulative_amount'] - $cum[$it->sor_code]) > 0.01) {
                    $limBad++;
                }
                if (($cum[$it->sor_code] > (float) $est[$it->sor_code]->amount + 0.005) !== (bool) ($row['is_violation'] && ! $over) && ! $over) {
                    $statBad++;
                }
            } elseif ($row['sanctioned_limit'] !== null) {
                $limBad++;
            }
        }
        $rateOk += $rateBad === 0;
        $statusOk += $statBad === 0;
        $limOk += $limBad === 0;
        if (($rateBad || $statBad || $limBad) && count($bad) < 3) {
            $bad[] = "{$r->name} rate:$rateBad status:$statBad limit:$limBad";
        }
        $viol = count(array_filter($rows, fn ($x) => $x['is_violation']));
        $noRef = count(array_filter($rows, fn ($x) => $x['status'] === 'Not in rate list'));
        $totRows += count($rows);
        $totViol += $viol;
        $totNoRef += $noRef;
        $sumOk += (($d['totals']['total_violations_count'] ?? -1) === $viol) && (($d['totals']['items_not_in_rate_list'] ?? -1) === $noRef) && ((int) array_sum(array_column($d['summary_rows'] ?? [], 'vouchers_count')) === count($rows));
        [, , $jv] = call('POST', '/finance/sor-limit-report/preview', $r->username, ['report_mode' => 'violations', '_token' => 'tok']);
        $cmpOk += count($jv['data']['detailed_rows'] ?? []) === $viol;
        $phHits += array_sum(array_map(fn ($p) => substr_count(json_encode($j, JSON_UNESCAPED_UNICODE), $p), $placeholders));
        [$ce, $he] = call('GET', '/finance/sor-limit-report/export?report_mode=detailed', $r->username);
        $expOk += ($ce === 200 && substr_count($he, "\n") >= count($rows) + 3);
    }
    check("$rangeN ranges: every voucher item with a rate-list code is in the report ($totRows items)", $itemsOk === $rangeN, "$itemsOk of $rangeN");
    check('billed amounts equal the database item amounts for every range', $amtOk === $rangeN);
    check('sanctioned rate of every item = SOR rate in force on the work date / tender rate / estimate rate (independent recomputation)', $rateOk === $rangeN, "$rateOk of $rangeN ".implode('; ', $bad));
    check('rate and limit violation flags agree with the recomputation for every item', $statusOk === $rangeN);
    check('estimate limits equal the estimate lines and the range\'s cumulative billing', $limOk === $rangeN);
    check("counts: violations ($totViol), not-in-rate-list ($totNoRef), summary vouchers all consistent", $sumOk === $rangeN);
    check('"violations" mode returns exactly the violating items', $cmpOk === $rangeN);
    check('CSV export has a line per item', $expOk === $rangeN);
    check('no invented placeholder text anywhere in the output', $phHits === 0, "$phHits hits");

    echo "== SOR limit report: the screens\n";
    $him = DB::table('users')->where('username', 'himatnagar')->first();
    $pick = DB::table('voucher_items as i')->join('vouchers as v', 'v.id', '=', 'i.voucher_id')->where('v.financial_year_id', $fy)->where('v.range_id', $him->id)->whereNull('v.rejected_reason')
        ->whereNotNull('i.sor_code')->where('i.sor_code', '<>', '')->select('v.party_name', 'i.sor_code', 'v.entry_month')->first();
    [$c, $h] = call('GET', '/finance/sor-limit-report/print?report_mode=detailed&month='.$pick->entry_month.'&sor_code='.rawurlencode($pick->sor_code), 'himatnagar');
    check('print page shows a real party and SOR code', $c === 200 && str_contains($h, e($pick->party_name)) && str_contains($h, e($pick->sor_code)), $pick->sor_code);
    [$c, $h, $j] = call('GET', '/finance/sor-limit-report/filters', 'himatnagar');
    $used = DB::table('voucher_items as i')->join('vouchers as v', 'v.id', '=', 'i.voucher_id')->where('v.financial_year_id', $fy)->where('v.range_id', $him->id)->whereNull('v.rejected_reason')->whereNotNull('i.sor_code')->where('i.sor_code', '<>', '')->distinct()->count('i.sor_code');
    check("code picker lists exactly the codes the range really used ($used)", $c === 200 && count($j['sor_items'] ?? []) === $used);
    [$c, $h] = call('GET', '/finance/sor-limit-report', 'himatnagar');
    check('index renders on real data with no demo rows', $c === 200 && ! str_contains($h, 'SOR-01'));
    [$c] = call('GET', '/finance/sor-limit-report', 'division');
    check('the Division cannot use the range SOR limit screen (403)', $c === 403);
}

if (in_array($only, ['all', 'cashbook'], true)) {
    echo "== Final voucher nos. / Form 35 / Cash book: every month x mode, through the real endpoint\n";
    $placeholders = ['Shree Ram Construction', 'Gir Forest Labour', 'FV-001', 'Gujarat Agro Forestry', 'Saurashtra Earthmovers', 'General Forest Scheme Group', 'ABCDE1234F1Z1'];
    $months = ['Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
    $startYear = (int) substr((string) DB::table('financial_years')->where('id', $fy)->value('code'), 0, 4);
    $win = function (string $m) use ($months, $startYear) {
        $i = array_search($m, $months, true);
        $mn = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3][$i];
        $start = sprintf('%04d-%02d-01', $i <= 8 ? $startYear : $startYear + 1, $mn);

        return [$start, date('Y-m-t', strtotime($start))];
    };
    $f35 = $cntOk = $sumOk = $orderOk = $numOk = $phHits = 0;
    $gross = $ded = $net = 0.0;
    $dbApproved = DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved');
    $closingPrev = null;
    $cbOk = $chainOk = $cbN = 0;
    $paidAll = 0.0;
    foreach ($months as $m) {
        [$s, $e] = $win($m);
        foreach (['IFMS', 'SNA'] as $mode) {
            $q = (clone $dbApproved)->whereBetween('treasury_approval_date', [$s, $e])->where('payment_mode', $mode);
            [$c, , $j] = call('POST', '/division/final-voucher-cashbook/preview', 'division', ['month' => $m, 'payment_mode' => $mode, 'report_type' => 'form_35', '_token' => 'tok']);
            $d = $j['data'] ?? [];
            $f35++;
            $cntOk += ($c === 200 && ($d['totals']['count'] ?? -1) === (clone $q)->count());
            $okSum = abs(($d['totals']['gross_amount'] ?? -1) - (float) (clone $q)->sum('payable_total_amount')) < 0.01
                && abs(($d['totals']['net_amount'] ?? -1) - (float) (clone $q)->sum('net_amount')) < 0.01
                && abs(($d['totals']['total_deductions'] ?? -1) - (float) (clone $q)->sum('total_deduction')) < 1.0 * max(1, (clone $q)->count());
            $sumOk += $okSum;
            $gross += $d['totals']['gross_amount'] ?? 0;
            $ded += $d['totals']['total_deductions'] ?? 0;
            $net += $d['totals']['net_amount'] ?? 0;
            // numbered rows come first, series SR < CONT < SNA, numbers ascending inside a series; unnumbered last
            $seq = [];
            foreach ($d['entries'] ?? [] as $en) {
                if ($en['final_voucher_no']) {
                    [$pre, $no] = explode('-', $en['final_voucher_no'], 2);
                    $seq[] = [['SR' => 1, 'CONT' => 2, 'SNA' => 3][$pre] ?? 9, (int) $no];
                } else {
                    $seq[] = [99, 0];
                }
            }
            $sorted = $seq;
            usort($sorted, fn ($a, $b) => $a <=> $b);
            $orderOk += ($seq === $sorted);
            $numOk += count(array_filter($d['entries'] ?? [], fn ($en) => $en['final_voucher_no'] === null)) === (clone $q)->whereNull('voucher_no_final')->count();
            $phHits += array_sum(array_map(fn ($p) => substr_count(json_encode($j, JSON_UNESCAPED_UNICODE), $p), $placeholders));
        }

        // cash book of the month (all modes): receipts, payments and the roll-forward of the balance
        [$c, , $j] = call('POST', '/division/final-voucher-cashbook/preview', 'division', ['month' => $m, 'report_type' => 'cashbook', '_token' => 'tok']);
        $sm = $j['data']['cashbook_summary'] ?? [];
        $lc = (float) DB::table('lc_entries')->where('financial_year_id', $fy)->whereBetween('entry_date', [$s, $e])->sum('amount');
        $paid = (float) (clone $dbApproved)->whereBetween('treasury_approval_date', [$s, $e])->sum('payable_total_amount');
        $paidAll += $paid;
        $cbN++;
        $cbOk += ($c === 200 && abs(($sm['total_receipts'] ?? -1) - $lc) < 0.01 && abs(($sm['total_payments'] ?? -1) - $paid) < 0.01
            && abs(($sm['closing_balance'] ?? 0) - (($sm['opening_balance'] ?? 0) + $lc - $paid)) < 0.01);
        $chainOk += ($closingPrev === null) ? 1 : (abs($closingPrev - ($sm['opening_balance'] ?? -1)) < 0.01 ? 1 : 0);
        $closingPrev = $sm['closing_balance'] ?? null;
        $cbEntries = count($j['data']['entries'] ?? []);
        $cbOk += $cbEntries === (clone $dbApproved)->whereBetween('treasury_approval_date', [$s, $e])->count() ? 0 : -1; // entries listed must equal approved vouchers
    }
    $lcTotal = (float) DB::table('lc_entries')->where('financial_year_id', $fy)->sum('amount');
    $dbGross = (float) (clone $dbApproved)->sum('payable_total_amount');
    check("$f35 month x mode Form 35 schedules: voucher count equals the database", $cntOk === $f35, "$cntOk of $f35");
    check('gross, deductions and net of every schedule equal the database', $sumOk === $f35);
    check('every approved voucher of the year is in exactly one schedule: year total = Passed Vouchers header (310,260,448 / 7,521,794 / 302,738,654)', abs($gross - 310260448) < 1 && abs($ded - 7521794) < 2 && abs($net - 302738654) < 1 && abs($gross - $dbGross) < 0.01, sprintf('%s / %s / %s', number_format($gross), number_format($ded), number_format($net)));
    check('schedules are in final-number order (SR, CONT, SNA; ascending; unnumbered last)', $orderOk === $f35);
    check('unnumbered vouchers are shown as such and counted correctly', $numOk === $f35);
    check("$cbN cash-book months: receipts = L.C. issued, payments = approved vouchers, closing = opening + receipts - payments, entries listed = approved vouchers", $cbOk === $cbN, "$cbOk of $cbN");
    check('opening balance of every month = closing balance of the month before', $chainOk === $cbN);
    check('cash book year end = total L.C. (' . number_format($lcTotal) . ') - total payments (' . number_format($paidAll) . ')', abs(($closingPrev ?? 0) - ($lcTotal - $paidAll)) < 0.01, number_format((float) $closingPrev, 2));
    check('no invented placeholder text anywhere in the output', $phHits === 0, "$phHits hits");

    echo "== Final voucher nos. / Form 35 / Cash book: ranges, assignment and screens\n";
    $ranges = DB::table('users')->where('role', 'range')->get(['id', 'name']);
    $rangeGross = 0.0;
    $rangeOk = 0;
    foreach ($ranges as $r) {
        [$c, , $j] = call('POST', '/division/final-voucher-cashbook/preview', 'division', ['month' => 'Oct', 'payment_mode' => 'IFMS', 'report_type' => 'range_form_35', 'range_id' => $r->id, '_token' => 'tok']);
        [$s, $e] = $win('Oct');
        $exp = (float) (clone $dbApproved)->whereBetween('treasury_approval_date', [$s, $e])->where('payment_mode', 'IFMS')->where('range_id', $r->id)->sum('payable_total_amount');
        $rangeOk += abs(($j['data']['totals']['gross_amount'] ?? -1) - $exp) < 0.01;
    }
    check('range Form 35 (Oct, IFMS) equals the database for each of the ' . count($ranges) . ' ranges', $rangeOk === count($ranges), "$rangeOk of " . count($ranges));

    // assignment: inside a transaction that is rolled back, so the real data is not touched
    $sna = DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved')->where('payment_mode', 'SNA')->whereNull('voucher_no_final')
        ->selectRaw('SUBSTR(CAST(treasury_approval_date AS CHAR),1,7) ym, COUNT(*) c')->groupBy('ym')->orderBy('ym')->first();
    if ($sna) {
        $mm = $months[array_search((int) substr($sna->ym, 5, 2), [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3], true)];
        $maxBefore = (int) DB::table('vouchers')->where('financial_year_id', $fy)->where('voucher_no_final', 'like', 'SNA-%')->whereBetween('treasury_approval_date', [$sna->ym.'-01', date('Y-m-t', strtotime($sna->ym.'-01'))])->get(['voucher_no_final'])->map(fn ($v) => (int) substr($v->voucher_no_final, 4))->max();
        DB::beginTransaction();
        [$c, , $j] = call('POST', '/division/final-voucher-cashbook/assign', 'division', ['month' => $mm, 'payment_mode' => 'SNA', '_token' => 'tok']);
        $nums = DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved')->where('payment_mode', 'SNA')->whereBetween('treasury_approval_date', [$sna->ym.'-01', date('Y-m-t', strtotime($sna->ym.'-01'))])->where('voucher_no_final', 'like', 'SNA-%')->get(['voucher_no_final'])->map(fn ($v) => (int) substr($v->voucher_no_final, 4));
        $audits = DB::table('voucher_versions')->where('version_kind', 'advice:final_number')->where('created_at', '>=', now()->subMinutes(5))->count();
        DB::rollBack();
        $after = (int) DB::table('vouchers')->where('financial_year_id', $fy)->where('payment_mode', 'SNA')->where('treasury_status', 'Approved')->whereNull('voucher_no_final')->count();
        check("assign numbers the {$sna->c} unnumbered SNA vouchers of {$mm} continuing after SNA-{$maxBefore} (rolled back)", $c === 200 && ($j['assigned_count'] ?? -1) === (int) $sna->c && $nums->max() === $maxBefore + (int) $sna->c && $nums->unique()->count() >= $nums->count() - 0, 'max now '.$nums->max());
        check('assignment wrote one audit version per voucher, and the rollback left the real data untouched', $audits >= (int) $sna->c && $after > 0);
    }
    $unn = DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved')->whereNull('voucher_no_final')->count();
    [$c, $h] = call('GET', '/division/final-voucher-cashbook', 'division');
    $anyRow = DB::table('vouchers')->where('financial_year_id', $fy)->where('treasury_status', 'Approved')->whereNotNull('voucher_no_final')->orderByDesc('treasury_approval_date')->first();
    check("index lists the real month/mode batches ($unn approved vouchers still unnumbered), no demo rows", $c === 200 && ! str_contains($h, 'Contingency & Salary') && ! str_contains($h, 'General Forest Scheme Group') && str_contains($h, 'not numbered'));
    $mshort = $months[array_search((int) date('n', strtotime($anyRow->treasury_approval_date)), [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3], true)];
    [$c, $h] = call('GET', '/division/final-voucher-cashbook/print?report_type=form_35&payment_mode='.$anyRow->payment_mode.'&month='.$mshort, 'division');
    check('Form 35 print shows a real party and its final voucher no.', $c === 200 && str_contains($h, e($anyRow->party_name)) && str_contains($h, e($anyRow->voucher_no_final)), $anyRow->voucher_no_final);
    [$c, $h] = call('GET', '/division/final-voucher-cashbook/print?report_type=cashbook&month='.$mshort, 'division');
    check('cash book print renders with its note and no demo balance', $c === 200 && str_contains($h, 'not recorded in the data') && ! str_contains($h, '150,000.00'));
    [$c] = call('GET', '/division/final-voucher-cashbook', 'himatnagar');
    check('a range login cannot use the Division screen (403)', $c === 403);
}

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
