<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GroupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'string',
                'max:255',
                'filled',
                'required',
            ],
            'description' => ['string', 'nullable', 'max:65535'],
        ];
    }
}
