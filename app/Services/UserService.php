<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserReminder;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;

readonly class UserService
{
    public function __construct(private User $user) {}

    /**
     * Create the default 2 & 24 hour reminders for the user
     *
     * @return Collection<UserReminder>
     */
    public function createDefaultReminders(): Collection
    {
        return $this->user->reminders()->createMany([
            [
                'name' => '2 Hour Reminder',
                'length' => CarbonInterval::hours(2)->totalSeconds,
                'enabled' => true,
            ],
            [
                'name' => '24 Hour Reminder',
                'length' => CarbonInterval::hours(24)->totalSeconds,
                'enabled' => true,
            ],
        ]);
    }
}
