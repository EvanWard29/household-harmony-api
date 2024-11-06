<?php

namespace App\Policies;

use App\Enums\RolesEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function view(User $authenticatedUser, User $user): bool
    {
        return $authenticatedUser->id === $user->id;
    }

    /**
     * Check if the requesting user has permission to edit the user
     */
    public function update(User $authenticatedUser, User $user): bool
    {
        return $authenticatedUser->can('view', $user)
            || ($authenticatedUser->can('manage', $user->household)
                && ! $user->hasRole(RolesEnum::ADMIN));
    }
}
