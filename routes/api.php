<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->name('auth.')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('login', 'login')->name('login');
        Route::post('register/{inviteToken?}', 'register')->name('register');
        Route::post('logout', 'logout')->name('logout')->middleware('auth:sanctum');
        Route::post('token', 'token')->name('token');
        Route::post('confirm-password', 'confirm')->name('confirm-password')->middleware('auth:sanctum');
    });

Route::prefix('user')->group(function () {
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

    // Password Reset
    Route::prefix('password')
        ->name('password.')
        ->controller(PasswordResetController::class)
        ->middleware('guest')
        ->group(function () {
            Route::post('forgot', 'forgot')
                ->name('email');

            Route::post('reset', 'reset')
                ->name('update');
        });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiSingleton('user', UserController::class)
        ->destroyable();

    Route::prefix('household/{household}')->group(function () {
        Route::controller(HouseholdController::class)->group(function () {
            Route::get('', 'show')->can('view', 'household');
            Route::put('', 'update')->can('update', 'household');

            Route::post('invite', 'invite')->can('invite', 'household');
        });
    });
});
