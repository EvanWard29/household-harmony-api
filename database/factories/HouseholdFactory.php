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
            if (! $owner) {
                $owner = User::factory()->create([
                    'household_id' => $household->id,
                ]);
            }

            if (! $owner->hasRole(RolesEnum::ADMIN)) {
                $owner->assignRole(RolesEnum::ADMIN);
            }

            $household->owner()->associate($owner);
        });
    }
}
