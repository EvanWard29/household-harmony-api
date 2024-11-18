<?php

namespace Tests\Feature;

use App\Enums\PermissionsEnum;
use App\Enums\RolesEnum;
use App\Http\Middleware\PasswordConfirmationMiddleware;
use App\Models\Household;
use App\Models\User;
use App\Notifications\DeletedUserNotification;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class HouseholdTest extends TestCase
{
    public function testShow()
    {
        $userCount = rand(2, 5);

        // Create a household
        /** @var Household $household */
        $household = Household::factory()
            ->has(User::factory()->count($userCount), 'users')
            ->create();

        // Assign one of the users as the owner
        $household->update(['owner_id' => $household->users->random()->id]);

        // Request the household as one of the users
        $response = $this->actingAs($household->users->random())->getJson(
            route('household.show', ['household' => $household])
        );

        $response->assertOk();

        $response->assertJson(function (AssertableJson $json) use ($household, $userCount) {
            $json->has('data')
                ->where('data.id', $household->id)
                ->where('data.name', $household->name)
                ->has('data.users', $userCount);
        });
    }

    public function testShowUnauthorized()
    {
        // Create a user
        $user = User::factory()->create();

        // Create another user
        $unauthorized = User::factory()->create();

        // Attempt to retrieve the household as the unauthorized user
        $response = $this->actingAs($unauthorized)->getJson(
            route('household.show', ['household' => $user->household])
        );

        $response->assertForbidden();
    }

    public function testUpdate()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Get a user to transfer ownership to
        $user = $users->random();

        // Assign the user the `admin` role
        $user->assignRole(RolesEnum::ADMIN);

        // Attempt to update the name and owner of the household
        $response = $this->actingAs($household->owner)->patchJson(
            route('household.update', ['household' => $household]),
            [
                'name' => $name = 'The '.\Str::possessive($household->owner->last_name),
                'owner_id' => $user->id,
            ]
        );

        $response->assertOk();

        $household->refresh();
        $this->assertEquals($name, $household->name);
        $this->assertEquals($user->id, $household->owner_id);
    }

    public function testUpdateUnauthorized()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        User::factory()->for($household)->count(3)->create();

        // Attempt to update the household as a non-admin
        $response = $this->actingAs($household->users()->whereDoesntHave('roles')->get()->random())->putJson(
            route('household.update', ['household' => $household]),
            [
                'name' => fake()->name,
            ]
        );

        $response->assertForbidden();
    }

    public function testUpdateNonAdminOwner()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Attempt to transfer ownership to a non-admin
        $response = $this->actingAs($household->owner)->putJson(
            route('household.update', ['household' => $household]),
            [
                'owner_id' => $users->random()->id,
            ]
        );

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'The selected user is not an admin. '
                .'Please assign them the role of Admin first if you wish to continue.',
        ]);
    }

    public function testDeleteUser()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        User::factory()->for($household)->count(3)->create();

        // Get a user to remove that is not the owner/admin
        $user = $household->users->where(function (User $user) use ($household) {
            return $user->id != $household->owner_id;
        })->random();

        // Attempt to remove one of the users
        $response = $this->actingAs($household->owner)
            ->withoutMiddleware(PasswordConfirmationMiddleware::class)
            ->deleteJson(route('household.user.delete', ['household' => $household, 'user' => $user]));

        $response->assertOk();

        $this->assertModelMissing($user);

        \Notification::assertSentTo($user, DeletedUserNotification::class);
    }

    public function testDeleteOwner()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Attempt to delete the owner whilst they are still the owner
        $response = $this->actingAs($household->owner)
            ->withoutMiddleware(PasswordConfirmationMiddleware::class)
            ->deleteJson(
                route('household.user.delete', ['household' => $household, 'user' => $household->owner])
            );

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'Cannot delete your account when you are the owner of the household. '
                .'Please transfer ownership to another Admin if you wish to continue.',
        ]);
    }

    public function testDeleteAdmin()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create an additional admin for the household
        $user = User::factory()->for($household)->admin()->create();

        // Attempt to remove the admin
        $response = $this->actingAs($household->owner)
            ->withoutMiddleware(PasswordConfirmationMiddleware::class)
            ->deleteJson(
                route('household.user.delete', ['household' => $household, 'user' => $user])
            );

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'Cannot delete the account of another Admin.',
        ]);
    }

    /**
     * Test assigning permissions to a household member
     */
    public function testAssignPermissions()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Attempt to assign a user all permissions
        $response = $this->actingAs($household->owner)->postJson(
            route(
                'household.user.set-permissions',
                ['household' => $household, 'user' => $user = $users->random()]
            ),
            [
                'permissions' => PermissionsEnum::cases(),
            ]
        );

        $response->assertOk();
        $this->assertTrue($user->hasAllPermissions(PermissionsEnum::cases()));
    }

    /**
     * Test setting a user as admin
     */
    public function testAssignAdmin()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Attempt to assign a user the admin role
        $response = $this->actingAs($household->owner)->postJson(
            route(
                'household.user.set-permissions',
                ['household' => $household, 'user' => $user = $users->random()]
            ),
            [
                'admin' => true,
            ]
        );

        $response->assertOk();
        $this->assertTrue($user->hasRole(RolesEnum::ADMIN));
        $this->assertTrue($user->hasAllPermissions(PermissionsEnum::cases()));
    }

    /**
     * Test validating a request to set permissions
     */
    public function testAssignPermissionsValidation()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Attempt to assign a user some permissions
        $response = $this->actingAs($household->owner)->postJson(
            route(
                'household.user.set-permissions',
                ['household' => $household, 'user' => $users->random()]
            ),
            [
                'permissions' => [
                    fake()->words(asText: true),
                ],
            ]
        );

        $response->assertUnprocessable();
        $response->assertJson(['message' => 'The selected permissions.0 is invalid.']);
    }

    /**
     * Test validating a request to set a user as admin
     */
    public function testAssignAdminValidation()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Attempt to assign a user the admin role with permissions
        $response = $this->actingAs($household->owner)->postJson(
            route(
                'household.user.set-permissions',
                ['household' => $household, 'user' => $users->random()]
            ),
            [
                'admin' => true,
                'permissions' => PermissionsEnum::cases(),
            ]
        );

        $response->assertUnprocessable();
        $response->assertJson(['message' => 'The permissions field must be missing when admin is true.']);
    }

    /**
     * Test assigning permissions as a non-admin user
     */
    public function testAssignPermissionsAsNonAdmin()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Get a user to request as
        $user = $users->random();

        // Attempt to assign permissions to a user
        $response = $this->actingAs($user)->postJson(
            route(
                'household.user.set-permissions',
                ['household' => $household, 'user' => $users->where('id', '!=', $user->id)->random()]
            ),
            [
                'permissions' => PermissionsEnum::cases(),
            ]
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'Only admins can modify permissions.']);
    }

    /**
     * Test assigning permissions to another admin as an admin
     */
    public function testAssignPermissionsToAdmin()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Get a user to request as and set as admin
        $user = $users->random()->assignRole(RolesEnum::ADMIN);

        // Attempt to assign permissions to the owner
        $response = $this->actingAs($user)->postJson(
            route(
                'household.user.set-permissions',
                ['household' => $household, 'user' => $household->owner]
            ),
            [
                'admin' => false,
            ]
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'Only the owner can manage admins.']);
    }

    /**
     * Test assigning permissions to another user as an admin other than the owner
     */
    public function testAssignPermissionsAsAdmin()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Get a user to request as and set as admin
        $user = $users->random()->assignRole(RolesEnum::ADMIN);

        // Attempt to assign permissions to a random user
        $response = $this->actingAs($user)->postJson(
            route(
                'household.user.set-permissions',
                ['household' => $household, 'user' => $users->where('id', '!=', $user->id)->random()]
            ),
            [
                'permissions' => PermissionsEnum::cases(),
            ]
        );

        $response->assertOk();
    }

    /**
     * Test assigning permissions to yourself
     */
    public function testAssignOwnPermissions()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        User::factory()->for($household)->count(3)->create();

        // Attempt to assign permissions yourself
        $response = $this->actingAs($household->owner)->postJson(
            route(
                'household.user.set-permissions',
                ['household' => $household, 'user' => $household->owner]
            ),
            [
                'permissions' => PermissionsEnum::cases(),
            ]
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'Cannot change your own permissions.']);
    }

    /**
     * Test removing admin from a user
     */
    public function testRemoveAdmin()
    {
        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(3)->create();

        // Get a user and set as admin
        $user = $users->random()->assignRole(RolesEnum::ADMIN);

        // Remove admin from the user
        $response = $this->actingAs($household->owner)->postJson(
            route(
                'household.user.set-permissions',
                ['household' => $household, 'user' => $user]
            ),
            [
                'admin' => false,
                'permissions' => PermissionsEnum::cases(),
            ]
        );

        $response->assertOk();
        $this->assertFalse($user->hasRole(RolesEnum::ADMIN));
    }
}
