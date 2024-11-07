<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TokenRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required_without:username',
                'missing_with:username',
                'email',
            ],
            'username' => [
                'required_without:email',
                'missing_with:email',
                'string',
            ],
            'password' => [
                'required',
                'string',
            ],
            'device_name' => ['required', 'string'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
