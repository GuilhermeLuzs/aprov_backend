<?php

namespace App\Http\Requests;

use App\Enums\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePreferencesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'categories' => ['required', 'array', 'min:3'],
            'categories.*' => ['string', 'distinct', Rule::enum(Category::class)],
            'companyIds' => ['required', 'array', 'min:2'],
            'companyIds.*' => ['string', 'distinct'],
            'discoverySource' => ['required', Rule::enum(\App\Enums\DiscoverySource::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'categories.min' => 'Escolha pelo menos 3 categorias.',
            'categories.*' => 'Categoria inválida.',
            'companyIds.min' => 'Escolha pelo menos 2 empresas.',
            'discoverySource' => 'Diga como você conheceu a APROV.',
        ];
    }
}
