<?php

namespace App\Http\Requests;

use App\Rules\Cpf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterCustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => strtolower(trim((string) $this->input('username'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._]+$/', Rule::unique('customers', 'username')],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'birthDate' => ['required', 'date_format:Y-m-d', 'before:today', 'after_or_equal:1900-01-01'],
            'cpf' => ['required', 'digits:11', new Cpf, Rule::unique('customers', 'cpf')],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-zÀ-ÿ]/', 'regex:/\d/'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'username.min' => 'Use de 3 a 30 letras, números, ponto ou sublinhado.',
            'username.max' => 'Use de 3 a 30 letras, números, ponto ou sublinhado.',
            'username.regex' => 'Use de 3 a 30 letras, números, ponto ou sublinhado.',
            'username.unique' => 'Este nome de usuário já está em uso.',
            'email.email' => 'E-mail inválido.',
            'email.unique' => 'Já existe uma conta com este e-mail.',
            'birthDate.date_format' => 'Data inválida. Use o formato aaaa-mm-dd.',
            'birthDate.before' => 'A data de nascimento precisa ser no passado.',
            'birthDate.after_or_equal' => 'Data inválida.',
            'cpf.digits' => 'Informe um CPF válido.',
            'cpf.unique' => 'Já existe uma conta com este CPF.',
            'password.min' => 'A senha precisa ter 8 caracteres ou mais, com letra e número.',
            'password.regex' => 'A senha precisa ter 8 caracteres ou mais, com letra e número.',
        ];
    }
}
