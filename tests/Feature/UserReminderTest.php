<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Tests\TestCase;

class UserReminderTest extends TestCase
{
    /**
     * Test creating new reminder settings for a user
     */
    public function test_store()
    {
        // Create a user
        $user = User::factory()->create();

        // Attempt to create a reminder
        $response = $this->actingAs($user)->postJson(
            route('household.user.reminder.store', ['household' => $user->household, 'user' => $user]),
            [
                'name' => $name = fake()->words(asText: true),
                'length' => rand(60, 604800), // Length between 1 minute and 1 week
                'enabled' => true,
            ]
        );

        $response->assertCreated();

        // A reminder should have been created
        $this->assertNotNull($user->reminders->firstWhere('name', $name));
    }

    /**
     * Test updating a user's reminder setting
     */
    public function test_update()
    {
        // Create a user
        $user = User::factory()->create();

        // Update the user's default 2-hour reminder to be a 12-hour reminder
        $response = $this->actingAs($user)->putJson(
            route(
                'household.user.reminder.update',
                [
                    'household' => $user->household,
                    'user' => $user,
                    'reminder' => $reminder = $user->reminders->firstWhere('length', 7200),
                ]
            ),
            [
                'name' => '12 Hour Reminder',
                'length' => $length = CarbonInterval::hours(12)->totalSeconds,
                'enabled' => true,
            ]
        );

        $response->assertOk();
        $reminder->refresh();

        // The reminder should have been updated
        $this->assertEquals('12 Hour Reminder', $reminder->name);
        $this->assertEquals($length, $reminder->length);
    }

    /*
     * Test deleting a user's reminder setting
     */
    public function test_destroy()
    {
        // Create a user
        $user = User::factory()->create();

        // Delete one of the user's reminders
        $response = $this->actingAs($user)->deleteJson(route(
            'household.user.reminder.destroy',
            [
                'household' => $user->household,
                'user' => $user,
                'reminder' => $reminder = $user->reminders->random(),
            ]
        ));

        $response->assertOk();

        // The reminder should have been deleted
        $this->assertModelMissing($reminder);
    }

    /**
     * Test enabling/disabling a user's reminder setting
     */
    public function test_toggle()
    {
        // Create a user
        $user = User::factory()->create();

        // Disable one of the user's reminders
        $response = $this->actingAs($user)->postJson(route(
            'household.user.reminder.toggle',
            [
                'household' => $user->household,
                'user' => $user,
                'reminder' => $reminder = $user->reminders->random(),
            ]
        ));

        $response->assertOk();

        $this->assertFalse($reminder->refresh()->enabled);
    }

    /**
     * Test reminders are not scheduled for disabled reminders
     */
    public function test_disabled_reminders()
    {
        // Create a user
        $user = User::factory()->isOwner()->create();

        // Disable one of the user's reminders
        $reminder = $user->reminders->random();
        $reminder->update(['enabled' => false]);

        // Create a new task
        $response = $this->actingAs($user)->postJson(
            route('household.task.store', ['household' => $user->household]),
            [
                'title' => fake()->word(),
                'deadline' => fake()->dateTimeBetween('tomorrow', '+1 week')->format(Carbon::ATOM),
                'assigned' => [$user->id],
            ]
        );

        $response->assertCreated();

        // A task should have been created
        $task = Task::firstOrFail();

        // A reminder should not have been scheduled for the disabled user reminder
        $this->assertNull($task->reminders->firstWhere('user_reminder_id', $reminder->id));
    }
}
