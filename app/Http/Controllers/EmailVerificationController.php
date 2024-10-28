<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationController
{
    /**
     * Send an email for the user's email verification again
     */
    public function send(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified!'], \HttpStatus::HTTP_FORBIDDEN);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Verification link sent!']);
    }

    /**
     * Verify the user's email once they've clicked on the link
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect('/');
    }
}
