<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function view(User $authenticatedUser, User $user): bool
    {
        return $authenticatedUser->id === $user->id;
    }

    public function update(User $authenticatedUser, User $user): bool
    {
        return $this->view($authenticatedUser, $user);
    }
}
