<?php

namespace Database\Factories;

use App\Enums\TaskStatusEnum;
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
            'deadline' => now()->addDays(3),

            'owner_id' => User::factory(),
            'household_id' => Household::factory(),
        ];
    }
}
