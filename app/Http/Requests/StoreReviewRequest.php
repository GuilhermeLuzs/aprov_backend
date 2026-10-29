<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'itemId' => ['required', 'integer', 'exists:items,id'],
            'deliveryRating' => ['required', 'integer', 'between:1,5'],
            'qualityRating' => ['required', 'integer', 'between:1,5'],
            'conformityRating' => ['required', 'integer', 'between:1,5'],
            'valueRating' => ['required', 'integer', 'between:1,5'],
            'tagIds' => ['required', 'array', 'min:1'],
            'tagIds.*' => ['integer', 'distinct', 'exists:tags,id'],
            'comment' => ['required', 'string', 'max:1000'],
            'wouldBuyAgain' => ['required', 'boolean'],
            'media' => ['nullable', 'array', 'max:6'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,webm', 'max:51200'],
            'mediaConsent' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'itemId.exists' => 'Item não encontrado.',
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
