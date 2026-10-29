<?php

namespace Tests\Unit;

use App\Rules\Cpf;
use PHPUnit\Framework\TestCase;

class CpfRuleTest extends TestCase
{
    private function errorFor(string $cpf): ?string
    {
        $error = null;
        (new Cpf())->validate('cpf', $cpf, function (string $message) use (&$error) {
            $error = $message;
        });

        return $error;
    }

    public function test_valid_cpf_is_accepted(): void
    {
        $this->assertNull($this->errorFor('529.982.247-25'));
    }

    public function test_cpf_with_wrong_check_digit_is_rejected(): void
    {
        $this->assertSame('Informe um CPF válido.', $this->errorFor('529.982.247-24'));
    }

    public function test_cpf_with_repeated_digits_is_rejected(): void
    {
        $this->assertSame('Informe um CPF válido.', $this->errorFor('111.111.111-11'));
    }
}
