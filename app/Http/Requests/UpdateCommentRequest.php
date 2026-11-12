<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.StoreCommentRequest::BODY_MAX_LENGTH],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'Escreva o comentário.',
            'body.max' => 'O comentário pode ter até 500 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['body' => trim((string) $this->input('body'))]);
    }
}
