<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\CreateChildRequest;
use App\Http\Requests\InviteRequest;
use App\Http\Resources\UserResource;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\User;
use App\Notifications\HouseholdInviteNotification;

class HouseholdInviteController
{
    /**
     * Invite a user to the household
     */
    public function invite(InviteRequest $request, Household $household)
    {
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
    public function createChild(CreateChildRequest $request, Household $household): UserResource
    {
        // Create the account and add to the household
        $child = User::make([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'username' => $request->input('username'),
            'password' => \Hash::make($request->input('password')),

            'type' => AccountType::Child,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $child->household()->associate($household);
        $child->save();

        return new UserResource($child);
    }
}
