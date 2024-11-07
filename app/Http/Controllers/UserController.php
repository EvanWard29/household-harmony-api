<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserController
{
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
        $user->update($request->input());

        return new UserResource($user);
    }
}
