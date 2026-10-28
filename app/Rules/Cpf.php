<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cpf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if (strlen($digits) !== 11 || preg_match('/^(\d)\1{10}$/', $digits)) {
            $fail('Informe um CPF válido.');

            return;
        }

        foreach ([9, 10] as $position) {
            if ((int) $digits[$position] !== $this->checkDigit(substr($digits, 0, $position))) {
                $fail('Informe um CPF válido.');

                return;
            }
        }
    }

    private function checkDigit(string $digits): int
    {
        $weight = strlen($digits) + 1;
        $sum = 0;

        foreach (str_split($digits) as $digit) {
            $sum += (int) $digit * $weight--;
        }

        $rest = $sum % 11;

        return $rest < 2 ? 0 : 11 - $rest;
    }
}
