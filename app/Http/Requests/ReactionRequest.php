<?php

namespace App\Http\Requests;

use App\Enums\ReactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ReactionType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Escolha concordar ou discordar.',
            'type.enum' => 'Escolha concordar ou discordar.',
        ];
    }
}
