<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('auth:clear-resets')->everyFifteenMinutes();

Schedule::command('model:prune')->daily();
