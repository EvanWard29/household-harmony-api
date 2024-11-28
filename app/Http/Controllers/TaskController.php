<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Group;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use App\Models\UserReminder;
use App\Services\TaskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskController
{
    use AuthorizesRequests;

    public function __construct(protected TaskService $service) {}

    /**
     * Retrieve all tasks of the given household
     */
    public function index(TaskFilterRequest $request, Household $household)
    {
        return TaskResource::collection($this->service->getTasks($household));
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

        // Group the task
        if ($request->filled('group_id')) {
            $task->group()->associate(Group::findOrFail($request->integer('group_id')));
        }

        $task->save();

        // Set the assigned users
        if ($request->filled('assigned')) {
            $task->assigned()->sync($request->input('assigned'));
        }

        // Schedule task reminders
        $this->service->scheduleReminders($task);

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

        // Group the task
        if ($request->filled('group_id')) {
            $task->group()->associate(Group::findOrFail($request->integer('group_id')));
        }

        // Re-schedule reminders if the deadline or assigned users has changed
        if ($request->filled('deadline') || $request->filled('assigned')) {
            $this->service->scheduleReminders($task);
        }

        return new TaskResource($task);
    }

    /**
     * Delete the given task
     */
    public function destroy(Household $household, Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json();
    }
}
