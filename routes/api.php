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
        ->group(function () {
            Route::post('forgot', 'forgot')->name('forgot');
            Route::post('reset', 'reset')->name('reset');
        });
});

Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::prefix('user/{user}')
        ->name('user.')
        ->controller(UserController::class)
        ->group(function () {
            Route::apiSingleton('', UserController::class)
                ->destroyable()
                ->middleware([
                    'show' => 'can:view,user',
                    'update' => 'can:update,user',
                    'destroy' => 'can:delete,user',
                ]);
        });

    // Household routes
    Route::prefix('household/{household}')
        ->name('household.')
        ->controller(HouseholdController::class)
        ->group(function () {
            Route::apiSingleton('', HouseholdController::class, [
                'middleware' => [
                    'show' => 'can:view,household',
                    'update' => 'can:update,household',
                ],
            ]);

            Route::middleware('can:manage,household')->group(function () {
                Route::delete('{user}', 'deleteUser')->name('delete-user');

                Route::post('invite', 'invite')
                    ->middleware('verified')
                    ->name('invite');

                Route::post('child', 'createChild')->name('create-child');
            });
        });
});
