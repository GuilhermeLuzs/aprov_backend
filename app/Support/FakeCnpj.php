<?php

namespace App\Support;

class FakeCnpj
{
    public static function fromSequence(int $sequence): string
    {
        $base = str_pad((string) $sequence, 8, '0', STR_PAD_LEFT).'0001';
        $digits = array_map('intval', str_split($base));

        $digits[] = self::checkDigit($digits, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
        $digits[] = self::checkDigit($digits, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);

        return implode('', $digits);
    }

    private static function checkDigit(array $digits, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $index => $weight) {
            $sum += $digits[$index] * $weight;
        }
        $rest = $sum % 11;

        return $rest < 2 ? 0 : 11 - $rest;
    }
}
