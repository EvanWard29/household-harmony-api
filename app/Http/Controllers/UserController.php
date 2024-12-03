<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskFilterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Models\Household;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController
{
    use AuthorizesRequests;

    /**
     * Get the requesting {@see User}
     */
    public function show(Household $household, User $user): UserResource
    {
        $user->load(['reminders']);

        return new UserResource($user);
    }

    /**
     * Update the requesting {@see User}
     */
    public function update(UpdateUserRequest $request, Household $household, User $user): UserResource
    {
        $this->authorize('update', $user);

        $user->update($request->input());

        return new UserResource($user);
    }

    /**
     * Get a user's tasks
     *
     * @return AnonymousResourceCollection<TaskResource>
     */
    public function tasks(TaskFilterRequest $request, Household $household, User $user): AnonymousResourceCollection
    {
        return TaskResource::collection(app(TaskService::class)->getTasks($user, $request->validated()));
    }
}
