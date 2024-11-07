<?php

namespace App\Policies;

use App\Enums\RolesEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class HouseholdPolicy
{
    use HandlesAuthorization;

    /**
     * Check if the user can view the requested household
     */
    public function view(User $user, Household $household): bool
    {
        return $user->household_id === $household->id;
    }

    /**
     * Check if the user can make updates to the requested household
     */
    public function update(User $user, Household $household): bool
    {
        return $user->can('view', $household) && $user->hasRole(RolesEnum::ADMIN);
    }

    /**
     * Check if a user can manage household members
     */
    public function manage(User $user, Household $household): bool
    {
        return $user->can('view', $household) && $user->hasRole(RolesEnum::ADMIN);
    }
}
