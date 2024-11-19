<?php

namespace Database\Factories;

use App\Enums\TaskStatusEnum;
use App\Models\Group;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title' => fake()->words(asText: true),
            'description' => fake()->text(),
            'status' => TaskStatusEnum::TODO,
            'deadline' => now()->addDays(3)->startOfMinute(),

            'household_id' => Household::factory(),
            'group_id' => function (array $attributes) {
                return Group::factory()->for(Household::findOrFail($attributes['household_id']));
            },
            'owner_id' => function (array $attributes) {
                return User::factory()->for(Household::findOrFail($attributes['household_id']));
            },
        ];
    }
}
