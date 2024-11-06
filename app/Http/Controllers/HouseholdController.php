<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\CreateChildRequest;
use App\Http\Requests\InviteRequest;
use App\Http\Resources\HouseholdResource;
use App\Http\Resources\UserResource;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\User;
use App\Notifications\DeletedUserNotification;
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
    public function update(Request $request, Household $household): HouseholdResource
    {
        $data = $request->validate([
            'name' => ['required'],
        ]);

        $household->update($data);

        return new HouseholdResource($household);
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

        // Notify adult users that their account has been deleted
        if ($user->email) {
            $user->notify(new DeletedUserNotification($request->user()));
        }

        // Delete the user's tokens
        $user->tokens()->delete();

        // Delete the user
        $user->delete();
    }

    /**
     * Assign a user some roles/permissions
     */
    public function assignRoles(Request $request, Household $household, User $user): UserResource
    {
        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => [Rule::enum(RolesEnum::class)],
        ]);

        $user->syncRoles($request->input('roles'));

        return new UserResource($user);
    }
}
