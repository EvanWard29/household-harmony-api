<?php

namespace Tests\Feature;

use App\Enums\PermissionsEnum;
use App\Models\Household;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class GroupTest extends TestCase
{
    /**
     * Test getting a list of a households groups/categories
     */
    public function testIndex()
    {
        // Create a household with groups
        $household = Household::factory()->hasOwner()->withUsers()->hasGroups(rand(2, 4))->create();

        // Attempt to get a list of a household's groups
        $response = $this->actingAs($household->users->random())->getJson(
            route('household.group.index', ['household' => $household])
        );

        $response->assertOk();
        $response->assertJson(function (AssertableJson $json) use ($household) {
            $json->has('data', $household->groups->count());
        });
    }

    /**
     * Test creating a new group/category
     */
    public function testStore()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Attempt to creat a group
        $response = $this->actingAs($household->owner)->postJson(
            route('household.group.store', ['household' => $household]),
            [
                'name' => fake()->word(),
                'description' => fake()->words(asText: true),
            ]
        );

        $response->assertCreated();

        // A group should have been created
        $this->assertNotEmpty($household->groups);
    }

    /**
     * Test attempting to create a group without the correct permissions
     */
    public function testStorePermissions()
    {
        // Create a household with some additional users
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Get a user to request as
        $user = $household->users->where('id', '!=', $household->owner_id)->random();

        // Attempt to create a group
        $response = $this->actingAs($user)->postJson(
            route('household.group.store', ['household' => $household]),
            ['name' => fake()->word()]
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'You do not have permission to manage groups/categories.']);
    }

    /**
     * Test getting a specific group/category
     */
    public function testShow()
    {
        // Create a household with some groups
        $household = Household::factory()->hasOwner()->hasGroups(rand(2, 4))->create();

        // Attempt to get one of the groups
        $response = $this->actingAs($household->owner)->getJson(
            route(
                'household.group.show',
                ['household' => $household, 'group' => $group = $household->groups->random()]
            )
        );

        $response->assertOk();
        $response->assertJson(function (AssertableJson $json) {
            $json->has('data', function (AssertableJson $json) {
                $json->hasAll([
                    'name',
                    'description',
                    'id',
                    'household_id',
                ]);
            });
        });
    }

    /**
     * Test updating the details of a group/category
     */
    public function testUpdate()
    {
        // Create a household with some groups
        $household = Household::factory()->hasOwner()->hasGroups(rand(2, 4))->create();

        // Attempt to update one of the groups
        $response = $this->actingAs($household->owner)->patchJson(
            route(
                'household.group.update',
                ['household' => $household, 'group' => $group = $household->groups->random()]
            ),
            [
                'name' => $name = fake()->word(),
                'description' => null,
            ]
        );

        $response->assertOk();
        $group->refresh();

        // The group's name should have been updated
        $this->assertEquals($name, $group->name);

        // The group's description should be empty
        $this->assertEmpty($group->description);
    }

    /**
     * Attempt to update a group as a user with the correct permission other than the admin
     */
    public function testUpdatePermissions()
    {
        // Create a household with some users and groups
        $household = Household::factory()->hasOwner()->withUsers()->hasGroups(rand(2, 4))->create();

        // Give one of the users the relevant permissions
        $user = $household->users->where('id', '!=', $household->owner_id)->random();
        $user->givePermissionTo(PermissionsEnum::GROUP_MANAGE);

        // Attempt to update a group
        $response = $this->actingAs($user)->patchJson(
            route(
                'household.group.update',
                ['household' => $household, 'group' => $group = $household->groups->random()]
            ),
            [
                'name' => $name = fake()->word(),
                'description' => null,
            ]
        );

        $response->assertOk();
        $group->refresh();

        // The group's name should have been updated
        $this->assertEquals($name, $group->name);

        // The group's description should be empty
        $this->assertEmpty($group->description);
    }

    /**
     * Test deleting a group/category
     */
    public function testDestroy()
    {
        // Create a household with some groups
        $household = Household::factory()->hasOwner()->hasGroups(rand(2, 4))->create();

        // Attempt to delete a group
        $response = $this->actingAs($household->owner)->deleteJson(
            route(
                'household.group.destroy',
                ['household' => $household, 'group' => $group = $household->groups->random()]
            )
        );

        $response->assertOk();

        // The group should have been deleted
        $this->assertModelMissing($group);
    }

    /**
     * Attempt to delete a group as a user with the correct permission other than the admin
     */
    public function testDestroyPermissions()
    {
        // Create a household with some users and groups
        $household = Household::factory()->hasOwner()->withUsers()->hasGroups(rand(2, 4))->create();

        // Give one of the users the relevant permissions
        $user = $household->users->where('id', '!=', $household->owner_id)->random();
        $user->givePermissionTo(PermissionsEnum::GROUP_MANAGE);

        // Attempt to delete a group
        $response = $this->actingAs($user)->deleteJson(
            route(
                'household.group.update',
                ['household' => $household, 'group' => $group = $household->groups->random()]
            )
        );

        $response->assertOk();

        // The group should have been deleted
        $this->assertModelMissing($group);
    }
}
