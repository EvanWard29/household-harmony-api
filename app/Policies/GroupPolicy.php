<?php

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Check if a user can manage groups/categories
     */
    public function manage(User $user): Response
    {
        return $user->hasPermissionTo(PermissionsEnum::GROUP_MANAGE)
            ? Response::allow()
            : Response::deny('You do not have permission to manage groups/categories.');
    }
}
