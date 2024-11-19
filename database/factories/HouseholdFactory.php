<?php

namespace Database\Factories;

use App\Enums\RolesEnum;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class HouseholdFactory extends Factory
{
    protected $model = Household::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),

            'owner_id' => null,
        ];
    }

    /**
     * Indicate an owner should be created/assigned for this household
     */
    public function hasOwner(?User $owner = null): static
    {
        return $this->afterCreating(function (Household $household) use ($owner) {
            if (! $owner && $household->users->isNotEmpty()) {
                // Assign one of the existing users as the owner
                $owner = $household->users->random();
            } elseif (! $owner) {
                // Create a new user to be the owner
                $owner = User::factory()->for($household)->create();
            }

            // Ensure the owner is an admin
            if (! $owner->isAdmin()) {
                $owner->assignRole(RolesEnum::ADMIN);
            }

            $household->owner()->associate($owner);
        });
    }

    /**
     * Create the household with some additional users
     */
    public function withUsers(?int $count = null): static
    {
        return $this->has(User::factory(! $count ? rand(2, 5) : $count));
    }
}
