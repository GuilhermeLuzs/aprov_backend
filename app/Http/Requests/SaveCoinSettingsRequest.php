<?php

namespace App\Http\Requests;

use App\Enums\CoinRuleTopic;
use App\Enums\ItemKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCoinSettingsRequest extends FormRequest
{
    public const ICONS = [
        'coins', 'utensils', 'coffee', 'cpu', 'sparkles', 'snowflake', 'eye', 'paw-print', 'book-open',
        'hammer', 'heart-pulse', 'zap', 'graduation-cap', 'star', 'heart', 'leaf', 'gem', 'flame',
    ];

    public const THRESHOLD_TOPICS = [CoinRuleTopic::MinTags, CoinRuleTopic::MinValidPhotos, CoinRuleTopic::MinCommentLength];

    private const NAME_MAX = 20;

    private const VALUE_MAX = 1000;

    private const COINS_MAX = 1000;

    private const THRESHOLD_MAX = 2000;

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:'.self::NAME_MAX],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['required', Rule::in(self::ICONS)],
            'valueInReais' => ['required', 'numeric', 'gt:0', 'max:'.self::VALUE_MAX],
            'rewards' => ['required', 'array'],
        ];

        foreach (ItemKind::cases() as $kind) {
            foreach (CoinRuleTopic::forKind($kind) as $topic) {
                $key = "rewards.{$kind->value}.{$topic->value}";
                $rules["{$key}.coins"] = ['required', 'integer', 'min:0', 'max:'.self::COINS_MAX];
                $rules["{$key}.threshold"] = in_array($topic, self::THRESHOLD_TOPICS, true)
                    ? ["required_unless:{$key}.coins,0", 'nullable', 'integer', 'min:1', 'max:'.self::THRESHOLD_MAX]
                    : ['prohibited'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome para a moeda.',
            'name.max' => 'Use até '.self::NAME_MAX.' caracteres.',
            'color.*' => 'Use uma cor no formato #RRGGBB.',
            'icon.*' => 'Escolha um ícone da lista.',
            'valueInReais.required' => 'Informe quanto vale uma moeda.',
            'valueInReais.max' => 'Use até R$ 1.000,00.',
            'valueInReais.*' => 'O valor precisa ser maior que zero.',
            'rewards.*.*.coins.max' => 'Use até '.self::COINS_MAX.'.',
            'rewards.*.*.coins.*' => 'Use um número inteiro, 0 ou mais.',
            'rewards.*.*.threshold.*' => 'Informe a quantidade, 1 ou mais.',
            'rewards.*' => 'Preencha a tabela de moedas.',
        ];
    }
}
