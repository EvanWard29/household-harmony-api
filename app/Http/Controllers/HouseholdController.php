<?php

namespace App\Http\Controllers;

use App\Enums\RolesEnum;
use App\Http\Requests\PermissionRequest;
use App\Http\Resources\HouseholdResource;
use App\Http\Resources\UserResource;
use App\Models\Household;
use App\Models\User;
use App\Notifications\DeletedUserNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;

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
        $request->validate([
            'name' => ['string'],
            'owner_id' => [
                'int',
                Rule::exists(User::class, 'id')
                    ->where('household_id', $household->id),
            ],
        ]);

        if (
            $request->filled('owner_id')
            && ! User::find($request->input('owner_id'))->hasRole(RolesEnum::ADMIN)
        ) {
            abort(
                \HttpStatus::HTTP_FORBIDDEN,
                'The selected user is not an admin. '
                    .'Please assign them the role of Admin first if you wish to continue.'
            );
        }

        $household->update($request->input());

        return new HouseholdResource($household);
    }

    /**
     * Remove a user from the household
     */
    public function deleteUser(Request $request, Household $household, User $user): HouseholdResource
    {
        \Gate::authorize('manage', $household);

        // Users cannot delete their account if they are the owner of the household
        if ($request->user()->id === $user->id && $household->owner_id === $user->id) {
            abort(
                \HttpStatus::HTTP_FORBIDDEN,
                'Cannot delete your account when you are the owner of the household. '
                    .'Please transfer ownership to another Admin if you wish to continue.'
            );
        }

        if ($user->hasRole(RolesEnum::ADMIN)) {
            abort(
                \HttpStatus::HTTP_FORBIDDEN,
                'Cannot delete the account of another Admin.'
            );
        }

        // Notify adult users that their account has been deleted
        if ($user->email) {
            $user->notify(new DeletedUserNotification($request->user()));
        }

        // Delete the user's tokens
        $user->tokens()->delete();

        // Delete the user
        $user->delete();

        return new HouseholdResource($household);
    }

    /**
     * Set the permissions of a user
     */
    public function permissions(PermissionRequest $request, Household $household, User $user)
    {
        \Gate::authorize('permissions', Household::class);

        if (($user->isAdmin() || $request->filled('admin')) && $household->owner()->isNot($request->user())) {
            abort(\HttpStatus::HTTP_FORBIDDEN, 'Only the owner can manage admins.');
        }

        if ($request->user()->is($user)) {
            abort(\HttpStatus::HTTP_FORBIDDEN, 'Cannot change your own permissions.');
        }

        // Set user as admin
        if ($request->filled('admin')) {
            if ($request->boolean('admin')) {
                // Remove any previous permissions as admins have all by default
                $user->revokePermissionTo(Permission::all());

                // Assign the user the admin role
                $user->assignRole(RolesEnum::ADMIN);
            } else {
                $user->removeRole(RolesEnum::ADMIN);
            }
        }

        // Assign permissions
        if ($request->filled('permissions')) {
            $user->syncPermissions($request->input('permissions'));
        }

        return new UserResource($user);
    }
}
