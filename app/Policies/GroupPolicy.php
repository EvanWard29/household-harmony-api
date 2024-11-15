<?php

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    /**
     * Check if a user can manage groups/categories
     */
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo(PermissionsEnum::GROUP_MANAGE);
    }
}
