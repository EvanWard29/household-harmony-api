<?php

namespace App\Http\Requests;

use App\Enums\TaskStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65535'],
            'status' => ['required', Rule::enum(TaskStatusEnum::class)],
            'deadline' => ['nullable', 'date'],

            'owner_id' => ['missing'],
            'household_id' => ['missing'],
        ];
    }
}
