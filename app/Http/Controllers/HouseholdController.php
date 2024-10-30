<?php

namespace App\Http\Controllers;

use App\Http\Resources\HouseholdResource;
use App\Mail\HouseholdInviteMail;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\User;
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
     * Invite a user to the household
     */
    public function invite(Request $request, Household $household): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', Rule::unique(User::class)],
        ]);

        // Create a unique invite token
        do {
            $token = \Str::random();
        } while (HouseholdInvite::where('token', $token)->exists());

        $invite = HouseholdInvite::make([
            'email' => $request->input('email'),
            'token' => $token,
        ]);

        $invite->household()->associate($household);
        $invite->save();

        // Send invite email
        \Mail::to($invite->email)->queue(new HouseholdInviteMail($invite));

        return response()->json();
    }
}
