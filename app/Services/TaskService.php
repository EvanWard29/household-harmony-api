<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use App\Models\UserReminder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    /**
     * Get tasks of the given user or household
     *
     * @return Collection<Task>
     */
    public function getTasks(User|Household $model): Collection
    {
        $tasks = $model->tasks();

        // Filter tasks by status
        if (request()->filled('status')) {
            $tasks = $tasks->where('status', (request()->enum('status', TaskStatusEnum::class)));
        }

        // Filter tasks between the set date and time deadline
        if (request()->filled('deadline')) {
            $start = request()->date('deadline.start.date');
            $end = request()->date('deadline.end.date');

            if (request()->filled('deadline.start.time')) {
                $start->setTimeFrom(request()->date('deadline.start.time'));
            } else {
                $start->startOfDay();
            }

            if (request()->filled('deadline.end.time')) {
                $end->setTimeFrom(request()->date('deadline.end.time'));
            } else {
                $end->endOfDay();
            }

            $tasks = $tasks->whereBetween('deadline', [$start, $end]);
        }

        // Filter tasks by assignee
        if (request()->filled('assigned')) {
            $tasks = $tasks->whereHas('assigned', function (Builder $query) {
                $query->whereIn('id', request()->input('assigned'));
            });
        }

        // Filter tasks by group
        if (request()->filled('group_id')) {
            $tasks = $tasks->whereRelation('group', 'id', request()->integer('group_id'));
        }

        $tasks->with(['assigned.roles', 'group']);

        return $tasks->get();
    }

    /**
     * Schedule reminders for the given task
     */
    public function scheduleReminders(Task $task): void
    {
        // Skip tasks that do not have a deadline set
        if (! $task->deadline) {
            return;
        }

        // Remove any existing reminders
        $task->reminders()->delete();

        $task->loadMissing('assigned.reminders');

        // Schedule reminders for each of the assigned user's enabled reminders
        $task->assigned->each(function (User $assigned) use ($task) {
            $assigned->reminders->where('enabled', true)->each(function (UserReminder $reminder) use ($task) {
                $task->reminders()->create([
                    'user_reminder_id' => $reminder->id,
                    'time' => $task->deadline->subSeconds($reminder->length),
                ]);
            });
        });
    }
}
