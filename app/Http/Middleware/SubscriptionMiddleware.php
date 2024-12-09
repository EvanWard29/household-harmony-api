<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SubscriptionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if user is subscribed
        abort_if(
            ! $request->user()->isSubscribed(),
            \HttpStatus::HTTP_FORBIDDEN,
            'User requires subscription to access feature.'
        );

        return $next($request);
    }
}
