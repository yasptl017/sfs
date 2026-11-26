<?php

namespace App\Support;

use App\Models\AllotmentAdjustment;
use App\Models\AllotmentFromCircle;
use App\Models\AllotmentToRange;
use Illuminate\Support\Facades\DB;

/**
 * Allotment position of one budget code (in the signed-in financial year), using the rules proven against the sheets:
 *   circle     = LATEST cumulative restatement from Circle (by its own time stamp, not creation time)
 *   adjusted   = circle + adjustments received - adjustments given
 *   distributed= SUM of increments given to ranges
 *   pending    = adjusted - distributed
 */
class BudgetPosition
{
    public static function latestCircle(int $divisionId, int $budgetCodeId): ?AllotmentFromCircle
    {
        return AllotmentFromCircle::where('division_id', $divisionId)->where('budget_code_id', $budgetCodeId)
            ->orderByDesc('source_time_stamp')->orderByDesc('id')->first();
    }

    /** @return array{circle: float, in: float, out: float, adjusted: float, distributed: float, pending: float, circle_entry: ?AllotmentFromCircle} */
    public static function for(int $divisionId, int $budgetCodeId, ?int $excludeAdjustmentId = null): array
    {
        $circleEntry = self::latestCircle($divisionId, $budgetCodeId);
        $adj = AllotmentAdjustment::where('division_id', $divisionId)->when($excludeAdjustmentId, fn ($q) => $q->whereKeyNot($excludeAdjustmentId));
        $in = (float) (clone $adj)->where('to_budget_code_id', $budgetCodeId)->sum('amount');
        $out = (float) (clone $adj)->where('from_budget_code_id', $budgetCodeId)->sum('amount');
        $distributed = (float) AllotmentToRange::where('division_id', $divisionId)->where('budget_code_id', $budgetCodeId)->sum('amount');
        $net = (float) DB::table('allotment_net_adjustments')->where('financial_year_id', CurrentFinancialYear::id())->where('budget_code_id', $budgetCodeId)->sum('amount');   // 2025-26: only the net per code is known
        $in += max($net, 0);
        $out += max(-$net, 0);
        $circle = (float) ($circleEntry?->amount ?? 0);
        $adjusted = $circle + $in - $out;

        return ['circle' => $circle, 'in' => $in, 'out' => $out, 'adjusted' => $adjusted, 'distributed' => $distributed, 'pending' => $adjusted - $distributed, 'circle_entry' => $circleEntry];
    }
}
