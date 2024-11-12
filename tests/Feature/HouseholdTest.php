<?php

namespace Tests\Feature;

use App\Enums\RolesEnum;
use App\Http\Controllers\HouseholdController;
use App\Http\Middleware\PasswordConfirmationMiddleware;
use App\Models\Household;
use App\Models\User;
use Illuminate\Testing\Fluent\AssertableJson;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(HouseholdController::class)]
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
            ->deleteJson(route('household.delete-user', ['household' => $household, 'user' => $user]));

        $response->assertOk();

        $this->assertModelMissing($user);
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
                route('household.delete-user', ['household' => $household, 'user' => $household->owner])
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
                route('household.delete-user', ['household' => $household, 'user' => $user])
            );

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'Cannot delete the account of another Admin.',
        ]);
    }

    /**
     * Test assigning roles to a household member
     */
    public function testAssignRoles()
    {
        // TODO: Add support for additional roles

        // Create a household
        /** @var Household $household */
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        User::factory()->for($household)->count(3)->create();

        // Get a user to add the `admin` role to
        $user = $household->users->where(function (User $user) use ($household) {
            return $user->id != $household->owner_id;
        })->random();

        // Attempt to assign the user the `admin` role
        $response = $this->actingAs($household->owner)->postJson(
            route('household.assign-roles', ['household' => $household, 'user' => $user]),
            [
                'roles' => [
                    RolesEnum::ADMIN->value,
                ],
            ]
        );

        $response->assertOk();
        $this->assertTrue($user->hasRole(RolesEnum::ADMIN));
    }
}
