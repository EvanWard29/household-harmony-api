<?php

namespace App\Policies;

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
        // TODO: Also check if user is `admin`
        return $user->can('view', $household);
    }

    /**
     * Check if the user can invite new users to the requested household
     */
    public function invite(User $user, Household $household): bool
    {
        // TODO: Also check if user is `admin`
        return $user->can('view', $household);
    }
}
