<?php

namespace App\Http\Requests;

use App\Enums\TaskStatusEnum;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskFilterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => [Rule::enum(TaskStatusEnum::class)],

            'deadline.start.date' => ['date_format:Y-m-d', 'required_with:deadline'],
            'deadline.start.time' => ['date_format:H:i'],

            'deadline.end.date' => ['date_format:Y-m-d', 'required_with:deadline'],
            'deadline.end.time' => ['date_format:H:i'],

            'assigned' => ['array'],
            'assigned.*' => [
                'int',
                Rule::exists(User::class, 'id')
                    ->where('household_id', $this->route('household')),
            ],

            'group_id' => [
                'int',
                Rule::exists(Group::class, 'id')
                    ->where('household_id', $this->user()->household_id),
            ],
        ];
    }
}
