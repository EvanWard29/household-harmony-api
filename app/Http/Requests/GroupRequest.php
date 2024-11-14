<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GroupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'max:255',
                'filled',
                Rule::requiredIf(in_array($this->method(), [self::METHOD_POST, self::METHOD_PUT])),
            ],
            'description' => ['string', 'nullable', 'max:65535'],
        ];
    }
}
