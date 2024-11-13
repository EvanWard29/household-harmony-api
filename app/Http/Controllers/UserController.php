<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController
{
    use AuthorizesRequests;

    /**
     * Get the requesting {@see User}
     */
    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    /**
     * Update the requesting {@see User}
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorize('update', $user);

        $user->update($request->input());

        return new UserResource($user);
    }
}
