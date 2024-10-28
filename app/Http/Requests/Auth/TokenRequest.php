<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class TokenRequest extends FormRequest{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
