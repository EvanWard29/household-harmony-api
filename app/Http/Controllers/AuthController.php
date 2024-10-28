<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Http\Requests\Auth\TokenRequest;
use App\Models\Household;
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
    public function register(RegistrationRequest $request): JsonResponse
    {
        $user = User::make([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'email' => $request->input('email'),
            'password' => \Hash::make($request->input('password')),
        ]);

        // Create a new household
        // TODO: Household should be retrieved if the user was invited
        $household = Household::create(['name' => "The $user->last_name's"]);

        // Associate user with a household
        $user->household()->associate($household);

        $user->save();

        // Trigger registration event to dispatch email verification notification
        event(new Registered($user));

        return response()->json(['message' => 'Registration successful.'], 201);
    }

    /**
     * Attempt to log in
     */
    public function login(LoginRequest $request): JsonResponse
    {
        // Attempt to authenticate
        if (Auth::once($request->only('email', 'password'))) {
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
        $user = User::firstWhere('email', $request->input('email'));

        // Attempt to authenticate and return a new token
        if (Auth::once($request->only('email', 'password'))) {
            return response()->json([
                'token' => $user->createToken(
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
