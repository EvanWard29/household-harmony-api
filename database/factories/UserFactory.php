<?php

namespace Database\Factories;

use App\Enums\RolesEnum;
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

    /**
     * Assign the {@see RolesEnum::ADMIN} to the user
     */
    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(RolesEnum::ADMIN));
    }

    /**
     * Create a pending user
     */
    public function pending(): static
    {
        return $this->unverified()->state(fn () => [
            'is_active' => false,
            'password' => null,
        ]);
    }

    /**
     * Create a child user
     */
    public function child(): static
    {
        return $this->unverified()
            ->state(function (array $attributes) {
                return [
                    'email' => null,
                    'username' => $attributes['first_name'].$attributes['last_name'],
                ];
            })
            ->afterCreating(fn (User $user) => $user->assignRole(RolesEnum::CHILD));
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user) {
            if (! $user->household->owner_id) {
                // Set the user as the owner of their household if not already set and assign them the `admin` role
                $user->household->update(['owner_id' => $user->id]);
                $user->assignRole(RolesEnum::ADMIN);
            }
        });
    }
}
