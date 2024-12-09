<?php

namespace Tests\Feature;

use App\Enums\RolesEnum;
use App\Models\Household;
use App\Models\User;
use App\Notifications\HouseholdInviteNotification;
use Tests\TestCase;

class HouseholdInviteTest extends TestCase
{
    /**
     * Test inviting a user to a household
     */
    public function test_invite()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Invite a user to the household
        $response = $this->actingAs($household->owner)->postJson(
            route('household.invite', ['household' => $household]),
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->safeEmail(),
            ]
        );

        $response->assertOk();
        $household->refresh();

        // A new user should have been created for the household
        $this->assertCount(2, $household->users);
        $user = $household->users->firstWhere('id', '!=', $household->owner_id);

        // The new user should not be marked as active
        $this->assertFalse($user->is_active);

        // The new user should have received an invitation
        \Notification::assertSentTo($user, HouseholdInviteNotification::class);

        // The new user should have default reminder settings
        $this->assertNotEmpty($user->reminders);
    }

    /**
     * Test inviting a user that already belongs to a household
     */
    public function test_invite_unique()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Create a user for another household
        $user = User::factory()->create();

        // Attempt to invite this user to the household
        $response = $this->actingAs($household->owner)->postJson(
            route('household.invite', ['household' => $household]),
            [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ]
        );

        $response->assertUnprocessable();
        $response->assertJson([
            'message' => 'User is already in a household.',
        ]);
    }

    /**
     * Test validating an invite request
     */
    public function test_invite_validation()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Invite a user to the household
        $response = $this->actingAs($household->owner)->postJson(
            route('household.invite', ['household' => $household]),
            [
                'first_name' => fake()->firstName(),
                'email' => fake()->word(),
            ]
        );

        $response->assertUnprocessable();
        $response->assertJson([
            'errors' => [
                'last_name' => ['The last name field is required.'],
                'email' => ['The email field must be a valid email address.'],
            ],
        ]);
    }

    /**
     * Test creating a child account for a household
     */
    public function test_child()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Attempt to create a child account
        $response = $this->actingAs($household->owner)->postJson(
            route('household.create-child', ['household' => $household]),
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'username' => fake()->userName(),
                'password' => 'password123!!',
                'password_confirmation' => 'password123!!',
            ]
        );

        $response->assertCreated();
        $household->refresh();

        // A new user should have been created for the household
        $this->assertCount(2, $household->users);
        $user = $household->users->firstWhere('id', '!=', $household->owner_id);

        // The new user should be marked as active
        $this->assertTrue($user->is_active);

        // The new user should have the `child` role
        $this->assertTrue($user->hasRole(RolesEnum::CHILD));

        // The new user should have default reminder settings
        $this->assertNotEmpty($user->reminders);
    }

    /**
     * Test creating a child account with an already taken username
     */
    public function test_child_unique()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Add a child user to the household
        $user = User::factory()->for($household)->child()->create();

        // Create another household
        $household = Household::factory()->hasOwner()->create();

        // Attempt to create a child with an already taken username
        $response = $this->actingAs($household->owner)->postJson(
            route('household.create-child', ['household' => $household]),
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'username' => $user->username,
                'password' => 'password123!!',
                'password_confirmation' => 'password123!!',
            ]
        );

        $response->assertUnprocessable();
        $response->assertJson([
            'message' => 'The username has already been taken.',
        ]);
    }

    /**
     * Test validating a create child request
     */
    public function test_child_validation()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Attempt to create a child account
        $response = $this->actingAs($household->owner)->postJson(
            route('household.create-child', ['household' => $household]),
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'password' => 'password123!!',
                'password_confirmation' => 'password',
            ]
        );

        $response->assertUnprocessable();
        $response->assertJson([
            'errors' => [
                'username' => ['The username field is required.'],
                'password' => ['The password field confirmation does not match.'],
            ],
        ]);
    }

    /**
     * Test inviting more than 4 users without a subscription is forbidden
     */
    public function test_freemium_invite()
    {
        // Create a household with 4 users
        $household = Household::factory()->hasOwner()->withUsers(4)->create();

        // Attempt to invite an additional user
        $response = $this->actingAs($household->owner)->postJson(
            route('household.invite', ['household' => $household]),
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->safeEmail(),
            ]
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'Subscription required for households bigger than 4 users.']);
    }

    /**
     * Test inviting more than 4 users with a subscription is allowed
     */
    public function test_premium_invite()
    {
        // Create a household with 4 users
        $household = Household::factory()->hasOwner()->withUsers(4)->subscribed()->create();

        // Attempt to invite an additional user
        $response = $this->actingAs($household->owner)->postJson(
            route('household.invite', ['household' => $household]),
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->safeEmail(),
            ]
        );

        $response->assertOk();

        $this->assertCount(5, $household->users()->get());
        \Notification::assertSentTimes(HouseholdInviteNotification::class, 1);
    }

    /**
     * Test creating a child user without a subscription and having 4 users already is forbidden
     */
    public function test_freemium_child()
    {
        // Create a household with 4 users
        $household = Household::factory()->hasOwner()->withUsers(4)->create();

        // Attempt to create an additional child user
        $response = $this->actingAs($household->owner)->postJson(
            route('household.create-child', ['household' => $household]),
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'username' => fake()->userName(),
            ]
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'Subscription required for households bigger than 4 users.']);
    }

    /**
     * Test creating a child user with a subscription and having 4 users already is allowed
     */
    public function test_premium_child()
    {
        // Create a household with 4 users
        $household = Household::factory()->hasOwner()->withUsers(4)->subscribed()->create();

        // Attempt to create an additional child user
        $response = $this->actingAs($household->owner)->postJson(
            route('household.create-child', ['household' => $household]),
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'username' => fake()->userName(),
                'password' => $password = 'password123!!',
                'password_confirmation' => $password,
            ]
        );

        $response->assertCreated();

        $this->assertCount(5, $household->users()->get());
    }
}
