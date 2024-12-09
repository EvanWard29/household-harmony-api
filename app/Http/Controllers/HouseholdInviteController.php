<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\CreateChildRequest;
use App\Http\Requests\InviteRequest;
use App\Http\Resources\UserResource;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\User;
use App\Notifications\HouseholdInviteNotification;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

class HouseholdInviteController implements HasMiddleware
{
    /**
     * Invite a user to the household
     */
    public function invite(InviteRequest $request, Household $household)
    {
        // Only subscribed users can have a household bigger than 4
        abort_if(
            ! $household->isSubscribed() && $household->users()->count() >= 4,
            \HttpStatus::HTTP_FORBIDDEN,
            'Subscription required for households bigger than 4 users.'
        );

        // Create a new pending user for the recipient
        $recipient = User::make([
            'email' => $request->input('email'),
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),

            'is_active' => false,
        ]);

        $recipient->household()->associate($household);
        $recipient->save();

        // Create a unique invite token
        do {
            $token = \Str::random();
        } while (HouseholdInvite::where('token', $token)->exists());

        $invite = HouseholdInvite::make();
        $invite->token = $token;

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
        // Only subscribed users can have a household bigger than 4
        abort_if(
            ! $household->isSubscribed() && $household->users()->count() >= 4,
            \HttpStatus::HTTP_FORBIDDEN,
            'Subscription required for households bigger than 4 users.'
        );

        // Create the account and add to the household
        $child = User::make([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'username' => $request->input('username'),
            'password' => \Hash::make($request->input('password')),

            'is_active' => true,
        ]);

        $child->household()->associate($household);
        $child->save();

        // Child accounts are automatically assigned the `child` role
        $child->assignRole(RolesEnum::CHILD);

        return new UserResource($child);
    }

    public static function middleware(): array
    {
        return [
            function (Request $request, \Closure $next) {
                /** @var Household $household */
                $household = $request->route('household');

                // Only subscribed households can have a household bigger than 4
                abort_if(
                    ! $household->isSubscribed() && $household->users()->count() >= 4,
                    \HttpStatus::HTTP_FORBIDDEN,
                    'Subscription required for households bigger than 4 users.'
                );

                return $next($request);
            },
        ];
    }
}
