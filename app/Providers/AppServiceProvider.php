<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes($this->app->isLocal());

        ResetPassword::createUrlUsing(function (User $user, string $token) {
            // TODO: Replace with url to open mobile app
            return config('app.url').'/api/reset-password?token='.$token;
        });

        VerifyEmail::createUrlUsing(function (User $user) {
            return URL::temporarySignedRoute(
                'user.verification.verify',
                now()->addMinutes(config('auth.verification.expire')),
                [
                    'user' => $user->id,
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );
        });

        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->numbers()
                ->symbols();
        });
    }
}
