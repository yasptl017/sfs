<?php
/**
 * Timing of the heaviest pages / report endpoints (in-process, no web server): wall time and number of SQL queries.
 *   php database/tools/perf_pages.php [2025-26|2026-27]
 */
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$fyCode = $argv[1] ?? '2026-27';
$fy = DB::table('financial_years')->where('code', $fyCode)->value('id');
$app['session.store']->put('financial_year_id', $fy);
$v = DB::table('vouchers')->where('financial_year_id', $fy)->where('range_id', DB::table('users')->where('username', 'himatnagar')->value('id'))->value('id');

$pages = [
    ['division', 'GET', '/dashboard', []],
    ['division', 'GET', '/vouchers', []],
    ['division', 'GET', '/vouchers?q=a', []],
    ['division', 'GET', "/vouchers/$v", []],
    ['division', 'GET', '/reports/position', []],
    ['division', 'GET', '/registers/budget-position', []],
    ['division', 'GET', '/registers/sf-beneficiaries', []],
    ['division', 'GET', '/division/advices', []],
    ['division', 'GET', '/division/advices/pending', []],
    ['division', 'GET', '/division/summary-reports', []],
    ['division', 'POST', '/division/summary-reports/preview', ['month' => 'Mar', 'payment_mode' => 'ALL', 'report_type' => 'summary_merged_salary']],
    ['division', 'GET', '/division/monthly-reports', []],
    ['division', 'POST', '/division/monthly-reports/preview', ['month' => 'Mar', 'payment_mode' => 'IFMS', 'report_type' => 'form_53_new']],
    ['division', 'GET', '/division/final-voucher-cashbook', []],
    ['division', 'POST', '/division/final-voucher-cashbook/preview', ['month' => 'Mar', 'report_type' => 'cashbook']],
    ['himatnagar', 'GET', '/vouchers', []],
    ['himatnagar', 'GET', '/finance/voucher-print', []],
    ['himatnagar', 'GET', '/finance/abstract-print', []],
    ['himatnagar', 'GET', '/finance/work-order-print', []],
    ['himatnagar', 'GET', '/finance/vavetar-register', []],
    ['himatnagar', 'GET', '/finance/sor-limit-report', []],
];
printf("%-11s %-5s %-52s %6s %8s %7s\n", 'user', 'verb', 'page', 'code', 'ms', 'queries');
$total = 0;
foreach ($pages as [$user, $method, $uri, $params]) {
    $u = App\Models\User::where('username', $user)->firstOrFail();
    $app['auth']->guard('web')->setUser($u);
    App\Support\CurrentFinancialYear::forget();
    $params['_token'] = 'tok';
    $req = Request::create($uri, $method, $params);
    $req->setLaravelSession($app['session.store']);
    $req->headers->set('X-CSRF-TOKEN', 'tok');
    $app['session.store']->put('_token', 'tok');
    if ($method === 'POST') {
        $req->headers->set('Accept', 'application/json');
    }
    DB::flushQueryLog();
    DB::enableQueryLog();
    $t = microtime(true);
    try {
        $code = $kernel->handle($req)->getStatusCode();
    } catch (Throwable $e) {
        $code = 'EXC';
    }
    $ms = (microtime(true) - $t) * 1000;
    $total += $ms;
    $ql = DB::getQueryLog();
    printf("%-11s %-5s %-52s %6s %8.0f %7d  sql %5.0f ms\n", $user, $method, substr($uri, 0, 52), $code, $ms, count($ql), array_sum(array_column($ql, 'time')));
    if (getenv('PERF_DETAIL')) {
        foreach ($ql as $q) {
            if ($q['time'] > 30) {
                printf("      %6.0f ms  %s\n", $q['time'], substr(preg_replace('/\s+/', ' ', $q['query']), 0, 230));
            }
        }
    }
    DB::disableQueryLog();
}
printf("TOTAL %.0f ms\n", $total);
