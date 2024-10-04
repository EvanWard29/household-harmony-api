<?php

namespace App\Http\Requests;

use App\Models\User;
use Closure;

class EmailVerificationRequest extends \Illuminate\Foundation\Auth\EmailVerificationRequest
{
    public function setUserResolver(Closure $callback): EmailVerificationRequest
    {
        return parent::setUserResolver(function () {
            return User::findOrFail($this->route('id'));
        });
    }
}
