<?php

namespace Tests\Feature;

use App\Enums\RolesEnum;
use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(AuthController::class)]
class AuthenticationTest extends TestCase
{
    /**
     * Test registering a new user
     */
    public function testRegistration()
    {
        \Notification::fake();

        // Generate some test data
        $data = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'password' => $password = 'password123!!',
            'password_confirmation' => $password,
        ];

        // Attempt to register
        $response = $this->postJson(
            route('auth.register'),
            $data
        );

        $response->assertCreated();

        $this->assertDatabaseHas('users', ['email' => $data['email']]);

        $user = User::firstWhere('email', $data['email']);

        // Check the user's password was hashed
        $this->assertTrue(\Hash::check($data['password'], $user->password));

        // The user should be marked as active
        $this->assertTrue($user->is_active);

        // The user's email should not have been verified
        $this->assertNull($user->email_verified_at);

        // The user should have the `admin` role
        $this->assertTrue($user->hasRole(RolesEnum::ADMIN));

        // The user should be the owner of a household
        $this->assertTrue($user->household->owner_id === $user->id);

        // The user should have a username
        $this->assertSame($data['first_name'].$data['last_name'], $user->username);

        // A verification email should have been sent to the user
        \Notification::assertSentTo($user, VerifyEmail::class);
    }

    /**
     * Test validating a registration request
     */
    public function testRegistrationValidation()
    {
        // Generate some invalid test data
        $data = [
            'email' => 'email',
            'password' => $password = 'password',
            'password_confirmation' => $password,
        ];

        // Attempt to register
        $response = $this->postJson(
            route('auth.register'),
            $data
        );

        $response->assertUnprocessable();

        $response->assertJson([
            'errors' => [
                'first_name' => [
                    'The first name field is required.',
                ],
                'last_name' => [
                    'The last name field is required.',
                ],
                'password' => [
                    'The password field must contain at least one symbol.',
                    'The password field must contain at least one number.',
                ],
                'email' => [
                    'The email field must be a valid email address.',
                ],
            ],
        ]);
    }

    /**
     * Test registering creates a unique username
     */
    public function testRegistrationUsername()
    {
        // Generate some test data
        $data = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'password' => $password = 'password123!!',
            'password_confirmation' => $password,
        ];

        // Create a user with a matching name/username
        User::factory()->create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'username' => $data['first_name'].$data['last_name'],
        ]);

        // Check the created user has the correct username
        $this->assertDatabaseHas('users', ['username' => $data['first_name'].$data['last_name']]);

        // Attempt to register
        $response = $this->postJson(
            route('auth.register'),
            $data
        );

        $response->assertCreated();

        $user = User::find($response->json('data.id'));

        // The user's username should have 4 digits at the end
        $this->assertNotSame($data['first_name'].$data['last_name'], $user->username);
        $this->assertIsNumeric(\Str::substr($user->username, -4, 4));
    }

    /**
     * Test logging in with an email
     */
    public function testEmailLogin()
    {
        // Create a user to log in with
        $user = User::factory()->create([
            'password' => 'password123!!',
        ]);

        // Attempt to log in
        $response = $this->postJson(
            route('auth.login'),
            [
                'email' => $user->email,
                'password' => 'password123!!',
            ]
        );

        // The response should be successful
        $response->assertOk();
    }

    /**
     * Test logging in with a username
     */
    public function testUsernameLogin()
    {
        // Create a user to log in with
        $user = User::factory()->create([
            'password' => 'password123!!',
        ]);

        // Attempt to log in
        $response = $this->postJson(
            route('auth.login'),
            [
                'username' => $user->username,
                'password' => 'password123!!',
            ]
        );

        // The response should be successful
        $response->assertOk();
    }

    /**
     * Test validating a login request
     */
    public function testLoginValidation()
    {
        // Create a user to log in with
        $user = User::factory()->create([
            'password' => 'password123!!',
        ]);

        // Attempt to log in
        $response = $this->postJson(
            route('auth.login'),
            [
                'email' => $user->email,
                'password' => 'password',
            ]
        );

        // The response should be an error
        $response->assertUnauthorized();
        $response->assertJson([
            'message' => 'The provided credentials are incorrect.',
        ]);
    }

    /**
     * Test logging a user out
     */
    public function testLogout()
    {
        // Create a user to log out with
        $user = User::factory()->token()->create();
        $user->load('tokens');

        // Attempt to log out as the user
        $response = $this->actingAs($user)->postJson(
            route('auth.logout'),
            [
                'device_name' => 'token',
            ]
        );

        $response->assertOk();

        // The user's tokens should have been removed
        $user->tokens->each(fn (PersonalAccessToken $token) => $this->assertModelMissing($token));
    }

    /**
     * Test requesting a token with an email
     */
    public function testEmailToken()
    {
        // Create a user to request a token for
        $user = User::factory()->create([
            'password' => 'password123!!',
        ]);

        // Attempt to request a token
        $response = $this->postJson(
            route('auth.token'),
            [
                'email' => $user->email,
                'password' => 'password123!!',
                'device_name' => fake()->word(),
            ]
        );

        // Confirm a token was returned
        $response->assertCreated();
        $response->assertJson(function (AssertableJson $json) {
            $json->has('token');
        });

        // Confirm the returned token is for the user
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => \Str::substr($response->json('token'), 0, 1),
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * Test requesting a token with a username
     */
    public function testUsernameToken()
    {
        // Create a user to request a token for
        $user = User::factory()->create([
            'password' => 'password123!!',
        ]);

        // Attempt to request a token
        $response = $this->postJson(
            route('auth.token'),
            [
                'username' => $user->username,
                'password' => 'password123!!',
                'device_name' => fake()->word(),
            ]
        );

        // Confirm a token was returned
        $response->assertCreated();
        $response->assertJson(function (AssertableJson $json) {
            $json->has('token');
        });

        // Confirm the returned token is for the user
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => \Str::substr($response->json('token'), 0, 1),
            'tokenable_id' => $user->id,
        ]);
    }

    /**
     * Test validating a token request
     */
    public function testTokenValidation()
    {
        // Create a user to request a token for
        $user = User::factory()->create();

        // Attempt to request a token
        $response = $this->postJson(
            route('auth.token'),
            [
                'email' => $user->email,
                'password' => 'password123!!',
            ]
        );

        // The `device_name` should be a required field
        $response->assertUnprocessable();
        $response->assertJson([
            'errors' => [
                'device_name' => [
                    'The device name field is required.',
                ],
            ],
        ]);
    }

    /**
     * Test password validation for a token request
     */
    public function testTokenPasswordValidation()
    {
        // Create a user to request a token for
        $user = User::factory()->create();

        // Attempt to request a token
        $response = $this->postJson(
            route('auth.token'),
            [
                'email' => $user->email,
                'password' => 'password',
                'device_name' => fake()->word(),
            ]
        );

        // The `password` should be incorrect
        $response->assertUnauthorized();
        $response->assertJson([
            'message' => 'The provided credentials are incorrect.',
        ]);
    }

    /**
     * Test confirming a password
     */
    public function testConfirm()
    {
        // Create a user to confirm their password for
        $user = User::factory()->create([
            'password' => 'password123!!',
        ]);

        // Attempt to confirm the user's password
        $response = $this->actingAs($user)->postJson(
            route('auth.confirm-password'),
            [
                'password' => 'password123!!',
                'password_confirmation' => 'password123!!',
            ]
        );

        $response->assertOk();

        // A cookie should have been returned containing an encrypted token
        $response->assertCookie('password_confirmation');
        $cookie = $response->getCookie('password_confirmation', false);

        // The cache should contain the unencrypted token
        $this->assertNotNull($token = \Cache::get("password_confirmation:user:{$user->id}"));
        $this->assertSame($token, \Crypt::decrypt($cookie->getValue(), false));
    }

    /**
     * Test validation a confirm password request
     */
    public function testConfirmValidation()
    {
        // Create a user to confirm their password for
        $user = User::factory()->create([
            'password' => 'password123!!',
        ]);

        // Attempt to confirm the user's password
        $response = $this->actingAs($user)->postJson(
            route('auth.confirm-password'),
            [
                'password' => 'password',
                'password_confirmation' => 'password',
            ]
        );

        // The user's password should be incorrect
        $response->assertUnprocessable();
        $response->assertJson([
            'errors' => [
                'password' => [
                    'The password is incorrect.',
                ],
            ],
        ]);
    }
}
