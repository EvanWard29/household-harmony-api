<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Household;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TaskController
{
    use AuthorizesRequests;

    /**
     * Retrieve all tasks of the given household
     */
    public function index(Household $household)
    {
        return TaskResource::collection($household->tasks);
    }

    /**
     * Create a new task
     */
    public function store(TaskRequest $request, Household $household)
    {
        $this->authorize('create', Task::class);

        $task = $request->user()->household->tasks()->make($request->validated());
        $task->owner()->associate($request->user());
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
