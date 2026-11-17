<?php

namespace App\Http\Requests;

class UpdateCompanyRequest extends RegisterCompanyRequest
{
    public const EDITABLE = [
        'name', 'email', 'phone', 'zipCode', 'street', 'number', 'complement', 'district', 'city', 'state',
        'niche', 'modality', 'size', 'responsibleName', 'responsiblePhone',
    ];

    public function rules(): array
    {
        return array_intersect_key(parent::rules(), array_flip(self::EDITABLE))
            + ['description' => ['nullable', 'string', 'max:500']];
    }

    public function messages(): array
    {
        return parent::messages() + ['description.max' => 'Use até 500 caracteres.'];
    }
}
