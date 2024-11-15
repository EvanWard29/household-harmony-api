<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    /**
     * Allow task deletion of other members
     */
    case TASK_DELETE = 'task.delete';

    /**
     * Allow creation of tasks
     */
    case TASK_CREATE = 'task.create';

    /**
     * Allow editing tasks of other members
     */
    case TASK_EDIT = 'task.edit';

    /**
     * Allow managing users of a household
     */
    case MEMBER_MANAGE = 'member.manage';

    /**
     * Allow editing of another member's details
     */
    case MEMBER_EDIT = 'member.edit';

    /**
     * Allow editing the details of a household
     */
    case HOUSEHOLD_EDIT = 'household.edit';

    /**
     * Allow creation/editing/deletion of groups/categories
     */
    case GROUP_MANAGE = 'group.manage';
}
