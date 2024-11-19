<?php

namespace App\Policies;

use App\Enums\PermissionsEnum;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    use HandlesAuthorization;

    /**
     * Check if user can create tasks
     */
    public function create(User $user): Response
    {
        return $user->hasPermissionTo(PermissionsEnum::TASK_CREATE)
            ? Response::allow()
            : Response::deny('You do not have permission to create tasks.');
    }

    /**
     * Check if user can edit the requested task
     */
    public function update(User $user, Task $task): Response
    {
        return $task->owner()->is($user) || $user->hasPermissionTo(PermissionsEnum::TASK_EDIT)
            ? Response::allow()
            : Response::deny('You do not have permission to update this task.');
    }

    /**
     * Check if user can delete the requested task
     */
    public function delete(User $user, Task $task): Response
    {
        return $task->owner()->is($user) || $user->hasPermissionTo(PermissionsEnum::TASK_DELETE)
            ? Response::allow()
            : Response::deny('You do not have permission to delete this task.');
    }
}
