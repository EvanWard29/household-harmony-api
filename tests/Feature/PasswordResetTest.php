<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    /**
     * Test requesting a password reset link
     */
    public function test_forgot()
    {
        // Create a user
        $user = User::factory()->create();

        // Request a password reset link
        $response = $this->postJson(
            route('user.password.forgot'),
            ['email' => $user->email]
        );

        $response->assertOk();

        // The user should have been sent a password reset notification
        \Notification::assertSentTo($user, ResetPassword::class);

        // A reset token should have been created
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    /**
     * Test resetting a user's password
     */
    public function test_reset()
    {
        // Create a user
        $user = User::factory()->create([
            'password' => $password = 'password',
        ]);

        // Create a reset token
        $token = \Password::createToken($user);

        // Attempt to set a new password
        $response = $this->postJson(
            route('user.password.reset'),
            [
                'token' => $token,
                'email' => $user->email,
                'password' => 'password123!!',
                'password_confirmation' => 'password123!!',
            ]
        );

        $response->assertOk();

        // The user's password should have been changed
        $user->refresh();
        $this->assertNotEquals($password, $user->password);

        // The password reset token should have been removed
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    /**
     * Test resetting a password with an invalid token
     */
    public function test_reset_invalid_token()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a reset token
        \Password::createToken($user);

        // Attempt to set a new password using an invalid token
        $response = $this->postJson(
            route('user.password.reset'),
            [
                'token' => hash_hmac('sha256', \Str::random(40), config('auth.passwords.users.expire')),
                'email' => $user->email,
                'password' => 'password123!!',
                'password_confirmation' => 'password123!!',
            ]
        );

        $response->assertForbidden();
        $response->assertJson([
            'message' => ['This password reset token is invalid.'],
        ]);
    }

    /**
     * Test validating a password reset request
     */
    public function test_reset_validation()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a reset token
        $token = \Password::createToken($user);

        // Attempt to set a new password
        $response = $this->postJson(
            route('user.password.reset'),
            [
                'token' => $token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        $response->assertUnprocessable();
        $response->assertJson([
            'errors' => [
                'password' => [
                    'The password field must contain at least one symbol.',
                    'The password field must contain at least one number.',
                ],
            ],
        ]);
    }
}
