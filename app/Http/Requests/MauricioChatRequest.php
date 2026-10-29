<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MauricioChatRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'messages' => ['required', 'array', 'min:1', 'max:20'],
            'messages.*.author' => ['required', 'in:user,mauricio'],
            'messages.*.text' => ['required', 'string', 'max:2000'],
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
            'messages.max' => 'Conversa muito longa.',
        ];
    }
}
