<?php

namespace Database\Factories;

use App\Enums\TaskStatusEnum;
use App\Models\Group;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use App\Models\UserReminder;
use Carbon\Carbon;
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
            'deadline' => Carbon::parse(fake()->dateTimeBetween('now', '+1 week'))->startOfMinute(),

            'household_id' => Household::factory(),
            'group_id' => function (array $attributes) {
                return Group::factory()->for(Household::findOrFail($attributes['household_id']));
            },
            'owner_id' => function (array $attributes) {
                return User::factory()->for(Household::findOrFail($attributes['household_id']));
            },
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Task $task) {
            // Schedule reminders for assigned users
            if ($task->deadline) {
                $task->assigned->each(function (User $user) use ($task) {
                    $user->reminders->each(function (UserReminder $reminder) use ($task) {
                        $task->reminders()->create([
                            'user_reminder_id' => $reminder->id,
                            'time' => $task->deadline->subSeconds($reminder->length),
                        ]);
                    });
                });
            }
        });
    }
}
