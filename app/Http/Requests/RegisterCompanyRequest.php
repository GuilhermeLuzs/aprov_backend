<?php

namespace App\Http\Requests;

use App\Enums\Category;
use App\Enums\CompanyModality;
use App\Enums\CompanySize;
use App\Rules\Cnpj;
use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterCompanyRequest extends FormRequest
{
    private const STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    protected function prepareForValidation(): void
    {
        $digits = fn (string $field) => preg_replace('/\D/', '', (string) $this->input($field));
        $text = fn (string $field) => trim((string) $this->input($field));

        $this->merge([
            'cnpj' => $digits('cnpj'),
            'name' => $text('name'),
            'email' => strtolower($text('email')),
            'phone' => $digits('phone'),
            'zipCode' => $digits('zipCode'),
            'street' => $text('street'),
            'number' => $text('number'),
            'complement' => $text('complement') ?: null,
            'district' => $text('district'),
            'city' => $text('city'),
            'state' => strtoupper($text('state')),
            'responsibleName' => $text('responsibleName'),
            'responsibleCpf' => $digits('responsibleCpf'),
            'responsibleEmail' => strtolower($text('responsibleEmail')),
            'responsiblePhone' => $digits('responsiblePhone'),
        ]);
    }

    public function rules(): array
    {
        return [
            'cnpj' => ['required', new Cnpj, Rule::unique('companies', 'cnpj')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'digits_between:10,11'],
            'zipCode' => ['required', 'digits:8'],
            'street' => ['required', 'string', 'max:255'],
            'number' => ['required', 'string', 'max:10'],
            'complement' => ['nullable', 'string', 'max:255'],
            'district' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', Rule::in(self::STATES)],
            'niche' => ['required', Rule::enum(Category::class)],
            'modality' => ['required', Rule::enum(CompanyModality::class)],
            'size' => ['required', Rule::enum(CompanySize::class)],
            'responsibleName' => ['required', 'string', 'max:255'],
            'responsibleCpf' => ['required', new Cpf],
            'responsibleEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'responsiblePhone' => ['required', 'digits_between:10,11'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-zÀ-ÿ]/', 'regex:/\d/'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'cnpj.unique' => 'Já existe cadastro para este CNPJ.',
            'email.email' => 'Informe um e-mail válido.',
            'responsibleEmail.email' => 'Informe um e-mail válido.',
            'responsibleEmail.unique' => 'Este e-mail já tem uma conta na APROV.',
            'phone.digits_between' => 'Informe um telefone com DDD.',
            'responsiblePhone.digits_between' => 'Informe um telefone com DDD.',
            'zipCode.digits' => 'Informe um CEP com 8 números.',
            'state.in' => 'Escolha uma UF válida.',
            'niche' => 'Escolha o nicho.',
            'modality' => 'Escolha a modalidade.',
            'size' => 'Escolha o porte.',
            'password.min' => 'A senha precisa ter 8 caracteres ou mais, com letra e número.',
            'password.regex' => 'A senha precisa ter 8 caracteres ou mais, com letra e número.',
        ];
    }
}
