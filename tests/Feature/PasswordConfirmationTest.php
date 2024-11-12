<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\User;
use App\Notifications\DeletedUserNotification;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    /**
     * Test making a request to an endpoint with the `password.confirm` middleware
     */
    public function testTokenSuccess()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(rand(2, 4))->create();

        // Create and cache a `password_confirmation` token
        $token = cache()->remember(
            "password_confirmation:user:{$household->owner_id}",
            config('auth.password_timeout'),
            fn () => \Str::random()
        );

        // Attempt to make the request
        $response = $this->actingAs($household->owner)
            ->withCredentials()
            ->withUnencryptedCookie('password_confirmation', \Crypt::encryptString($token))
            ->deleteJson(route(
                'household.delete-user',
                ['household' => $household, 'user' => $user = $users->random()]
            ));

        $response->assertOk();

        \Notification::assertSentTo($user, DeletedUserNotification::class);

        // The user should have been deleted
        $this->assertModelMissing($user);
    }

    /**
     * Test making a request to an endpoint using the `password.confirm`
     * middleware without providing the `password_confirmation` token
     */
    public function testTokenPresence()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(rand(2, 4))->create();

        // Create and cache a `password_confirmation` token
        cache()->remember(
            "password_confirmation:user:{$household->owner_id}",
            config('auth.password_timeout'),
            fn () => \Str::random()
        );

        // Attempt to make the request
        $response = $this->actingAs($household->owner)
            ->withCredentials()
            ->deleteJson(route(
                'household.delete-user',
                ['household' => $household, 'user' => $users->random()]
            ));

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'Password has not been confirmed or token is missing.',
        ]);
    }

    /**
     * Test making a request to an endpoint with the `password.confirm` middleware with a modified token
     */
    public function testTokenEncryption()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(rand(2, 4))->create();

        // Create and cache a `password_confirmation` token
        $token = cache()->remember(
            "password_confirmation:user:{$household->owner_id}",
            config('auth.password_timeout'),
            fn () => \Str::random()
        );

        // Encrypt the token and modify
        $token = \Str::upper(\Crypt::encryptString($token));

        // Attempt to make the request with a modified token
        $response = $this->actingAs($household->owner)
            ->withCredentials()
            ->withUnencryptedCookie('password_confirmation', $token)
            ->deleteJson(route(
                'household.delete-user',
                ['household' => $household, 'user' => $users->random()]
            ));

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'Password confirmation token has been modified.',
        ]);
    }

    /**
     * Test making a request to an endpoint with the `password_confirmation` middleware using an invalid token
     */
    public function testTokenMismatch()
    {
        // Create a household
        $household = Household::factory()->hasOwner()->create();

        // Create some additional users for the household
        $users = User::factory()->for($household)->count(rand(2, 4))->create();

        // Create and cache a `password_confirmation` token
        cache()->remember(
            "password_confirmation:user:{$household->owner_id}",
            config('auth.password_timeout'),
            fn () => \Str::random()
        );

        // Attempt to make the request with an invalid token
        $response = $this->actingAs($household->owner)
            ->withCredentials()
            ->withUnencryptedCookie('password_confirmation', \Crypt::encryptString(\Str::random()))
            ->deleteJson(route(
                'household.delete-user',
                ['household' => $household, 'user' => $users->random()]
            ));

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'Password confirmation tokens do not match.',
        ]);
    }
}
