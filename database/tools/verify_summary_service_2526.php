<?php
/** FY 2025-26: proves ExpenditureReport against the spreadsheet's own Summary (Oct, per budget code) and LC SHEET (Mar, per scheme x class). */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Support\ExpenditureReport;
use Illuminate\Support\Facades\DB;

$pass = $fail = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    printf("  %s %s%s\n", $ok ? 'OK  ' : 'FAIL', $name, $detail ? "  [$detail]" : '');
}
function staged(string $sheet): array
{
    $sid = DB::table('staging_sheets')->where('source_file', 'Division Finance System/Master00 SF Sabarkantha 25-26 DFS.xlsx')->where('sheet_name', $sheet)->value('id');

    return DB::table('staging_rows')->where('staging_sheet_id', $sid)->orderBy('row_no')->get(['row_no', 'cells'])->map(fn ($r) => [$r->row_no, array_pad(json_decode($r->cells, true), 30, null)])->all();
}
$fy = DB::table('financial_years')->where('code', '2025-26')->value('id');

echo "== Summary sheet (October), per budget code\n";
$sheet = [];
foreach (staged('Summary') as [$r, $c]) {
    if ($r >= 6 && $c[0]) {
        $sheet[$c[0]] = [(float) ($c[6] ?? 0), (float) ($c[7] ?? 0), (float) ($c[8] ?? 0)];
    }
}
$mine = [];
foreach (ExpenditureReport::codeRows($fy, 'Oct') as $r) {
    $mine[$r->budget_code] = [$r->allotment, $r->p_exp, $r->c_exp];
}
foreach (['allotment' => 0, 'expenditure up to September (P_Exp)' => 1, 'expenditure of October (C_Exp)' => 2] as $lab => $i) {
    $eq = 0;
    foreach ($sheet as $k => $v) {
        $eq += abs(($mine[$k][$i] ?? 0) - $v[$i]) < 1;
    }
    check("$lab == sheet for all ".count($sheet).' codes', $eq === count($sheet), "$eq equal; total ".number_format(array_sum(array_column($mine, $i))).' vs '.number_format(array_sum(array_column($sheet, $i))));
}
check('code order equals the sheet order', array_keys($sheet) === array_map(fn ($r) => $r->budget_code, ExpenditureReport::codeRows($fy, 'Oct')));

echo "== LC SHEET (March), per scheme x object class\n";
$lc = [];
foreach (staged('LC SHEET') as [$r, $c]) {
    if ($r >= 6 && is_string($c[1]) && is_string($c[2]) && is_numeric($c[0])) {
        $lc[$c[1].'|'.$c[2]] = ['allot' => (float) ($c[3] ?? 0), 'p' => (float) ($c[4] ?? 0), 'c' => (float) ($c[5] ?? 0), 'lc' => (float) ($c[17] ?? 0)];
    }
}
$agg = [];
foreach (ExpenditureReport::codeRows($fy, 'Mar') as $r) {
    $k = $r->scheme.'|'.$r->object_class;
    $agg[$k] ??= ['allot' => 0, 'p' => 0, 'c' => 0];
    $agg[$k]['allot'] += $r->allotment;
    $agg[$k]['p'] += $r->p_exp;
    $agg[$k]['c'] += $r->c_exp;
}
$rel = ExpenditureReport::released($fy, 'Mar');
$res = ['allot' => 0, 'p' => 0, 'c' => 0, 'lc' => 0];
$bad = [];
foreach ($lc as $k => $v) {
    [$scheme, $class] = explode('|', $k);
    $m = ['allot' => $agg[$k]['allot'] ?? 0, 'p' => $agg[$k]['p'] ?? 0, 'c' => $agg[$k]['c'] ?? 0, 'lc' => $rel[$scheme][$class] ?? 0];
    foreach ($res as $f => $_) {
        if (abs($m[$f] - $v[$f]) < 1) {
            $res[$f]++;
        } elseif (count($bad) < 4) {
            $bad[] = "$k $f mine {$m[$f]} sheet {$v[$f]}";
        }
    }
}
foreach (['allot' => 'allotment', 'p' => 'expenditure up to February', 'c' => 'expenditure of March'] as $f => $lab) {
    check("$lab == sheet in all ".count($lc).' scheme x class rows', $res[$f] === count($lc), $res[$f].' equal');
}
// KNOWN source typo (open point OP-03): L.C. Sr 53 (02- Divisional / Class-2, 120,000) is typed 2026-10-13, entered 2025-10-15; the sheet counts it as of March 2026.
$lcDiff = [];
foreach ($lc as $k => $v) {
    [$scheme, $class] = explode('|', $k);
    $d = ($v['lc']) - ($rel[$scheme][$class] ?? 0);
    if (abs($d) >= 1) {
        $lcDiff[$k] = $d;
    }
}
check('L.C. issued (Released) == sheet in all '.count($lc).' scheme x class rows, except the one explained by the mistyped date of L.C. Sr 53 (+120,000)', $lcDiff === ['02- Divisional|Class-2' => 120000.0] || $lcDiff === [], json_encode($lcDiff));
check('L.C. issued total = 497,378,059 (sheet header) once the mistyped date of L.C. Sr 53 is read as 2025', abs(array_sum(array_map('array_sum', $rel)) + 120000 - 497378059) < 1);
check('no scheme x class of mine is missing from the LC SHEET', count(array_diff_key($agg, $lc)) === 0 || array_sum(array_map(fn ($a) => abs($a['allot']) + abs($a['p']) + abs($a['c']), array_diff_key($agg, $lc))) < 1, implode(',', array_keys(array_diff_key($agg, $lc))));
echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail ? 1 : 0);
