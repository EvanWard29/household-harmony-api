<?php

namespace App\Enums;

use App\Traits\HasValues;

enum TaskStatusEnum: string
{
    use HasValues;

    case COMPLETED = 'completed';
    case IN_PROGRESS = 'in_progress';
    case TODO = 'todo';
}
