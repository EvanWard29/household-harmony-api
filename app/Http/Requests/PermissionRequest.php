<?php

namespace App\Http\Requests;

use App\Enums\PermissionsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'admin' => ['bool'],

            'permissions' => ['array', 'missing_if:admin,true'],
            'permissions.*' => [Rule::enum(PermissionsEnum::class)],
        ];
    }
}
