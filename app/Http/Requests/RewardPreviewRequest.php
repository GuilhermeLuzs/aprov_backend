<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RewardPreviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'draft' => ['required', 'array'],
            'draft.completed' => ['required', 'boolean'],
            'draft.tagCount' => ['required', 'integer', 'min:0'],
            'draft.hasVideo' => ['required', 'boolean'],
            'draft.photoCount' => ['required', 'integer', 'min:0'],
            'draft.commentLength' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
        ];
    }
}
