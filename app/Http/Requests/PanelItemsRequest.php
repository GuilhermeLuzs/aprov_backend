<?php

namespace App\Http\Requests;

use App\Enums\Category;
use App\Enums\ItemKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PanelItemsRequest extends FormRequest
{
    public const PAGE_SIZES = [5, 10, 25, 50];

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', Rule::in(self::PAGE_SIZES)],
            'search' => ['nullable', 'string', 'max:100'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['distinct', Rule::enum(Category::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'kind' => ['nullable', Rule::enum(ItemKind::class)],
            'sort' => ['nullable', Rule::in(['title', 'average', 'count'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    public function messages(): array
    {
        return [
            'search.max' => 'A busca pode ter até 100 caracteres.',
            '*' => 'Filtro inválido.',
        ];
    }
}
