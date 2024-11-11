<?php

namespace Tests\Feature;

use App\Http\Controllers\EmailVerificationController;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(EmailVerificationController::class)]
class EmailVerificationTest extends TestCase
{
    public function testSend()
    {
        \Notification::fake();

        // Create an unverified user
        $user = User::factory()->unverified()->create();

        // Request a new verification email
        $response = $this->actingAs($user)->post(route('user.verification.send', ['user' => $user]));

        $response->assertOk();

        \Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testSendValidation()
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

    public function testVerify()
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
