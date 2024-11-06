<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Http\Requests\Auth\TokenRequest;
use App\Http\Resources\UserResource;
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
    public function register(RegistrationRequest $request, ?HouseholdInvite $inviteToken = null): UserResource
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

        return new UserResource($user);
    }

    /**
     * Attempt to log in
     */
    public function login(LoginRequest $request)
    {
        if ($request->filled('username')) {
            $user = User::firstWhere('username', $request->input('username'));
        } else {
            $user = User::firstWhere('email', $request->input('email'));
        }

        if (! $user || ! \Hash::check($request->input('password'), $user->password)) {
            abort(\HttpStatus::HTTP_UNAUTHORIZED, 'The provided credentials are incorrect.');
        }
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
        if ($request->filled('username')) {
            $user = User::firstWhere('username', $request->input('username'));
        } else {
            $user = User::firstWhere('email', $request->input('email'));
        }

        if (! $user || ! \Hash::check($request->input('password'), $user->password)) {
            abort(\HttpStatus::HTTP_UNAUTHORIZED, 'The provided credentials are incorrect.');
        }

        $token = $user->createToken($request->input('device_name'))->plainTextToken;

        return response()->json(['token' => $token]);
    }

    /**
     * Confirm the authenticated user's entered password is correct
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'password' => 'required|confirmed|current_password:api'
        ]);

        return response('')
            ->cookie('password_confirmation_timeout', config('auth.password_timeout'));
    }
}
