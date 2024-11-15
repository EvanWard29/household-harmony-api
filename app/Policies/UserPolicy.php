<?php

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Check if requesting user has permission to view user
     */
    public function view(User $authenticatedUser, User $user): bool
    {
        return $authenticatedUser->is($user);
    }

    /**
     * Check if the requesting user has permission to edit the user
     */
    public function update(User $authenticatedUser, User $user): bool
    {
        return $authenticatedUser->is($user)
            || $authenticatedUser->hasPermissionTo(PermissionsEnum::MEMBER_EDIT);
    }
}
