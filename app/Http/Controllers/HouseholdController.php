<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\CreateChildRequest;
use App\Http\Requests\InviteRequest;
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
}
