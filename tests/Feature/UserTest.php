<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UserTest extends TestCase
{
    /**
     * Test getting a user
     */
    public function testShow()
    {
        // Create a user
        $user = User::factory()->create();

        // Get the user's details
        $response = $this->actingAs($user)->getJson(route(
            'household.user.show',
            ['household' => $user->household, 'user' => $user]
        ));

        $response->assertOk();
        $response->assertJson(function (AssertableJson $json) {
            $json->has('data.id')
                ->has('data.household_id')
                ->has('data.permissions')
                ->has('data.is_admin')
                ->missing('data.password');
        });
    }

    /**
     * Test updating a user's details
     */
    public function testUpdate()
    {
        // Create a user
        $user = User::factory()->create();

        // Update the user's details
        $response = $this->actingAs($user)->patchJson(
            route('household.user.update', ['household' => $user->household, 'user' => $user]),
            [
                'username' => $username = fake()->userName(),
            ]
        );

        $response->assertOk();

        // The user's details should have been updated
        $user->refresh();
        $this->assertEquals($username, $user->username);
    }

    /**
     * Test updating another user's details as an admin
     */
    public function testUpdateAdmin()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Create another user for the household
        $user = User::factory()->for($household)->create();

        // Attempt to change the details of the created user as the admin
        $response = $this->actingAs($household->owner)->patchJson(
            route('household.user.update', ['household' => $household, 'user' => $user]),
            [
                'username' => $username = fake()->userName(),
            ]
        );

        $response->assertOk();

        // The user's details should have been updated
        $user->refresh();
        $this->assertEquals($username, $user->username);
    }

    /**
     * Test updating another user's details as a non-admin
     */
    public function testUpdateNonAdmin()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Create another user for the household
        $user = User::factory()->for($household)->create();

        // Attempt to change the details of the created user as the non-admin
        $response = $this->actingAs($user)->patchJson(
            route('household.user.update', ['household' => $household, 'user' => $household->owner]),
            [
                'username' => fake()->userName(),
            ]
        );

        $response->assertForbidden();
    }

    /**
     * Test validating a request to update a user's username
     */
    public function testUpdateUsernameValidation()
    {
        // Create a user
        $user = User::factory()->create();

        // Update the user's details
        $response = $this->actingAs($user)->patchJson(
            route('household.user.update', ['household' => $user->household, 'user' => $user]),
            [
                'username' => fake()->email(),
            ]
        );

        $response->assertUnprocessable();
        $response->assertJson([
            'message' => 'The username field format is invalid.',
        ]);
    }

    /**
     * Test getting a user's assigned tasks
     */
    public function testTasks()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers(10)->create();

        // Create some tasks and assign to users
        $tasks = Task::factory()
            ->for($household)
            ->count(rand(6, 12))
            ->create();

        // Assign some users to the tasks
        $tasks->each(function (Task $task) use ($household) {
            $task->assigned()->sync($household->users->random(rand(1, 3)));
        });

        // Get a user to request as
        $user = $household->users()->whereHas('tasks')->get()->random();

        // Attempt to get a user's task list
        $response = $this->actingAs($user)->getJson(
            route('household.user.task', ['household' => $user->household, 'user' => $user])
        );

        $response->assertOk();
        $response->assertJson(function (AssertableJson $json) use ($user) {
            $json->has('data', $user->tasks->count());
        });
    }
}
