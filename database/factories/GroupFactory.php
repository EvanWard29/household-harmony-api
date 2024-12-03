<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'description' => fake()->words(asText: true),

            'household_id' => Household::factory(),
        ];
    }
}
