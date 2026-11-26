<?php

namespace App\Support;

/**
 * Voucher amount rules (same as the entry form's live totals; reproduces the stored amounts of the migrated
 * vouchers: approved amount 100%, total deduction 100%, net 100%, payable 99.99% of 7,572 vouchers).
 *   item amount     = the typed amount; quantity x rate only when no amount is given
 *   sub total       = sum of item amounts
 *   approved amount = sub total x approved % (100 when blank)
 *   payable         = approved amount + SGST + CGST + IGST + labour cess + adjustment
 *   total deduction = deduction SGST/CGST/IGST/labour cess + GPF + CPF + P.Tax + group insurance + deposit + TDS + other
 *   net amount      = payable - total deduction
 */
class VoucherCalculator
{
    public const ADDITIONS = ['sgst', 'cgst', 'igst', 'labour_cess', 'adjustment'];

    public const DEDUCTIONS = ['deduction_sgst', 'deduction_cgst', 'deduction_igst', 'deduction_labour_cess', 'gpf', 'cpf', 'p_tax', 'group_insurance', 'deposit_deduction_amount', 'tds', 'other_deductions'];

    /** A typed amount is authoritative (e.g. survival-based payments pay 1,000 plants x 4 although the line shows 670 surviving plants); quantity x rate is the fallback. */
    public static function itemAmount(array $item): float
    {
        $amount = self::n($item['amount'] ?? null);
        if ($amount !== null) {
            return $amount;
        }
        $qty = self::n($item['quantity'] ?? null);
        $rate = self::n($item['rate'] ?? null);

        return ($qty !== null && $rate !== null) ? round($qty * $rate, 6) : 0.0;
    }

    /** @param array<string, mixed> $v voucher inputs (column => value), @param array<int, array<string, mixed>> $items */
    public static function totals(array $v, array $items): array
    {
        $sub = array_sum(array_map([self::class, 'itemAmount'], $items));
        $pct = self::n($v['approved_percent'] ?? null);
        $approved = $sub * (($pct ?? 100.0) / 100.0);
        $payable = $approved + array_sum(array_map(fn ($k) => self::n($v[$k] ?? null) ?? 0.0, self::ADDITIONS));
        $ded = array_sum(array_map(fn ($k) => self::n($v[$k] ?? null) ?? 0.0, self::DEDUCTIONS));

        return [
            'sub_total' => round($sub, 2),
            'amount_as_per_approved_rate' => round($approved, 2),
            'payable_total_amount' => round($payable, 2),
            'total_deduction' => round($ded, 2),
            'net_amount' => round($payable - $ded, 2),
        ];
    }

    /** Stable fingerprint of every input that feeds the totals (to detect "nothing relevant changed"). */
    public static function signature(array $v, array $items): string
    {
        $parts = [round(self::n($v['approved_percent'] ?? null) ?? -1, 2)];
        foreach (array_merge(self::ADDITIONS, self::DEDUCTIONS) as $k) {
            $parts[] = round(self::n($v[$k] ?? null) ?? 0.0, 2);
        }
        foreach ($items as $it) {
            $parts[] = [round(self::n($it['quantity'] ?? null) ?? 0.0, 4), round(self::n($it['rate'] ?? null) ?? 0.0, 4), round(self::itemAmount($it), 2)];
        }

        return md5(json_encode($parts));
    }

    private static function n(mixed $value): ?float
    {
        return $value === null || $value === '' || ! is_numeric($value) ? null : (float) $value;
    }
}
