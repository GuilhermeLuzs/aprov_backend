<?php

namespace App\Http\Requests;

use App\Enums\ItemKind;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'itemId' => ['required', 'integer', fn (string $attribute, mixed $value, \Closure $fail) => Item::query()->onSale()->whereKey((int) $value)->exists() || $fail('Item não encontrado.')],
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

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $item = Item::query()->find((int) $this->input('itemId'));
                if ($item?->kind === ItemKind::Product && count($this->file('media', [])) === 0) {
                    $validator->errors()->add('media', 'Avaliação de produto precisa de pelo menos uma foto ou vídeo.');
                }
            },
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
