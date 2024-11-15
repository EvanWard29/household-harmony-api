<?php

namespace App\Policies;

use App\Enums\PermissionsEnum;
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
        return $user->hasPermissionTo(PermissionsEnum::TASK_CREATE);
    }

    /**
     * Check if user can edit the requested task
     */
    public function update(User $user, Task $task): bool
    {
        return $task->owner()->is($user) || $user->hasPermissionTo(PermissionsEnum::TASK_EDIT);
    }

    /**
     * Check if user can delete the requested task
     */
    public function delete(User $user, Task $task): bool
    {
        return $task->owner()->is($user) || $user->hasPermissionTo(PermissionsEnum::TASK_DELETE);
    }
}
