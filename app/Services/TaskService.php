<?php

namespace App\Services;

use App\Enums\TaskStatusEnum;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use App\Models\UserReminder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TaskService
{
    /**
     * Get tasks of the given user or household
     *
     * @return Collection<Task>
     */
    public function getTasks(User|Household $model, array $filters = []): Collection
    {
        $tasks = $model->tasks();

        // Filter tasks by status
        if (! empty($filters['status'])) {
            $tasks = $tasks->where('status', TaskStatusEnum::from($filters['status']));
        }

        // Filter tasks between the set date and time deadline
        if (! empty($filters['deadline'])) {
            if (empty($filters['deadline']['start']['date']) || empty($filters['deadline']['end']['date'])) {
                abort(
                    \HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
                    'The `deadline.start.date` and `deadline.end.date` fields are required when `deadline` is present.'
                );
            }

            $start = Carbon::parse($filters['deadline']['start']['date']);
            $end = Carbon::parse($filters['deadline']['end']['date']);

            if (! empty($filters['deadline']['start']['time'])) {
                $start->setTimeFrom($filters['deadline']['start']['time']);
            } else {
                $start->startOfDay();
            }

            if (! empty($filters['deadline']['end']['time'])) {
                $end->setTimeFrom($filters['deadline']['end']['time']);
            } else {
                $end->endOfDay();
            }

            $tasks = $tasks->whereBetween('deadline', [$start, $end]);
        }

        // Filter tasks by assignee
        if (! empty($filters['assigned'])) {
            $tasks = $tasks->whereHas('assigned', function (Builder $query) use ($filters) {
                $query->whereIn('id', $filters['assigned']);
            });
        }

        // Filter tasks by group
        if (! empty($filters['group_id'])) {
            $tasks = $tasks->whereRelation('group', 'id', $filters['group_id']);
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
