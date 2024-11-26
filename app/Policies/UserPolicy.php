<?php

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Check if the requesting user has permission to edit the user
     */
    public function update(User $auth, User $user): Response
    {
        return $auth->is($user) || $auth->hasPermissionTo(PermissionsEnum::MEMBER_EDIT)
            ? Response::allow()
            : Response::deny('You do not have permission to edit this user.');
    }
}
