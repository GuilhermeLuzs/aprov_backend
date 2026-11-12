<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public const BODY_MAX_LENGTH = 500;

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.self::BODY_MAX_LENGTH],
            'parentId' => ['nullable', 'integer'],
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
