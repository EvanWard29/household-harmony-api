<?php

namespace App\Enums;

use App\Traits\HasValues;

enum AccountType: string
{
    use HasValues;

    case Adult = 'adult';
    case Child = 'child';
}
