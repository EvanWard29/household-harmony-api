<?php

namespace Tests\Unit;

use App\Models\Household;
use App\Models\Task;
use App\Models\TaskReminder;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Tests\TestCase;

class SendTaskReminderTest extends TestCase
{
    /**
     * Test sending scheduled task reminders
     */
    public function testSendReminders()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers(6)->create();

        // Create a task
        $task = Task::factory()->hasAttached($household->users->random(3), relationship: 'assigned')->create();

        // Delete existing reminders
        $task->reminders()->delete();

        // Freeze the current time
        $this->freezeTime();

        // Schedule some reminders to send now
        $task->assigned->each(function (User $user) use ($task) {
            $task->reminders()->create([
                'time' => now(),
                'user_reminder_id' => $user->reminders->first()->id,
            ]);
        });

        // There should only be 3 reminders scheduled
        $this->assertDatabaseCount('task_reminders', 3);

        $reminders = TaskReminder::all();

        // Run the command to send reminders
        $this->artisan('task:send-reminders')->assertOk();

        // The reminders should be marked as sent
        $reminders->each(function (TaskReminder $reminder) {
            $this->assertNotNull($reminder->refresh()->sent_at);
        });

        // A task reminder notification should have been sent to each assigned user
        \Notification::assertSentTimes(TaskReminderNotification::class, 3);

        $task->assigned->each(function (User $user) {
            \Notification::assertSentTo($user, TaskReminderNotification::class);
        });
    }
}
