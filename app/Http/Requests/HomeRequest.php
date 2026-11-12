<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HomeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'companies' => ['nullable', 'string', 'regex:/^\d+(,\d+)*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'Página inválida.',
            'page.min' => 'Página inválida.',
            'search.max' => 'A busca pode ter até 100 caracteres.',
            'companies.regex' => 'Empresa inválida.',
        ];
    }

    public function search(): string
    {
        return trim((string) $this->validated('search', ''));
    }

    public function companyIds(): array
    {
        $companies = $this->validated('companies');

        return $companies ? array_map('intval', explode(',', $companies)) : [];
    }
}
