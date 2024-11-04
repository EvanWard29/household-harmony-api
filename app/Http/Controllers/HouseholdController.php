<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Resources\HouseholdResource;
use App\Http\Resources\UserResource;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\User;
use App\Notifications\HouseholdInviteNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HouseholdController
{
    /**
     * Retrieve the user's household
     */
    public function show(Household $household): HouseholdResource
    {
        return new HouseholdResource($household);
    }

    /**
     * Update a user's household
     */
    public function update(Request $request, Household $household)
    {
        $data = $request->validate([
            'name' => ['required'],
        ]);

        $household->update($data);

        return $household;
    }

    /**
     * Remove a user from the household
     */
    public function deleteUser(Request $request, Household $household, User $user)
    {
        // Users cannot delete themselves
        if ($request->user() == $user) {
            abort(\HttpStatus::HTTP_FORBIDDEN, 'Cannot remove yourself.');
        }

        // TODO: Notify the user to be deleted they have been removed

        // Delete the user
        $user->delete();
    }

    /**
     * Invite a user to the household
     */
    public function invite(Request $request, Household $household)
    {
        $request->validate([
            'email' => ['required', 'email', Rule::unique(User::class)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ], [
            'email.unique' => 'User is already in a household.',
        ]);

        // Create a new pending user for the recipient
        $recipient = User::make([
            'email' => $request->input('email'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'is_active' => false,
            'type' => AccountType::Adult,
        ]);

        $recipient->household()->associate($household);
        $recipient->save();

        // Create a unique invite token
        do {
            $token = \Str::random();
        } while (HouseholdInvite::where('token', $token)->exists());

        $invite = HouseholdInvite::make([
            'token' => $token,
        ]);

        $invite->household()->associate($household);
        $invite->sender()->associate($request->user());
        $invite->recipient()->associate($recipient);

        $invite->save();

        // Send invite email
        $recipient->notify(new HouseholdInviteNotification($invite));
    }

    /**
     * Create a new child account
     */
    public function createChild(Request $request, Household $household): UserResource
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique(User::class)],
        ]);

        // Create the account and add to the household
        $child = User::make([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'username' => $request->input('username'),
            'type' => AccountType::Child,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $child->household()->associate($household);
        $child->save();

        return new UserResource($child);
    }
}
