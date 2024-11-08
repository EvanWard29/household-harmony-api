<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => $firstName = fake()->firstName(),
            'last_name' => $lastName = fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'username' => $firstName.$lastName,
            'email_verified_at' => now(),
            'password' => \Hash::make('password123!!'),
            'is_active' => true,

            'household_id' => Household::factory(),
        ];
    }

    /**
     * Mark the user's email as unverified
     */
    public function unverified(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    /**
     * Create a token for the user
     */
    public function token($token = 'token'): static
    {
        return $this->afterCreating(fn (User $user) => $user->createToken($token));
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            // Set the user as the owner of their household
            $user->household->update(['owner_id' => $user->id]);
        });
    }
}
