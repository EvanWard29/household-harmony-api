<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegistrationRequest extends FormRequest{
    public function rules(): array
    {
        return [
            'first_name' => [
                Rule::requiredIf(is_null($this->route('inviteToken'))),
                'string',
                'max:255'
            ],
            'last_name' => [
                Rule::requiredIf(is_null($this->route('inviteToken'))),
                'string',
                'max:255',
            ],
            'email' => [
                Rule::requiredIf(is_null($this->route('inviteToken'))),
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => [
                'required',
                'string',
                Password::default(),
                'confirmed',
            ],
        ];
    }

    public function authorize(): bool
    {
        return is_null($this->user());
    }
}
