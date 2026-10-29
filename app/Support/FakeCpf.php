<?php

namespace App\Support;

class FakeCpf
{
    public static function fromSequence(int $sequence): string
    {
        $base = str_pad((string) $sequence, 9, '0', STR_PAD_LEFT);
        $digits = array_map('intval', str_split($base));

        $digits[] = self::checkDigit($digits);
        $digits[] = self::checkDigit($digits);

        return implode('', $digits);
    }

    private static function checkDigit(array $digits): int
    {
        $weight = count($digits) + 1;
        $sum = 0;
        foreach ($digits as $digit) {
            $sum += $digit * $weight--;
        }
        $rest = $sum % 11;

        return $rest < 2 ? 0 : 11 - $rest;
    }
}
