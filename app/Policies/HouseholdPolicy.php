<?php

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class HouseholdPolicy
{
    use HandlesAuthorization;

    /**
     * Check if the user can view the requested household
     */
    public function view(User $user, Household $household): bool
    {
        return $user->household()->is($household);
    }

    /**
     * Check if a user can manage household members
     */
    public function manage(User $user, Household $household): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::MEMBER_MANAGE);
    }

    /**
     * Check if the requesting user can assign permissions
     */
    public function permissions(User $user): Response
    {
        return $user->isAdmin()
            ? Response::allow()
            : Response::deny('Only admins can modify permissions.');
    }
}
