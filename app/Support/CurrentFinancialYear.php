<?php

namespace App\Support;

use App\Models\FinancialYear;

/**
 * The financial year the signed-in user is working in (session), defaulting to the current one.
 * Cached per request in the application container (a new application = a new cache, so tests and queue workers never see a stale year).
 */
class CurrentFinancialYear
{
    private const KEY = 'financial_year.current';

    public static function model(): FinancialYear
    {
        $sessionId = self::sessionId();
        $app = app();
        if ($app->bound(self::KEY)) {
            $cached = $app->make(self::KEY);
            if (! $sessionId || $cached->id === $sessionId) {
                return $cached;
            }
        }

        $year = $sessionId ? FinancialYear::find($sessionId) : null;
        $year ??= FinancialYear::where('is_current', true)->first() ?? FinancialYear::orderByDesc('code')->first() ?? self::createForToday();
        $app->instance(self::KEY, $year);

        return $year;
    }

    public static function id(): int
    {
        return self::model()->id;
    }

    public static function forget(): void
    {
        app()->forgetInstance(self::KEY);
    }

    /** A fresh database has no financial year yet: create the one (April - March) that today falls in. */
    private static function createForToday(): FinancialYear
    {
        $today = now();
        $start = (int) $today->format('n') >= 4 ? (int) $today->format('Y') : (int) $today->format('Y') - 1;

        return FinancialYear::firstOrCreate(
            ['code' => sprintf('%d-%02d', $start, ($start + 1) % 100)],
            ['starts_on' => "$start-04-01", 'ends_on' => ($start + 1).'-03-31', 'is_current' => true]
        );
    }

    private static function sessionId(): ?int
    {
        try {
            $id = session('financial_year_id');
        } catch (\Throwable) {
            return null; // console / no session store
        }

        return $id ? (int) $id : null;
    }
}
