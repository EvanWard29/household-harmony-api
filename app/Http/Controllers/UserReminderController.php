<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserReminderRequest;
use App\Http\Resources\UserResource;
use App\Models\Household;
use App\Models\User;
use App\Models\UserReminder;

class UserReminderController
{
    /**
     * Create a new reminder setting for the user
     */
    public function store(UserReminderRequest $request, Household $household, User $user): UserResource
    {
        $user->reminders()->create($request->validated());

        $user->load(['reminders']);

        return new UserResource($user);
    }

    /**
     * Update an existing reminder setting
     */
    public function update(
        UserReminderRequest $request,
        Household $household,
        User $user,
        UserReminder $reminder
    ): UserResource {
        $reminder->update($request->validated());

        $user->load(['reminders']);

        return new UserResource($user);
    }

    /**
     * Delete a user reminder setting
     */
    public function destroy(Household $household, User $user, UserReminder $reminder): UserResource
    {
        $reminder->delete();

        $user->load(['reminders']);

        return new UserResource($user);
    }

    /**
     * Toggle a user reminder on/off
     */
    public function toggle(Household $household, User $user, UserReminder $reminder): UserResource
    {
        $reminder->update(['enabled' => ! $reminder->enabled]);

        $user->load(['reminders']);

        return new UserResource($user);
    }
}
