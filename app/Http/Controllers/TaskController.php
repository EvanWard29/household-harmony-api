<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatusEnum;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController
{
    use AuthorizesRequests;

    /**
     * Retrieve all tasks of the given household
     */
    public function index(Request $request, Household $household)
    {
        $request->validate([
            'status' => [Rule::enum(TaskStatusEnum::class)],

            'deadline.start.date' => ['date_format:Y-m-d', 'required_with:deadline'],
            'deadline.start.time' => ['date_format:H:i'],

            'deadline.end.date' => ['date_format:Y-m-d', 'required_with:deadline'],
            'deadline.end.time' => ['date_format:H:i'],

            'assigned' => ['array'],
            'assigned.*' => [
                'int',
                Rule::exists(User::class, 'id')->where('household_id', $household->id),
            ],
        ]);

        $tasks = $household->tasks();

        // Filter tasks by status
        if ($request->filled('status')) {
            $tasks = $tasks->where('status', $request->enum('status', TaskStatusEnum::class));
        }

        // Filter tasks between the set date and time deadline
        if ($request->filled('deadline')) {
            $start = $request->date('deadline.start.date');
            $end = $request->date('deadline.end.date');

            if ($request->filled('deadline.start.time')) {
                $start->setTimeFrom($request->date('deadline.start.time'));
            } else {
                $start->startOfDay();
            }

            if ($request->filled('deadline.end.time')) {
                $end->setTimeFrom($request->date('deadline.end.time'));
            } else {
                $end->endOfDay();
            }

            $tasks = $tasks->whereBetween('deadline', [$start, $end]);
        }

        // Filter tasks by assignee
        if ($request->filled('assigned')) {
            $tasks = $tasks->whereHas('assigned', function (Builder $query) use ($request) {
                $query->whereIn('id', $request->input('assigned'));
            });
        }

        $tasks->with('assigned.roles');

        return TaskResource::collection($tasks->get());
    }

    /**
     * Create a new task
     */
    public function store(TaskRequest $request, Household $household)
    {
        $this->authorize('create', Task::class);

        $task = $request->user()->household->tasks()->make($request->validated());

        // Set the owner/creator of the task
        $task->owner()->associate($request->user());

        // Set the assigned users
        if ($request->filled('assigned')) {
            $task->assigned()->sync($request->input('assigned'));
        }

        $task->save();

        return new TaskResource($task);
    }

    /**
     * Retrieve the given task
     */
    public function show(Household $household, Task $task)
    {
        return new TaskResource($task);
    }

    /**
     * Update the given task
     */
    public function update(TaskRequest $request, Household $household, Task $task)
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        // Set the assigned users
        if ($request->filled('assigned')) {
            $task->assigned()->sync($request->input('assigned'));
        }

        return new TaskResource($task);
    }

    /**
     * Delete the given task
     */
    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json();
    }
}
