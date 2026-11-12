<?php

namespace App\Http\Requests;

use App\Enums\Category;
use App\Enums\ItemKind;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class CompanyItemsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:100'],
            'kinds' => ['nullable', 'string', $this->listRule(array_column(ItemKind::cases(), 'value'))],
            'categories' => ['nullable', 'string', $this->listRule(array_column(Category::cases(), 'value'))],
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'Página inválida.',
            'page.min' => 'Página inválida.',
            'search.max' => 'A busca pode ter até 100 caracteres.',
        ];
    }

    public function search(): string
    {
        return trim((string) $this->validated('search', ''));
    }

    public function listOf(string $field): array
    {
        $value = $this->validated($field);

        return $value ? explode(',', $value) : [];
    }

    private function listRule(array $allowed): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($allowed) {
            if (array_diff(explode(',', (string) $value), $allowed) !== []) {
                $fail('Filtro inválido.');
            }
        };
    }
}
