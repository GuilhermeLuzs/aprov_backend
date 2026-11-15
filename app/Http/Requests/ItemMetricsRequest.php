<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ItemMetricsRequest extends FormRequest
{
    private const MAX_DAYS = 366;

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.after_or_equal' => 'A data final precisa ser igual ou depois da inicial.',
            '*' => 'Período inválido.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isEmpty() && $this->start()->diffInDays($this->end()) > self::MAX_DAYS) {
                    $validator->errors()->add('to', 'Escolha um período de até um ano.');
                }
            },
        ];
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->input('from'), config('aprov.panel_timezone'))->startOfDay();
    }

    public function end(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d', $this->input('to'), config('aprov.panel_timezone'))->endOfDay();
    }
}
