<?php
/**
 * In-process GUI smoke test: logs in as an office user and requests every parameter-less GET page.
 *   php database/tools/smoke_gui.php [username ...]      (SMOKE_FY=2025-26 php ... runs it on another financial year)
 * Prints HTTP status per page and the first line of any exception. No web server needed.
 */
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$users = array_slice($argv, 1) ?: ['division', 'himatnagar', 'admin'];
$routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($r) => in_array('GET', $r->methods()) && ! str_contains($r->uri(), '{') && $r->getName() && ! in_array($r->getName(), ['login', 'logout']))->values();
$summary = [];
$fyCode = getenv('SMOKE_FY');
if ($fyCode) {
    $app['session.store']->put('financial_year_id', Illuminate\Support\Facades\DB::table('financial_years')->where('code', $fyCode)->value('id'));
}
foreach ($users as $username) {
    $user = App\Models\User::where('username', $username)->firstOrFail();
    $ok = $bad = 0; $fails = [];
    foreach ($routes as $route) {
        $app['auth']->guard('web')->setUser($user);
        App\Support\CurrentFinancialYear::forget();
        $request = Request::create('/' . ltrim($route->uri(), '/'), 'GET');
        $request->setLaravelSession($app['session.store']);
        try {
            $response = $kernel->handle($request);
            $code = $response->getStatusCode();
            $err = '';
            if ($code >= 500 && isset($response->exception)) { $err = strtok($response->exception->getMessage(), "\n"); }
        } catch (Throwable $e) { $code = 500; $err = strtok($e->getMessage(), "\n"); }
        if ($code < 400 || $code === 302) { $ok++; } else { $bad++; $fails[] = sprintf('%-48s %d  %s', $route->uri(), $code, mb_substr($err, 0, 130)); }
    }
    $summary[$username] = [$ok, $bad];
    echo "== $username (" . $user->role . "): ok=$ok failing=$bad of " . count($routes) . "\n";
    foreach ($fails as $f) echo "   $f\n";
}
