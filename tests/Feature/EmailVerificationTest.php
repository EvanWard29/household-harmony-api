<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    public function test_send()
    {
        // Create an unverified user
        $user = User::factory()->unverified()->create();

        // Request a new verification email
        $response = $this->actingAs($user)->post(route('user.verification.send', ['user' => $user]));

        $response->assertOk();

        \Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_send_validation()
    {
        // Create a verified user
        $user = User::factory()->create();

        // Request a new verification email
        $response = $this->actingAs($user)->post(route('user.verification.send', ['user' => $user]));

        $response->assertForbidden();
        $response->assertJson([
            'message' => 'Email already verified!',
        ]);
    }

    public function test_verify()
    {
        // Create an unverified user
        $user = User::factory()->unverified()->create();

        // Generate the verification url
        $url = \URL::temporarySignedRoute(
            'user.verification.verify',
            now()->addMinutes(config('auth.verification.expire')),
            [
                'user' => $user,
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        // Attempt to verify
        $response = $this->get($url);

        // TODO: Update the expected redirect
        $response->assertRedirect('/');

        // The user's email should now be verified
        $this->assertNotNull($user->refresh()->email_verified_at);
    }
}
