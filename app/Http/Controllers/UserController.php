<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController
{
    /**
     * Get the requesting {@see User}
     */
    public function show(): User
    {
        return request()->user();
    }

    /**
     * Update the requesting {@see User}
     */
    public function update(): User
    {
        return request()->user();
    }

    /**
     * Delete the requesting {@see User}
     */
    public function destroy(): bool
    {
        return request()->user()->delete();
    }
}
