<?php

namespace App\Http\Requests;

use App\Enums\TaskStatusEnum;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:65535'],
            'status' => [Rule::enum(TaskStatusEnum::class)],
            'deadline' => ['nullable', 'date_format:'.Carbon::ATOM],

            'assigned' => ['array', 'exclude'],
            'assigned.*' => [
                'int',
                Rule::exists(User::class)
                    ->where('household_id', $this->user()->household_id),
            ],

            'group_id' => [
                'int',
                'nullable',
                Rule::exists(Group::class, 'id')
                    ->where('household_id', $this->user()->household_id),
            ],

            'owner_id' => ['missing'],
            'household_id' => ['missing'],
        ];
    }
}
