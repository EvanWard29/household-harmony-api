<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;

class PasswordConfirmationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check the user has confirmed their password, and the encrypted token is present in the request
        if (
            ! ($cachedToken = \Cache::get("password-confirmation:user:{$request->user()->id}"))
            || ! $request->hasCookie('password_confirmation')
        ) {
            abort(\HttpStatus::HTTP_FORBIDDEN, 'Password has not been confirmed.');
        }

        try {
            // Attempt to decrypt the provided password confirmation token
            $token = \Crypt::decryptString($request->cookie('password_confirmation'));
        } catch (DecryptException $e) {
            abort(\HttpStatus::HTTP_FORBIDDEN, 'Password confirmation tokens do not match.');
        }

        // Check if the tokens match
        if ($token !== $cachedToken) {
            abort(\HttpStatus::HTTP_FORBIDDEN, 'Password confirmation tokens do not match.');
        }

        return $next($request);
    }
}
