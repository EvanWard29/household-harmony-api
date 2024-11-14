<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Check if user has permission to create groups
     */
    public function create(User $user): bool
    {
        // TODO: User has permission to create groups
        return true;
    }

    /**
     * Check if user has permission to edit groups
     */
    public function update(User $user): bool
    {
        // TODO: User has permission to edit groups
        return true;
    }

    /**
     * Check if user has permission to delete groups
     */
    public function delete(User $user): bool
    {
        // TODO: User has permission to delete groups
        return true;
    }
}
