<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserReminderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'length' => ['required', 'int', 'min:60', 'max:604800'], // Maximum length of 1 week
            'enabled' => ['required', 'bool'],
        ];
    }
}
