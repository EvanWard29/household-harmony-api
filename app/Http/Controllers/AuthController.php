<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Http\Requests\Auth\TokenRequest;
use App\Models\Household;
use App\Models\HouseholdInvite;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController
{
    /**
     * Register a new user
     */
    public function register(RegistrationRequest $request, ?HouseholdInvite $inviteToken = null): JsonResponse
    {
        if (! is_null($inviteToken)) {
            $user = $inviteToken->recipient;

            // Mark the invitee's email as verified as they were invited by email
            $user->email_verified_at = now();
        } else {
            $user = User::make([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
            ]);

            // Create a new household for new users
            $household = Household::create(['name' => "The $user->last_name's"]);
            $user->household()->associate($household);
        }

        // Set the user's password
        $user->password = \Hash::make($request->input('password'));

        // Set the user's username
        $user->username = \Str::studly($user->first_name.' '.$user->last_name);

        // Add a random 4 digit integer to the username to ensure it is unique
        while (User::where('username', $user->username)->exists()) {
            $user->username .= rand(1000, 9999);
        }

        // Set the user as active
        $user->is_active = true;

        $user->save();

        // Trigger registration event to dispatch email verification notification
        event(new Registered($user));

        // Delete the household invite if set
        $inviteToken?->delete();

        return response()->json(['message' => 'Registration successful.'], \HttpStatus::HTTP_CREATED);
    }

    /**
     * Attempt to log in
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Attempt to authenticate
        if (Auth::once($request->only('email', 'username', 'password'))) {
            return response()->json(['message' => 'Login success!']);
        }

        return response()->json(
            ['message' => 'The provided credentials do not match our records.'],
            \HttpStatus::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Remove the user's tokens for the device logging out
     */
    public function logout(Request $request): JsonResponse
    {
        $request->validate(['device_name' => 'required|string']);

        // Delete the user's tokens for this device
        $request->user()->tokens()->where('name', $request->input('device_name'))->delete();

        return response()->json(['message' => 'Logout success!']);
    }

    /**
     * Request a new Sanctum token for the user
     */
    public function token(TokenRequest $request): JsonResponse
    {
        // Attempt to authenticate and return a new token
        if (Auth::once($request->only('email', 'username', 'password'))) {
            return response()->json([
                'token' => Auth::user()->createToken(
                    $request->input('device_name')
                )->plainTextToken
            ]);
        }

        return response()->json(
            ['message' => 'The provided credentials do not match our records.'],
            \HttpStatus::HTTP_UNAUTHORIZED
        );
    }

    /**
     * Confirm the authenticated user's entered password is correct
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|confirmed|current_password:sanctum'
        ]);

        return response()->json()
            ->cookie('password_confirmation_timeout', config('auth.password_timeout'));
    }
}
