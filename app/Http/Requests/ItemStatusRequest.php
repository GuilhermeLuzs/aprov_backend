<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return ['active' => ['required', 'boolean']];
    }

    public function messages(): array
    {
        return ['active.*' => 'Status inválido.'];
    }
}
