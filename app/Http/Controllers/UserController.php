<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function destroy(Request $request): JsonResponse
    {
        // Delete all user tokens
        $request->user()->tokens()->delete();

        return $request->user()->delete()
            ? response()->json(['message' => 'User deleted!'])
            : response()->json(['message' => 'User was not deleted!'], \HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
}
