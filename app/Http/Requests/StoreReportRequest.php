<?php

namespace App\Http\Requests;

use App\Enums\ReportReason;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'targetType' => ['required', Rule::in([Report::TARGET_REVIEW, Report::TARGET_COMMENT])],
            'targetId' => ['required', 'integer'],
            'reason' => ['required', Rule::enum(ReportReason::class)],
            'description' => [
                'nullable',
                'string',
                'max:500',
                Rule::requiredIf(fn () => $this->input('reason') === ReportReason::Other->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'targetType.in' => 'Conteúdo inválido.',
            'reason.enum' => 'Motivo inválido.',
            'description.required' => 'Conte o motivo da denúncia.',
            'description.max' => 'A descrição pode ter até 500 caracteres.',
        ];
    }
}
