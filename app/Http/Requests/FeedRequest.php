<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeedRequest extends FormRequest
{
    public const TAB_FOR_YOU = 'for_you';

    public const TAB_FOLLOWING = 'following';

    public function rules(): array
    {
        return [
            'tab' => ['nullable', 'in:'.self::TAB_FOR_YOU.','.self::TAB_FOLLOWING],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'tab.in' => 'Aba inválida.',
            'page.integer' => 'Página inválida.',
            'page.min' => 'Página inválida.',
        ];
    }

    public function followingTab(): bool
    {
        return $this->validated('tab') === self::TAB_FOLLOWING;
    }
}
