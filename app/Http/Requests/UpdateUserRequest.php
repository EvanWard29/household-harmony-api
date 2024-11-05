<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'username' => [
                'string',
                'max:255',
                'regex:'.User::USERNAME_REGEX,
                Rule::unique(User::class),
            ],
        ];
    }
}
