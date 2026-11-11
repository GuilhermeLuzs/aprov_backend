<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateReviewRequest extends FormRequest
{
    private const MAX_MEDIA = 6;

    public function rules(): array
    {
        return [
            'deliveryRating' => ['required', 'integer', 'between:1,5'],
            'qualityRating' => ['required', 'integer', 'between:1,5'],
            'conformityRating' => ['required', 'integer', 'between:1,5'],
            'valueRating' => ['required', 'integer', 'between:1,5'],
            'tagIds' => ['required', 'array', 'min:1'],
            'tagIds.*' => ['integer', 'distinct', 'exists:tags,id'],
            'comment' => ['required', 'string', 'max:1000'],
            'wouldBuyAgain' => ['required', 'boolean'],
            'keepMediaIds' => ['nullable', 'array'],
            'keepMediaIds.*' => ['integer', 'distinct'],
            'media' => ['nullable', 'array', 'max:'.self::MAX_MEDIA],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,webm', 'max:51200'],
            'mediaConsent' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $total = count($this->input('keepMediaIds', [])) + count($this->file('media', []));
                if ($total > self::MAX_MEDIA) {
                    $validator->errors()->add('media', 'No máximo 6 arquivos.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'deliveryRating.between' => 'A nota precisa ser de 1 a 5.',
            'qualityRating.between' => 'A nota precisa ser de 1 a 5.',
            'conformityRating.between' => 'A nota precisa ser de 1 a 5.',
            'valueRating.between' => 'A nota precisa ser de 1 a 5.',
            'tagIds.min' => 'Marque pelo menos uma tag.',
            'tagIds.*.exists' => 'Tag não encontrada.',
            'comment.max' => 'O comentário pode ter até 1000 caracteres.',
            'media.max' => 'No máximo 6 arquivos.',
            'media.*.mimes' => 'Envie foto (JPG, PNG ou WebP) ou vídeo (MP4 ou WebM).',
            'media.*.max' => 'Cada arquivo pode ter até 50 MB.',
        ];
    }
}
