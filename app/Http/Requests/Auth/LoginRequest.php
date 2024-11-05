<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest{
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
        ];
    }

    public function authorize(): bool
    {
        return is_null($this->user());
    }
}
