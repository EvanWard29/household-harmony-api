<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordResetRequest extends FormRequest
{
    /**
     * Required fields for the `/password/reset` endpoint
     *
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
