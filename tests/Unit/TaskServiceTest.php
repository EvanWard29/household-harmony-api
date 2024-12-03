<?php

namespace Tests\Unit;

use App\Enums\TaskStatusEnum;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use App\Models\UserReminder;
use App\Services\TaskService;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    private TaskService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TaskService::class);
    }

    /**
     * Test filtering tasks between two given dates
     */
    public function testFilterDate()
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

        // Get tasks between a given date range
        $tasks = $household->tasks()->whereBetween(
            'deadline',
            [($start = today())->startOfDay(), ($end = today()->addDays(3))->endOfDay()]
        )->get();

        // Get the filtered tasks and check they are correct
        $filtered = $this->service->getTasks($household, [
            'deadline' => [
                'start' => [
                    'date' => $start->toDateString(),
                ],
                'end' => [
                    'date' => $end->toDateString(),
                ],
            ],
        ]);

        $this->assertEqualsCanonicalizing($tasks->pluck('id'), $filtered->pluck('id'));
    }

    /**
     * Test attempting to filter tasks by deadline without providing dates
     */
    public function testFilterEmptyDeadline()
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

        // Attempting to filter tasks without setting dates will throw an HTTP exception
        $this->expectException(HttpException::class);

        // Attempt to filter tasks
        $this->service->getTasks($household, [
            'deadline' => [
                'start' => [
                    'time' => fake()->time('H:i'),
                ],
                'end' => [
                    'time' => fake()->time('H:i'),
                ],
            ],
        ]);
    }

    /**
     * Test filtering tasks between two given date times
     */
    public function testFilterDateTime()
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

        // Get tasks between a given time range
        $tasks = $household->tasks()->whereBetween(
            'deadline',
            [($start = today()->addDay()->setTime(9, 0)), ($end = today()->addDay()->setTime(17, 0))]
        )->get();

        // Get the filtered tasks and check they are correct
        $filtered = $this->service->getTasks($household, [
            'deadline' => [
                'start' => [
                    'date' => $start->toDateString(),
                    'time' => $start->toTimeString(),
                ],
                'end' => [
                    'date' => $end->toDateString(),
                    'time' => $end->toTimeString(),
                ],
            ],
        ]);

        $this->assertEqualsCanonicalizing($tasks->pluck('id'), $filtered->pluck('id'));
    }

    /**
     * Test filtering tasks by its status
     */
    public function testFilterStatus()
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

        // Get a status to filter by
        $status = \Arr::random(TaskStatusEnum::cases());

        // Get the expected tasks
        $tasks = $household->tasks()->where('status', $status)->get();

        // Get the actual tasks
        $filtered = $this->service->getTasks($household, ['status' => $status->value]);

        $this->assertEqualsCanonicalizing($tasks->pluck('id'), $filtered->pluck('id'));
    }

    /**
     * Test filtering tasks by group
     */
    public function testFilterGroup()
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

        $group = $household->groups->random();

        // Get the expected tasks
        $tasks = $household->tasks()->whereBelongsTo($group)->get();

        // Get the actual tasks
        $filtered = $this->service->getTasks($household, ['group_id' => $group->id]);

        $this->assertEqualsCanonicalizing($tasks->pluck('id'), $filtered->pluck('id'));
    }

    /**
     * Test filtering tasks by assigned users
     */
    public function testFilterAssigned()
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

        // Choose some users to filter by
        $assigned = $household->users->random(rand(1, 3));

        // Get the expected tasks
        $tasks = $household->tasks()->whereHas('assigned', function (Builder $query) use ($assigned) {
            $query->whereIn('id', $assigned->pluck('id'));
        })->get();

        // Get the actual tasks
        $filtered = $this->service->getTasks($household, ['assigned' => $assigned->pluck('id')->toArray()]);

        $this->assertEqualsCanonicalizing($tasks->pluck('id'), $filtered->pluck('id'));
    }

    /**
     * Test scheduling reminders for a task
     */
    public function testScheduleReminders()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Create a task
        $task = Task::factory()->hasAttached($household->users->random(rand(1, 2)), relationship: 'assigned')->create();

        // Remove any already scheduled reminders
        $task->reminders()->delete();

        $this->assertDatabaseEmpty('task_reminders');

        // Schedule reminders
        $this->service->scheduleReminders($task);

        $task->assigned->each(function (User $user) {
            $user->reminders->each(function (UserReminder $reminder) {
                $this->assertDatabaseHas('task_reminders', ['user_reminder_id' => $reminder->id]);
            });
        });
    }

    /**
     * Test scheduling reminders with an empty task deadline
     */
    public function testScheduleRemindersEmptyDeadline()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->withUsers()->create();

        // Create a task
        $task = Task::factory()
            ->hasAttached($household->users->random(rand(1, 2)), relationship: 'assigned')
            ->create(['deadline' => null]);

        // Remove any already scheduled reminders
        $task->reminders()->delete();

        $this->assertDatabaseEmpty('task_reminders');

        // Schedule reminders
        $this->service->scheduleReminders($task);

        // Reminders should not have been scheduled as the `deadline` is empty
        $this->assertDatabaseEmpty('task_reminders');
    }
}
