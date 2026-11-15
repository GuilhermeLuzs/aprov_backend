<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'currentPassword' => ['required', 'string', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-zÀ-ÿ]/', 'regex:/\d/', 'different:currentPassword'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Campo obrigatório.',
            'currentPassword.current_password' => 'A senha atual não confere.',
            'password.min' => 'A senha precisa ter 8 caracteres ou mais, com letra e número.',
            'password.regex' => 'A senha precisa ter 8 caracteres ou mais, com letra e número.',
            'password.different' => 'A senha nova precisa ser diferente da atual.',
        ];
    }
}
