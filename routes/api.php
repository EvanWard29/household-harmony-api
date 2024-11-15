<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\HouseholdInviteController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->name('auth.')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('login', 'login')->name('login');
        Route::post('register/{inviteToken?}', 'register')->name('register');
        Route::post('logout', 'logout')->name('logout')->middleware('auth:api');
        Route::post('token', 'token')->name('token');
        Route::post('confirm-password', 'confirm')->name('confirm-password')->middleware('auth:api');
    });

Route::prefix('user')->name('user.')->group(function () {
    // Email Verification
    Route::prefix('{user}/email')
        ->name('verification.')
        ->controller(EmailVerificationController::class)
        ->group(function () {
            Route::post('verification-notification', 'send')
                ->middleware(['auth:api', 'throttle:6,1'])
                ->name('send');

            Route::get('verify/{hash}', 'verify')
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

Route::middleware('auth:api')->group(function () {
    // User routes
    Route::prefix('user/{user}')
        ->name('user.')
        ->controller(UserController::class)
        ->middleware('can:view,user')
        ->group(function () {
            Route::apiSingleton('/', UserController::class);
            Route::get('task', 'tasks')->name('tasks');
        });

    // Household routes
    Route::prefix('household/{household}')
        ->name('household.')
        ->middleware('can:view,household')
        ->group(function () {
            Route::controller(HouseholdController::class)->group(function () {
                Route::get('', 'show')->name('show');

                Route::middleware('can:manage,household')->group(function () {
                    Route::match(['put', 'patch'], '', 'update')->name('update');
                    Route::post('{user}/roles', 'assignRoles')->name('assign-roles');
                });

                Route::prefix('user/{user}')->group(function () {
                    Route::delete('/', 'deleteUser')
                        ->middleware('password.confirm')
                        ->name('delete-user');
                });
            });

            Route::controller(HouseholdInviteController::class)
                ->middleware('can:manage,household')
                ->group(function () {
                    Route::post('invite', 'invite')
                        ->middleware('verified')
                        ->name('invite');

                    Route::post('child', 'createChild')->name('create-child');
                });

            // Task routes
            Route::controller(TaskController::class)
                ->group(function () {
                    Route::apiResource('task', TaskController::class);
                });

            // Group routes
            Route::controller(GroupController::class)
                ->group(function () {
                    Route::apiResource('group', GroupController::class);
                });
        });
});
