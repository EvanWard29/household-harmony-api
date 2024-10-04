<?php

use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::post('/sanctum/token', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
        'device_name' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    return [
        'token' => $user->createToken($request->device_name)->plainTextToken,
        'user' => $user->id,
    ];
});

// Email Verification
Route::prefix('email')
    ->name('verification.')
    ->controller(EmailVerificationController::class)
    ->group(function () {
        Route::post('verification-notification', 'send')
            ->middleware(['auth:sanctum', 'throttle:6,1'])
            ->name('send');

        Route::get('verify/{id}/{hash}', 'verify')
            ->middleware(['signed'])
            ->name('verify');
    });

Route::apiSingleton('user', UserController::class)
    ->destroyable()
    ->middleware('auth:sanctum');
