<?php

namespace Tests\Feature;

use App\Enums\TaskStatusEnum;
use App\Models\Group;
use App\Models\Household;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class TaskTest extends TestCase
{
    /**
     * Test getting the tasks of a household
     */
    public function testIndex()
    {
        // Create a household with tasks
        $household = Household::factory()->hasTasks(5)->create();

        // Attempt to get a household's task list
        $response = $this->actingAs($household->users->random())->getJson(
            route('household.task.index', ['household' => $household])
        );

        $response->assertOk();

        // The response should contain 5 tasks
        $response->assertJson(function (AssertableJson $json) {
            $json->has('data', 5);
        });
    }

    /**
     * Test filtering a household's tasks
     */
    public function testIndexFilter()
    {
        // Create a household
        $household = Household::factory()
            ->hasOwner()
            ->withUsers(4)
            ->hasGroups(2)
            ->create();

        // Create some tasks
        Task::factory(rand(100, 200))
            ->for($household)
            ->sequence(fn () => ['status' => \Arr::random(TaskStatusEnum::cases())])
            ->sequence(fn () => ['owner_id' => $household->users->random()->id])
            ->sequence(fn () => ['group_id' => $household->groups->random()->id])
            ->create();

        // Filter the household's tasks
        $response = $this->actingAs($household->users->random())->getJson(route(
            'household.task.index',
            [
                'household' => $household,

                'status' => TaskStatusEnum::TODO,
                'deadline' => [
                    'start' => [
                        'date' => ($start = today())->toDateString(),
                    ],
                    'end' => [
                        'date' => ($end = today()->addDays(4))->toDateString(),
                    ],
                ],
                'group_id' => ($group = $household->groups->random())->id,
                'assigned' => $assigned = $household->users->random(rand(1, 2))->pluck('id')->toArray(),
            ]
        ));

        $response->assertOk();

        // Get all tasks with the filtered status, group, deadline, and assigned users
        $tasks = $household->tasks()
            ->where('status', TaskStatusEnum::TODO)
            ->whereBetween('deadline', [$start->startOfDay(), $end->endOfDay()])
            ->whereRelation('group', 'id', $group->id)
            ->whereHas('assigned', function (Builder $query) use ($assigned) {
                $query->whereIn('id', $assigned);
            })
            ->get();

        $response->assertJsonCount($tasks->count(), 'data');
    }

    /**
     * Test creating a new task
     */
    public function testStore()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Create some groups/categories
        Group::factory()->for($household)->count(rand(2, 4))->create();

        // Create a task and assign to one of the users
        $response = $this->actingAs($household->owner)->postJson(
            route('household.task.store', ['household' => $household]),
            [
                'title' => fake()->word(),
                'description' => fake()->words(asText: true),
                'status' => TaskStatusEnum::TODO,
                'deadline' => fake()->dateTimeBetween('now', '+1 week')->format(\DateTime::ATOM),
                'group_id' => $household->groups->random()->id,
                'assigned' => $household->users->random(rand(1, 2))->pluck('id'),
            ]
        );

        $response->assertCreated();

        // A task should have been created
        $this->assertDatabaseCount('tasks', 1);

        $task = Task::first();

        // The task should have been assigned users
        $this->assertNotEmpty($task->assigned);
    }

    /**
     * Test creating a task without the correct permissions
     */
    public function testStorePermission()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Attempt to create a task as a user without permissions to do so
        $response = $this->actingAs($household->users->where('id', '!=', $household->owner_id)->random())->postJson(
            route('household.task.store', ['household' => $household]),
            [
                'title' => fake()->word(),
                'description' => fake()->words(asText: true),
            ]
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'You do not have permission to create tasks.']);
    }

    /**
     * Test getting a specific task
     */
    public function testShow()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Create a task and assign to some users
        $task = Task::factory()
            ->for($household)
            ->for($household->owner, 'owner')
            ->hasAttached($assigned = $household->users->random(rand(1, 2)), relationship: 'assigned')
            ->create();

        // Attempt to get the task as a user of the household
        $response = $this->actingAs($task->household->users->random())->getJson(
            route('household.task.show', ['household' => $household, 'task' => $task])
        );

        $response->assertOk();
        $response->assertJson(function (AssertableJson $json) use ($assigned) {
            $json->has('data', function (AssertableJson $json) use ($assigned) {
                // These fields should be present
                $json->hasAll([
                    'id',
                    'title',
                    'description',
                    'status',
                    'deadline',
                    'group',
                    'owner_id',
                    'household_id',
                ])->has('assigned', $assigned->count());
            });
        });
    }

    /**
     * Test updating a task
     */
    public function testUpdate()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Create some groups for the household
        $groups = Group::factory()->for($household)->count(rand(2, 4))->create();

        // Create a task and assign to some users
        $task = Task::factory()
            ->for($household)
            ->for($household->owner, 'owner')
            ->hasAttached($household->users->random(rand(1, 2)), relationship: 'assigned')
            ->create();

        // Attempt to update the task description, group, and remove the assigned users
        $response = $this->actingAs($task->owner)->patchJson(
            route('household.task.update', ['household' => $household, 'task' => $task]),
            [
                'title' => $task->title,
                'description' => $description = fake()->words(asText: true),
                'assigned' => [],
                'group_id' => ($group = $groups->random())->id,
            ]
        );

        $response->assertOk();
        $task->refresh();

        // The task's description should have changed
        $this->assertEquals($description, $task->description);

        // The task should have no assigned users
        $this->assertEmpty($task->assigned);

        // The tasks group should have changed
        $this->assertTrue($task->group()->is($group));
    }

    /**
     * Test updating a task without the correct permissions
     */
    public function testUpdatePermission()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Create a task and assign to some users
        $task = Task::factory()
            ->for($household)
            ->for($household->owner, 'owner')
            ->hasAttached($household->users->random(rand(1, 2)), relationship: 'assigned')
            ->create();

        // Get a random user without permissions to request as
        $user = $household->users->where('id', '!=', $household->owner_id)->random();

        // Attempt to update a task as a user without permissions to do so
        $response = $this->actingAs($user)
            ->patchJson(
                route('household.task.update', ['household' => $household, 'task' => $task]),
                [
                    'title' => fake()->word(),
                    'description' => fake()->words(asText: true),
                ]
            );

        $response->assertForbidden();
        $response->assertJson(['message' => 'You do not have permission to update this task.']);
    }

    /**
     * Test deleting a task
     */
    public function testDestroy()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Create a task for the household
        $task = Task::factory()
            ->for($household)
            ->for($household->owner, 'owner')
            ->create();

        // Delete the task
        $response = $this->actingAs($task->owner)->deleteJson(
            route('household.task.destroy', ['household' => $household, 'task' => $task])
        );

        $response->assertOk();

        // the task should have been removed
        $this->assertModelMissing($task);
    }

    /**
     * Test deleting a task without the correct permissions
     */
    public function testDestroyPermission()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Create a task for the household
        $task = Task::factory()
            ->for($household)
            ->for($household->owner, 'owner')
            ->create();

        // Get a random user without permissions to request as
        $user = $household->users->where('id', '!=', $household->owner_id)->random();

        // Attempt to delete the task
        $response = $this->actingAs($user)->deleteJson(
            route('household.task.destroy', ['household' => $household, 'task' => $task])
        );

        $response->assertForbidden();
        $response->assertJson(['message' => 'You do not have permission to delete this task.']);
    }
}