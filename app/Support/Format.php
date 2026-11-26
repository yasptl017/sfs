<?php

namespace App\Support;

class Format
{
    /** Indian digit grouping (12,34,567.50); null/'' -> '-' */
    public static function inr(mixed $value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '-';
        }
        $n = round((float) $value, $decimals);
        $neg = $n < 0;
        [$int, $dec] = array_pad(explode('.', number_format(abs($n), $decimals, '.', '')), 2, '');
        $last3 = substr($int, -3);
        $rest = substr($int, 0, -3);
        if ($rest !== '') {
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $last3 = $rest.','.$last3;
        }

        return ($neg ? '-' : '').$last3.($decimals ? '.'.$dec : '');
    }
}
