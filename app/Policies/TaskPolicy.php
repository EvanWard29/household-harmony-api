<?php

namespace App\Policies;

use App\Enums\RolesEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Check if user can create tasks
     */
    public function create(User $user): bool
    {
        // TODO: User should have permission to create tasks
        return true;
    }

    /**
     * Check if user can edit the requested task
     */
    public function update(User $user, Task $task): bool
    {
        // TODO: User should own the task or have permission to update tasks
        return $task->owner()->is($user) || $user->hasRole(RolesEnum::ADMIN);
    }

    /**
     * Check if user can delete the requested task
     */
    public function delete(User $user, Task $task): bool
    {
        // TODO: User should either own task or have permission to delete tasks
        return $task->owner()->is($user) || $user->hasRole(RolesEnum::ADMIN);
    }
}
