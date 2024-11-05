<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateChildRequest extends FormRequest
{
    private const USERNAME_REGEX = '/(?!.*[\.\-\_]{2,})^[a-zA-Z0-9\.\-\_]{3,24}$/';

    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique(User::class),
                'regex:'.self::USERNAME_REGEX,
            ],
        ];
    }
}
