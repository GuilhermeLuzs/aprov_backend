<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    private const FIRST_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    private const SECOND_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) !== 14 || preg_match('/^(\d)\1{13}$/', $digits)) {
            $fail('CNPJ inválido. Confira os números.');

            return;
        }

        $numbers = array_map('intval', str_split($digits));

        if ($numbers[12] !== $this->checkDigit($numbers, self::FIRST_WEIGHTS)
            || $numbers[13] !== $this->checkDigit($numbers, self::SECOND_WEIGHTS)) {
            $fail('CNPJ inválido. Confira os números.');
        }
    }

    private function checkDigit(array $digits, array $weights): int
    {
        $sum = 0;
        foreach ($weights as $index => $weight) {
            $sum += $digits[$index] * $weight;
        }
        $rest = $sum % 11;

        return $rest < 2 ? 0 : 11 - $rest;
    }
}
